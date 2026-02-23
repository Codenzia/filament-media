<?php

namespace Codenzia\FilamentMedia\Services;

use Codenzia\FilamentMedia\Events\MediaFileUploaded;
use Codenzia\FilamentMedia\Exceptions\MediaUploadException;
use Codenzia\FilamentMedia\Helpers\BaseHelper;
use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaFolder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use League\Flysystem\UnableToWriteFile;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Symfony\Component\Mime\MimeTypes;
use Throwable;

/**
 * Orchestrates file uploads from multiple sources (form input, URL, path, blob, editor),
 * handling validation, SSRF protection, folder resolution, and storage delegation.
 *
 * All public upload methods return MediaFile directly and throw MediaUploadException on failure.
 */
class UploadService
{
    public function __construct(
        protected StorageDriverService $storageDriver,
        protected MediaUrlService $urlService,
        protected ImageService $imageService,
        protected UploadsManager $uploadManager
    ) {}

    /**
     * Upload a file from a form input.
     *
     * @param  string|null  $allowedExtensions  Per-field extension override (comma-separated), or null for global default
     *
     * @throws MediaUploadException
     */
    public function handleUpload(
        ?UploadedFile $fileUpload,
        int|string|null $folderId = 0,
        ?string $folderSlug = null,
        bool $skipValidation = false,
        string $visibility = 'public',
        ?string $allowedExtensions = null,
    ): MediaFile {
        $request = request();

        if ($uploadPath = $request->input('path')) {
            $folderId = $this->handleTargetFolder($folderId, $uploadPath);
        }

        if (! $fileUpload) {
            throw MediaUploadException::noFileDetected();
        }

        if (! $this->isChunkUploadEnabled()) {
            $this->validateUpload($fileUpload, $skipValidation);
        }

        return $this->storeFile($fileUpload, $folderId, $folderSlug, $skipValidation, $visibility, $allowedExtensions);
    }

    /**
     * Download a file from a URL and upload it.
     *
     * @throws MediaUploadException
     */
    public function uploadFromUrl(
        string $url,
        int|string $folderId = 0,
        ?string $folderSlug = null,
        ?string $defaultMimetype = null
    ): MediaFile {
        if (empty($url)) {
            throw MediaUploadException::invalidUrl($url);
        }

        $ssrfError = $this->validateUrlForSsrf($url);
        if ($ssrfError) {
            throw MediaUploadException::ssrfBlocked($ssrfError);
        }

        try {
            $response = Http::timeout(30)->get($url);

            if ($response->failed() || ! $response->body()) {
                throw MediaUploadException::networkError(
                    $url,
                    $response->reason() ?: ''
                );
            }

            $contents = $response->body();
        } catch (MediaUploadException $e) {
            throw $e;
        } catch (Throwable $e) {
            logger()->error('Failed to download file from URL', ['url' => $url, 'error' => $e->getMessage()]);

            throw MediaUploadException::networkError(
                $url,
                $e->getMessage() ?: trans('filament-media::media.validation.upload_network_error')
            );
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'media_');
        if ($tempPath === false || file_put_contents($tempPath, $contents) === false) {
            @unlink($tempPath);

            throw MediaUploadException::tempFileError();
        }

        try {
            $fileUpload = $this->newUploadedFile($tempPath, $defaultMimetype);

            return $this->handleUpload($fileUpload, $folderId, $folderSlug);
        } finally {
            @unlink($tempPath);
        }
    }

    /**
     * Upload a file from a local path.
     *
     * @throws MediaUploadException
     */
    public function uploadFromPath(
        string $path,
        int|string $folderId = 0,
        ?string $folderSlug = null,
        ?string $defaultMimetype = null
    ): MediaFile {
        if (empty($path)) {
            throw MediaUploadException::invalidPath();
        }

        $fileUpload = $this->newUploadedFile($path, $defaultMimetype);

        return $this->handleUpload($fileUpload, $folderId, $folderSlug);
    }

    /**
     * Upload a file from a blob.
     *
     * @throws MediaUploadException
     */
    public function uploadFromBlob(
        UploadedFile $path,
        ?string $fileName = null,
        int|string $folderId = 0,
        ?string $folderSlug = null,
    ): MediaFile {
        $fileUpload = new UploadedFile($path, $fileName ?: Str::uuid());

        return $this->handleUpload($fileUpload, $folderId, $folderSlug, true);
    }

    /**
     * Upload a file from a CKEditor request.
     * Returns an HTTP response (CKEditor callback or JSON) — this is the one
     * method that does NOT follow the throw-on-error pattern because it must
     * return CKEditor-specific response formats.
     */
    public function uploadFromEditor(
        $request,
        int|string|null $folderId = 0,
        $folderName = null,
        string $fileInput = 'upload'
    ) {
        $validator = Validator::make($request->all(), [
            'upload' => 'required|image|mimes:jpg,jpeg,png,webp,gif,bmp',
        ], [
            'upload.required' => trans('filament-media::media.validation.uploaded_file_required'),
            'upload.image' => trans('filament-media::media.validation.uploaded_file_invalid_type'),
            'upload.mimes' => trans('filament-media::media.validation.uploaded_file_invalid_type'),
        ], [
            'upload' => trans('filament-media::media.validation.attributes.uploaded_file'),
        ]);

        if ($validator->fails()) {
            $errorMessage = $validator->getMessageBag()->first();

            if (! $request->input('CKEditorFuncNum')) {
                return response()->json([
                    'uploaded' => 0,
                    'error' => ['message' => $errorMessage],
                ], 422);
            }

            return response(
                '<script>window.parent.CKEDITOR.tools.callFunction(' .
                json_encode($request->input('CKEditorFuncNum')) . ', "", ' .
                json_encode($errorMessage) . ');</script>'
            )->header('Content-Type', 'text/html');
        }

        $folderName = $folderName ?: $request->input('upload_type');

        try {
            $file = $this->handleUpload($request->file($fileInput), $folderId, $folderName);

            if (! $request->input('CKEditorFuncNum')) {
                return response()->json([
                    'fileName' => File::name($this->urlService->url($file->url)),
                    'uploaded' => 1,
                    'url' => $this->urlService->url($file->url),
                ]);
            }

            return response(
                '<script>window.parent.CKEDITOR.tools.callFunction("' . $request->input('CKEditorFuncNum') .
                '", "' . $this->urlService->url($file->url) . '", "");</script>'
            )->header('Content-Type', 'text/html');
        } catch (MediaUploadException $e) {
            $errorMessage = $e->getMessage();

            if (! $request->input('CKEditorFuncNum')) {
                return response()->json([
                    'uploaded' => 0,
                    'error' => ['message' => $errorMessage],
                ], 422);
            }

            return response(
                '<script>window.parent.CKEDITOR.tools.callFunction(' .
                json_encode($request->input('CKEditorFuncNum')) . ', "", ' .
                json_encode($errorMessage) . ');</script>'
            )->header('Content-Type', 'text/html');
        }
    }

    public function getMaxSize(): int
    {
        $maxUploadFilesizeAllowed = \setting('media_max_file_size');
        if ($maxUploadFilesizeAllowed) {
            return (int) $maxUploadFilesizeAllowed;
        }

        return (int) $this->getServerConfigMaxUploadFileSize();
    }

    public function getMaxSizeForHumans(): string
    {
        return BaseHelper::humanFilesize($this->getMaxSize());
    }

    public function getAllowedMimeTypes(): array
    {
        $allowedMimeTypes = $this->getConfig('allowed_mime_types', '');

        if (empty($allowedMimeTypes)) {
            return [];
        }

        return array_map('trim', explode(',', $allowedMimeTypes));
    }

    public function getAllowedMimeTypesString(): string
    {
        return $this->getConfig('allowed_mime_types', '');
    }

    public function isChunkUploadEnabled(): bool
    {
        return (bool) \setting('media_chunk_enabled', (int) $this->getConfig('chunk.enabled') == 1);
    }

    public function validateUrlForSsrf(string $url): ?string
    {
        $parsed = parse_url($url);

        if ($parsed === false || ! isset($parsed['host'])) {
            return trans('filament-media::media.url_invalid');
        }

        $scheme = strtolower($parsed['scheme'] ?? '');
        if (! in_array($scheme, ['http', 'https'])) {
            return trans('filament-media::media.url_scheme_not_allowed');
        }

        $host = strtolower($parsed['host']);

        if (in_array($host, ['localhost', '127.0.0.1', '::1', '0.0.0.0', '[::1]'])) {
            return trans('filament-media::media.url_internal_not_allowed');
        }

        $ip = gethostbyname($host);

        if ($ip === $host && ! filter_var($host, FILTER_VALIDATE_IP)) {
            logger()->warning('SSRF check: Could not resolve hostname', ['url' => $url, 'host' => $host]);

            return trans('filament-media::media.url_invalid');
        }

        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return trans('filament-media::media.url_invalid');
        }

        $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
        if (! filter_var($ip, FILTER_VALIDATE_IP, $flags)) {
            return trans('filament-media::media.url_internal_not_allowed');
        }

        $blockedIps = ['169.254.169.254', '169.254.170.2', '100.100.100.200'];
        if (in_array($ip, $blockedIps)) {
            return trans('filament-media::media.url_internal_not_allowed');
        }

        $allowedDomains = $this->getConfig('allowed_download_domains', []);
        if (! empty($allowedDomains)) {
            $isAllowed = false;
            foreach ($allowedDomains as $domain) {
                $domain = strtolower($domain);
                if ($host === $domain || Str::endsWith($host, '.' . $domain)) {
                    $isAllowed = true;
                    break;
                }
            }
            if (! $isAllowed) {
                return trans('filament-media::media.url_domain_not_allowed');
            }
        }

        return null;
    }

    /**
     * @throws MediaUploadException
     */
    protected function validateUpload(UploadedFile $fileUpload, bool $skipValidation): void
    {
        if (! $skipValidation) {
            $validator = Validator::make(['uploaded_file' => $fileUpload], [
                'uploaded_file' => ['required'],
            ], [
                'uploaded_file.required' => trans('filament-media::media.validation.uploaded_file_required'),
                'uploaded_file.file' => trans('filament-media::media.validation.uploaded_file_invalid_type'),
                'uploaded_file.types' => trans('filament-media::media.validation.uploaded_file_invalid_type'),
            ], [
                'uploaded_file' => trans('filament-media::media.validation.attributes.uploaded_file'),
            ]);

            if ($validator->fails()) {
                throw MediaUploadException::validationFailed($validator->getMessageBag()->first());
            }
        }

        $maxUploadFilesizeAllowed = \setting('max_upload_filesize');
        if ($maxUploadFilesizeAllowed && ($fileUpload->getSize() / 1024) / 1024 > (float) $maxUploadFilesizeAllowed) {
            throw MediaUploadException::fileTooLarge(
                BaseHelper::humanFilesize($maxUploadFilesizeAllowed * 1024 * 1024)
            );
        }

        $maxSize = $this->getServerConfigMaxUploadFileSize();
        if ($fileUpload->getSize() / 1024 > (int) $maxSize) {
            throw MediaUploadException::fileTooLarge(
                BaseHelper::humanFilesize($maxSize * 1024)
            );
        }
    }

    /**
     * @param  string|null  $allowedExtensions  Per-field extension override (comma-separated), or null for global default
     *
     * @throws MediaUploadException
     */
    protected function storeFile(
        UploadedFile $fileUpload,
        int|string|null $folderId,
        ?string $folderSlug,
        bool $skipValidation,
        string $visibility,
        ?string $allowedExtensions = null,
    ): MediaFile {
        $effectiveTypes = $allowedExtensions ?? $this->getConfig('allowed_mime_types');

        try {
            $fileExtension = strtolower(
                $fileUpload->getClientOriginalExtension() ?: $fileUpload->guessExtension()
            );

            $allowedList = array_map('trim', explode(',', $effectiveTypes));

            if (! $skipValidation && ! in_array($fileExtension, $allowedList)) {
                throw MediaUploadException::invalidFileType($effectiveTypes);
            }

            $folderId = $this->resolveFolder($folderId, $folderSlug);

            $file = new MediaFile;
            $fileName = File::name($fileUpload->getClientOriginalName());
            $file->name = MediaFile::createName($fileName, $folderId);

            $folderPath = MediaFolder::getFullPath($folderId);
            $diskFileName = MediaFile::createSlug($file->name, $fileExtension, $folderPath ?: '');

            $filePath = $folderPath ? $folderPath . '/' . $diskFileName : $diskFileName;

            if ($this->storageDriver->getMediaDriver() === 's3') {
                $filePath = $this->storageDriver->getCustomS3Path() . $filePath;
            }

            $content = ($fileUpload instanceof TemporaryUploadedFile)
                ? $fileUpload->get()
                : File::get($fileUpload->getRealPath());

            $this->uploadManager->saveFile($filePath, $content, $fileUpload, $visibility);

            $data = $this->uploadManager->fileDetails($filePath);

            $file->url = $data['url'];
            $file->alt = $file->name;
            $file->size = $data['size'] ?: $fileUpload->getSize();
            $file->mime_type = $data['mime_type'];
            $file->folder_id = $folderId;
            $file->user_id = Auth::guard()->check() ? Auth::guard()->id() : 0;
            $file->options = request()->input('options', []);
            $file->visibility = $visibility;
            $file->save();

            MediaFileUploaded::dispatch($file);

            $this->imageService->generateThumbnails($file, $fileUpload);

            return $file;
        } catch (MediaUploadException $e) {
            throw $e;
        } catch (UnableToWriteFile $e) {
            $message = $this->storageDriver->isUsingCloud()
                ? $e->getMessage()
                : trans('filament-media::media.unable_to_write', ['folder' => $this->storageDriver->getUploadPath()]);

            throw MediaUploadException::unableToWrite($message);
        } catch (Throwable $e) {
            throw new MediaUploadException(
                $e->getMessage() ?: trans('filament-media::media.validation.upload_network_error'),
                0,
                $e
            );
        }
    }

    protected function resolveFolder(int|string|null $folderId, ?string $folderSlug): int|string|null
    {
        if ($folderId == 0 && ! empty($folderSlug)) {
            if (str_contains($folderSlug, '/')) {
                $paths = array_filter(explode('/', $folderSlug));
                foreach ($paths as $folder) {
                    $folderId = $this->createFolder($folder, $folderId);
                }
            } else {
                $folderId = $this->createFolder($folderSlug, $folderId);
            }
        }

        return $folderId;
    }

    protected function createFolder(string $folderSlug, int|string|null $parentId = 0): int|string
    {
        $folder = MediaFolder::query()
            ->where(['slug' => $folderSlug, 'parent_id' => $parentId])
            ->first();

        if (! $folder) {
            MediaFolder::query()
                ->where(['slug' => $folderSlug, 'parent_id' => $parentId])
                ->each(fn (MediaFolder $f) => $f->forceDelete());

            $folder = MediaFolder::query()->create([
                'user_id' => Auth::guard()->check() ? Auth::guard()->id() : 0,
                'name' => MediaFolder::createName($folderSlug, $parentId),
                'slug' => MediaFolder::createSlug($folderSlug, $parentId),
                'parent_id' => $parentId,
            ]);
        }

        return $folder->id;
    }

    protected function handleTargetFolder(int|string|null $folderId, string $filePath): int|string|null
    {
        if (str_contains($filePath, '/')) {
            $paths = array_filter(explode('/', $filePath));
            array_pop($paths);
            foreach ($paths as $folder) {
                $folderId = $this->createFolder($folder, $folderId);
            }
        }

        return $folderId;
    }

    protected function newUploadedFile(string $path, ?string $defaultMimeType = null): UploadedFile
    {
        $mimeType = $this->urlService->getMimeType($path) ?: $defaultMimeType;

        // For temp files (e.g. from URL downloads), detect MIME type from contents
        // since the file extension is meaningless (.tmp on Windows, none on Linux).
        if (! $mimeType && file_exists($path)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($path) ?: $defaultMimeType;
        }

        $fileName = File::name($path);
        $fileExtension = File::extension($path);

        // Temp files have meaningless extensions (.tmp) — derive from MIME type instead
        if ($mimeType && (empty($fileExtension) || $fileExtension === 'tmp')) {
            $fileExtension = Arr::first((new MimeTypes)->getExtensions($mimeType)) ?: $fileExtension;
        }

        return new UploadedFile($path, $fileName . '.' . $fileExtension, $mimeType, null, true);
    }

    public function getServerConfigMaxUploadFileSize(): float
    {
        $maxSize = $this->parseSize(@ini_get('post_max_size'));
        $uploadMax = $this->parseSize(@ini_get('upload_max_filesize'));

        if ($uploadMax > 0 && $uploadMax < $maxSize) {
            $maxSize = $uploadMax;
        }

        return $maxSize;
    }

    protected function parseSize(int|string $size): float
    {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
        $size = (int) preg_replace('/[^0-9\.]/', '', $size);

        if ($unit) {
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        }

        return round($size);
    }

    protected function getConfig(?string $key = null, mixed $default = null): mixed
    {
        $configs = config('filament-media.media') ?? config('media');

        if (! $key) {
            return $configs;
        }

        return Arr::get($configs, $key, $default);
    }
}
