<?php

namespace Codenzia\FilamentMedia\Services;

use Codenzia\FilamentMedia\Events\MediaFileUploaded;
use Codenzia\FilamentMedia\Helpers\BaseHelper;
use Codenzia\FilamentMedia\Http\Resources\FileResource;
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
 */
class UploadService
{
    public function __construct(
        protected StorageDriverService $storageDriver,
        protected MediaUrlService $urlService,
        protected ImageService $imageService,
        protected UploadsManager $uploadManager
    ) {}

    public function handleUpload(
        ?UploadedFile $fileUpload,
        int|string|null $folderId = 0,
        ?string $folderSlug = null,
        bool $skipValidation = false,
        string $visibility = 'public'
    ): array {
        $request = request();

        if ($uploadPath = $request->input('path')) {
            $folderId = $this->handleTargetFolder($folderId, $uploadPath);
        }

        if (! $fileUpload) {
            return $this->error(trans('filament-media::media.can_not_detect_file_type'));
        }

        if (! $this->isChunkUploadEnabled()) {
            $validationError = $this->validateUpload($fileUpload, $skipValidation);
            if ($validationError) {
                return $validationError;
            }
        }

        return $this->storeFile($fileUpload, $folderId, $folderSlug, $skipValidation, $visibility);
    }

    public function uploadFromUrl(
        string $url,
        int|string $folderId = 0,
        ?string $folderSlug = null,
        ?string $defaultMimetype = null
    ): ?array {
        if (empty($url)) {
            return $this->error(trans('filament-media::media.url_invalid'));
        }

        $ssrfError = $this->validateUrlForSsrf($url);
        if ($ssrfError) {
            return $this->error($ssrfError);
        }

        try {
            $response = Http::timeout(30)->get($url);

            if ($response->failed() || ! $response->body()) {
                return $this->error(
                    $response->reason() ?: trans('filament-media::media.unable_download_image_from', ['url' => $url])
                );
            }

            $contents = $response->body();
        } catch (Throwable $e) {
            logger()->error('Failed to download file from URL', ['url' => $url, 'error' => $e->getMessage()]);

            return $this->error($e->getMessage() ?: trans('filament-media::media.validation.upload_network_error'));
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'media_');
        if ($tempPath === false || file_put_contents($tempPath, $contents) === false) {
            @unlink($tempPath);

            return $this->error(trans('filament-media::media.unable_to_create_temp_file'));
        }

        $fileUpload = $this->newUploadedFile($tempPath, $defaultMimetype);
        $result = $this->handleUpload($fileUpload, $folderId, $folderSlug);

        @unlink($tempPath);

        return $result;
    }

    public function uploadFromPath(
        string $path,
        int|string $folderId = 0,
        ?string $folderSlug = null,
        ?string $defaultMimetype = null
    ): array {
        if (empty($path)) {
            return $this->error(trans('filament-media::media.path_invalid'));
        }

        $fileUpload = $this->newUploadedFile($path, $defaultMimetype);

        return $this->handleUpload($fileUpload, $folderId, $folderSlug);
    }

    public function uploadFromBlob(
        UploadedFile $path,
        ?string $fileName = null,
        int|string $folderId = 0,
        ?string $folderSlug = null,
    ): array {
        $fileUpload = new UploadedFile($path, $fileName ?: Str::uuid());

        return $this->handleUpload($fileUpload, $folderId, $folderSlug, true);
    }

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
        $result = $this->handleUpload($request->file($fileInput), $folderId, $folderName);

        if (! $result['error']) {
            $file = $result['data'];

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
        }

        $errorMessage = Arr::get($result, 'message', 'Upload failed');

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

            return null;
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

    protected function validateUpload(UploadedFile $fileUpload, bool $skipValidation): ?array
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
                return $this->error($validator->getMessageBag()->first());
            }
        }

        $maxUploadFilesizeAllowed = \setting('max_upload_filesize');
        if ($maxUploadFilesizeAllowed && ($fileUpload->getSize() / 1024) / 1024 > (float) $maxUploadFilesizeAllowed) {
            return $this->error(trans('filament-media::media.file_too_big_readable_size', [
                'size' => BaseHelper::humanFilesize($maxUploadFilesizeAllowed * 1024 * 1024),
            ]));
        }

        $maxSize = $this->getServerConfigMaxUploadFileSize();
        if ($fileUpload->getSize() / 1024 > (int) $maxSize) {
            return $this->error(trans('filament-media::media.file_too_big_readable_size', [
                'size' => BaseHelper::humanFilesize($maxSize * 1024),
            ]));
        }

        return null;
    }

    protected function storeFile(
        UploadedFile $fileUpload,
        int|string|null $folderId,
        ?string $folderSlug,
        bool $skipValidation,
        string $visibility
    ): array {
        $allowedMimeTypes = $this->getConfig('allowed_mime_types');

        try {
            $fileExtension = strtolower(
                $fileUpload->getClientOriginalExtension() ?: $fileUpload->guessExtension()
            );

            if (! $skipValidation && ! in_array($fileExtension, explode(',', $allowedMimeTypes))) {
                return $this->error(trans('filament-media::media.validation.uploaded_file_invalid_type'));
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

            return [
                'error' => false,
                'data' => new FileResource($file),
            ];
        } catch (UnableToWriteFile $e) {
            $message = $this->storageDriver->isUsingCloud()
                ? $e->getMessage()
                : trans('filament-media::media.unable_to_write', ['folder' => $this->storageDriver->getUploadPath()]);

            return $this->error($message);
        } catch (Throwable $e) {
            return $this->error($e->getMessage() ?: trans('filament-media::media.validation.upload_network_error'));
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

        $fileName = File::name($path);
        $fileExtension = File::extension($path);

        if (empty($fileExtension) && $mimeType) {
            $fileExtension = Arr::first((new MimeTypes)->getExtensions($mimeType));
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

    protected function error(string $message): array
    {
        return ['error' => true, 'message' => $message];
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
