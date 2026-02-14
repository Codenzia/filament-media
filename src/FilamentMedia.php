<?php

namespace Codenzia\FilamentMedia;


use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\HtmlString;
use Codenzia\FilamentMedia\Helpers\BaseHelper;
use Codenzia\FilamentMedia\Helpers\AdminHelper;
use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaFolder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Mime\MimeTypes;
use Throwable;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Encoders\AutoEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Codenzia\FilamentMedia\Http\Resources\FileResource;
use Codenzia\FilamentMedia\Events\MediaFileUploaded;
use Codenzia\FilamentMedia\Events\MediaFileRenaming;
use Codenzia\FilamentMedia\Events\MediaFileRenamed;
use Codenzia\FilamentMedia\Events\MediaFolderRenaming;
use Codenzia\FilamentMedia\Events\MediaFolderRenamed;
use League\Flysystem\UnableToWriteFile;
use Codenzia\FilamentMedia\Services\ThumbnailService;
use Codenzia\FilamentMedia\Services\UploadsManager;
use Illuminate\Validation\Rules\File as ValidationFile;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class FilamentMedia
{
    protected array $permissions = [];

    public function __construct(
        protected ThumbnailService $thumbnailService,
        protected UploadsManager $uploadManager
    ) {
    }



    public function renderHeader(): string
    {
        $urls = $this->getUrls();

        return view('filament-media::header', compact('urls'))->render();
    }

    public function getUrls(): array
    {
        return [
            'base_url' => url(''),
            'base' => route('media.index'),
            'get_media' => route('media.list'),
            'create_folder' => route('media.folders.create'),
            'popup' => route('media.popup'),
            'download' => route('media.download'),
            'upload_file' => route('media.files.upload'),
            'get_breadcrumbs' => route('media.breadcrumbs'),
            'global_actions' => route('media.global_actions'),
            'media_upload_from_editor' => route('media.files.upload.from.editor'),
            'download_url' => route('media.download_url'),
        ];
    }

    public function renderFooter(): string
    {
        return view('filament-media::footer')->render();
    }

    public function renderContent(): string
    {
        $sorts = $this->getSorts();

        return view('filament-media::content', compact('sorts'))->render();
    }

    public static function getSorts(): array
    {
        return [
            'name-asc' => [
                'label' => trans('filament-media::media.file_name_asc'),
                'icon' => 'heroicon-m-chevron-up-down',
            ],
            'name-desc' => [
                'label' => trans('filament-media::media.file_name_desc'),
                'icon' => 'heroicon-m-chevron-up-down',
            ],
            'created_at-asc' => [
                'label' => trans('filament-media::media.uploaded_date_asc'),
                'icon' => 'heroicon-m-chevron-up-down',
            ],
            'created_at-desc' => [
                'label' => trans('filament-media::media.uploaded_date_desc'),
                'icon' => 'heroicon-m-chevron-up-down',
            ],
            'size-asc' => [
                'label' => trans('filament-media::media.size_asc'),
                'icon' => 'heroicon-m-chevron-up-down',
            ],
            'size-desc' => [
                'label' => trans('filament-media::media.size_desc'),
                'icon' => 'heroicon-m-chevron-up-down',
            ],
        ];
    }

    public function responseSuccess(array $data, ?string $message = null): JsonResponse
    {
        return response()->json([
            'error' => false,
            'data' => $data,
            'message' => $message,
        ]);
    }

    public function responseError(
        string $message,
        array $data = [],
        ?int $code = null,
        int $status = 200
    ): JsonResponse {
        return response()->json([
            'error' => true,
            'message' => $message,
            'data' => $data,
            'code' => $code,
        ], $status);
    }

    public function getAllImageSizes(?string $url): array
    {
        $images = [];
        foreach ($this->getSizes() as $size) {
            $readableSize = explode('x', $size);
            $images = $this->getImageUrl($url, $readableSize);
        }

        return $images;
    }

    public function getSizes(): array
    {
        $sizes = $this->getConfig('sizes', []);

        foreach ($sizes as $name => $size) {
            $size = explode('x', $size);

            $settingName = 'media_sizes_' . $name;

            $width = \setting($settingName . '_width', $size[0]);

            $height = \setting($settingName . '_height', $size[1]);

            if (! $width && ! $height) {
                unset($sizes[$name]);

                continue;
            }

            if (! $width) {
                $width = 'auto';
            }

            if (! $height) {
                $height = 'auto';
            }

            $sizes[$name] = $width . 'x' . $height;
        }

        return $sizes;
    }

    public function getImageUrl(
        ?string $url,
        $size = null,
        bool $relativePath = false,
        $default = null
    ): ?string {
        if (empty($url)) {
            return $default;
        }

        $url = trim($url);

        if (empty($url)) {
            return $default;
        }

        if (Str::startsWith($url, ['data:image/png;base64,', 'data:image/jpeg;base64,', 'http://', 'https://'])) {
            return $url;
        }

        if (empty($size) || $url == '__value__') {
            if ($relativePath) {
                return $url;
            }

            return $this->url($url);
        }

        if ($url == $this->getDefaultImage(false, $size)) {
            return url($url);
        }

        if (
            \setting('media_enable_thumbnail_sizes', true) &&
            array_key_exists($size, $this->getSizes()) &&
            $this->canGenerateThumbnails($this->getMimeType($this->getRealPath($url)))
        ) {
            $fileName = File::name($url);
            $fileExtension = File::extension($url);

            $url = str_replace(
                $fileName . '.' . $fileExtension,
                $fileName . '-' . $this->getSize($size) . '.' . $fileExtension,
                $url
            );
        }

        if ($relativePath) {
            return $url;
        }

        if ($url == '__image__') {
            return $this->url($default);
        }

        return $this->url($url);
    }

    public function url(?string $path): string
    {
        $path = $path ? trim($path) : $path;

        if (Str::contains($path, ['http://', 'https://'])) {
            return $this->normalizeUrl($path);
        }

        if ($this->getMediaDriver() === 'do_spaces' && (int) \setting('media_do_spaces_cdn_enabled')) {
            $customDomain = \setting('media_do_spaces_cdn_custom_domain');

            if ($customDomain) {
                return $this->normalizeUrl(rtrim($customDomain, '/') . '/' . ltrim($path, '/'));
            }

            return $this->normalizeUrl(str_replace('.digitaloceanspaces.com', '.cdn.digitaloceanspaces.com', Storage::url($path)));
        } else {
            if ($this->getMediaDriver() === 'backblaze' && (int) \setting('media_backblaze_cdn_enabled')) {
                $customDomain = \setting('media_backblaze_cdn_custom_domain');
                $currentEndpoint = \setting('media_backblaze_endpoint');
                if ($customDomain) {
                    return $this->normalizeUrl(rtrim($customDomain, '/') . '/' . ltrim($path, '/'));
                }

                return $this->normalizeUrl(str_replace($currentEndpoint, $customDomain, Storage::url($path)));
            }
        }

        return $this->normalizeUrl(Storage::url($path));
    }

    /**
     * Normalize URL to fix common issues like double slashes (except after protocol).
     */
    protected function normalizeUrl(string $url): string
    {
        // Fix double slashes in path (but not in protocol)
        return preg_replace('#(?<!:)//+#', '/', $url);
    }

    public function getDefaultImage(bool $relative = false, ?string $size = null): string
    {
        $default = $this->getConfig('default_image');
//TODO: remove this?
//         if ($placeholder = setting('media_default_placeholder_image')) {
//             $filename = pathinfo($placeholder, PATHINFO_FILENAME);
//
//             if ($size && $size = $this->getSize($size)) {
//                 $placeholder = str_replace($filename, $filename . '-' . $size, $placeholder);
//             }
//
//             return Storage::url($placeholder);
//         }

        if ($relative) {
            return $default;
        }

        //TODO: it is safer to return null, but UI needs to handle this
        // Return null if no default configured - let the UI handle missing images
        //return $default ? url($default) : null;
        return $default ? url($default) : '';         
    }

    public function getSize(string $name): ?string
    {
        return Arr::get($this->getSizes(), $name);
    }

    public function deleteFile(MediaFile $file): bool
    {
        // Try to delete thumbnails (ignore errors if files don't exist)
        $this->deleteThumbnails($file);

        // If file doesn't exist on disk, that's okay - just return true
        // The database record will still be deleted
        if (! $this->isUsingCloud() && ! Storage::exists($file->url)) {
            return true;
        }

        try {
            return Storage::delete($file->url);
        } catch (\Throwable $e) {
            // Log but don't fail - the database record is already deleted
            logger()->warning('Failed to delete file from disk', [
                'file' => $file->url,
                'error' => $e->getMessage(),
            ]);
            return true;
        }
    }

    public function deleteThumbnails(MediaFile $file): bool
    {
        if (! $file->canGenerateThumbnails()) {
            return true;
        }

        $filename = pathinfo($file->url, PATHINFO_FILENAME);

        $files = [];
        foreach ($this->getSizes() as $size) {
            $files[] = str_replace($filename, $filename . '-' . $size, $file->url);
        }

        try {
            return Storage::delete($files);
        } catch (\Throwable $e) {
            // Ignore errors when deleting thumbnails - they may not exist
            return true;
        }
    }

    /**
     * Default permissions that are always included.
     * These ensure basic operations work even if config hasn't been updated.
     */
    protected array $defaultPermissions = [
        'folders.create',
        'folders.edit',
        'folders.trash',
        'folders.destroy',
        'folders.favorite',
        'files.create',
        'files.read',
        'files.edit',
        'files.trash',
        'files.destroy',
        'files.favorite',
        'settings.access',
    ];

    public function getPermissions(): array
    {
        $configPermissions = $this->permissions ?: $this->getConfig('permissions', []);

        // Merge with default permissions to ensure basic operations always work
        return array_unique(array_merge($this->defaultPermissions, $configPermissions));
    }

    public function setPermissions(array $permissions): void
    {
        $this->permissions = $permissions;
    }

    public function removePermission(string $permission): void
    {
        Arr::forget($this->permissions, $permission);
    }

    public function addPermission(string $permission): void
    {
        $this->permissions[] = $permission;
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->getPermissions());
    }

    public function hasAnyPermission(array $permissions): bool
    {
        $availablePermissions = $this->getPermissions();

        foreach ($permissions as $permission) {
            if (in_array($permission, $availablePermissions)) {
                return true;
            }
        }

        return false;
    }

    public function addSize(string $name, int|string $width, int|string $height = 'auto'): self
    {
        if (! $width) {
            $width = 'auto';
        }

        if (! $height) {
            $height = 'auto';
        }

        config(['core.media.media.sizes.' . $name => $width . 'x' . $height]);

        return $this;
    }

    public function removeSize(string $name): self
    {
        $sizes = $this->getSizes();
        Arr::forget($sizes, $name);

        config(['core.media.media.sizes' => $sizes]);

        return $this;
    }

    public function uploadFromEditor(
        Request $request,
        int|string|null $folderId = 0,
        $folderName = null,
        string $fileInput = 'upload'
    ) {
        $validator = Validator::make($request->all(), [
            'upload' => $this->imageValidationRule(),
        ], [
            'upload.required' => trans('filament-media::media.validation.uploaded_file_required'),
            'upload.image' => trans('filament-media::media.validation.uploaded_file_invalid_type'),
            'upload.mimes' => trans('filament-media::media.validation.uploaded_file_invalid_type'),
        ], [
            'upload' => trans('filament-media::media.validation.attributes.uploaded_file'),
        ]);

        if ($validator->fails()) {
            $errorMessage = $validator->getMessageBag()->first();

            // Return JSON for modern editors, fallback to safe script for CKEditor
            if (!$request->input('CKEditorFuncNum')) {
                return response()->json([
                    'uploaded' => 0,
                    'error' => ['message' => $errorMessage],
                ], 422);
            }

            // For CKEditor, use proper escaping
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
                    'fileName' => File::name($this->url($file->url)),
                    'uploaded' => 1,
                    'url' => $this->url($file->url),
                ]);
            }

            return response(
                '<script>window.parent.CKEDITOR.tools.callFunction("' . $request->input('CKEditorFuncNum') .
                '", "' . $this->url($file->url) . '", "");</script>'
            )
                ->header('Content-Type', 'text/html');
        }

        // Return JSON for modern editors, fallback to safe script for CKEditor
        $errorMessage = Arr::get($result, 'message', 'Upload failed');
        if (!$request->input('CKEditorFuncNum')) {
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

    protected function getCustomS3Path(): string
    {
        $customPath = trim(\setting('media_s3_path', $this->getConfig('custom_s3_path')), '/');
        $customPath = \apply_filters('core_media_custom_s3_path', $customPath);

        return $customPath ? $customPath . '/' : '';
    }

    public function handleUpload(
        ?UploadedFile $fileUpload,
        int|string|null $folderId = 0,
        ?string $folderSlug = null,
        bool $skipValidation = false,
        string $visibility = 'public'
    ): array {
        $fileUpload = \apply_filters('core_media_file_upload', $fileUpload);

        $request = request();

        if ($uploadPath = $request->input('path')) {
            $folderId = $this->handleTargetFolder($folderId, $uploadPath);
        }

        if (! $fileUpload) {
            return [
                'error' => true,
                'message' => trans('filament-media::media.can_not_detect_file_type'),
            ];
        }

        $allowedMimeTypes = $this->getConfig('allowed_mime_types');


        if (! $this->isChunkUploadEnabled()) {
            if (! $skipValidation) {
                $rules = ['required'];

                $validator = Validator::make(['uploaded_file' => $fileUpload], [
                    'uploaded_file' => $rules,
                ], [
                    'uploaded_file.required' => trans('filament-media::media.validation.uploaded_file_required'),
                    'uploaded_file.file' => trans('filament-media::media.validation.uploaded_file_invalid_type'),
                    'uploaded_file.types' => trans('filament-media::media.validation.uploaded_file_invalid_type'),
                ], [
                    'uploaded_file' => trans('filament-media::media.validation.attributes.uploaded_file'),
                ]);

                if ($validator->fails()) {
                    return [
                        'error' => true,
                        'message' => $validator->getMessageBag()->first(),
                    ];
                }
            }

            $maxUploadFilesizeAllowed = \setting('max_upload_filesize');

            if (
                $maxUploadFilesizeAllowed
                && ($fileUpload->getSize() / 1024) / 1024 > (float) $maxUploadFilesizeAllowed
            ) {
                return [
                    'error' => true,
                    'message' => trans('filament-media::media.file_too_big_readable_size', [
                        'size' => BaseHelper::humanFilesize($maxUploadFilesizeAllowed * 1024 * 1024),
                    ]),
                ];
            }

            $maxSize = $this->getServerConfigMaxUploadFileSize();

            if ($fileUpload->getSize() / 1024 > (int) $maxSize) {
                return [
                    'error' => true,
                    'message' => trans('filament-media::media.file_too_big_readable_size', [
                        'size' => BaseHelper::humanFilesize($maxSize * 1024),
                    ]),
                ];
            }
        }

        $extraValidation = \apply_filters('core_media_extra_validation', [], $fileUpload);

        if ($extraValidation && Arr::get($extraValidation, 'error')) {
            return [
                'error' => true,
                'message' => $extraValidation['message'],
            ];
        }

        try {
            $fileExtension = $fileUpload->getClientOriginalExtension() ?: $fileUpload->guessExtension();

            $fileExtension = strtolower($fileExtension);
            if (
                ! $skipValidation
                && ! in_array(strtolower($fileExtension), explode(',', $allowedMimeTypes))
            ) {
                return [
                    'error' => true,
                    'message' => trans('filament-media::media.validation.uploaded_file_invalid_type'),
                ];
            }

            if ($folderId == 0 && ! empty($folderSlug)) {
                if (str_contains($folderSlug, '/')) {
                    $paths = array_filter(explode('/', $folderSlug));
                    foreach ($paths as $folder) {
                        $folderId = $this->createFolder($folder, $folderId, true);
                    }
                } else {
                    $folderId = $this->createFolder($folderSlug, $folderId, true);
                }
            }

            $file = new MediaFile();

            $fileName = \apply_filters(
                'core_media_upload_filename',
                File::name($fileUpload->getClientOriginalName()),
                $fileUpload
            );

            $file->name = MediaFile::createName($fileName, $folderId);

            $folderPath = MediaFolder::getFullPath($folderId);

            $fileName = MediaFile::createSlug(
                $file->name,
                $fileExtension,
                $folderPath ?: ''
            );

            $filePath = $fileName;

            if ($folderPath) {
                $filePath = $folderPath . '/' . $filePath;
            }

            if ($this->getMediaDriver() === 's3') {
                $filePath = $this->getCustomS3Path() . $filePath;
            }

            // Read file content - use Livewire's storage-aware method for TemporaryUploadedFile
            // This skips Intervention Image processing during upload for reliability
            // Image resizing/conversion happens during thumbnail generation instead
            if ($fileUpload instanceof TemporaryUploadedFile) {
                $content = $fileUpload->get();
            } else {
                $content = File::get($fileUpload->getRealPath());
            }

            $this->uploadManager->saveFile($filePath, $content, $fileUpload, $visibility);

            $data = $this->uploadManager->fileDetails($filePath);

            $file->url = $data['url'];
            $file->alt = $file->name;
            $file->size = $data['size'] ?: $fileUpload->getSize();

            $file->mime_type = $data['mime_type'];
            $file->folder_id = $folderId;
            $file->user_id = Auth::guard()->check() ? Auth::guard()->id() : 0;
            $file->options = $request->input('options', []);

            $file->visibility = $visibility;

            $file->save();

            MediaFileUploaded::dispatch($file);

            \do_action('core_media_file_uploaded', $file);

            $customizedGeneratedThumbnails = \apply_filters('core_media_customized_generate_thumbnails_function', null);

            if (! $customizedGeneratedThumbnails) {
                $this->generateThumbnails($file, $fileUpload);
            }

            return [
                'error' => false,
                'data' => new FileResource($file),
            ];
        } catch (UnableToWriteFile $exception) {
            $message = $exception->getMessage();

            if (! $this->isUsingCloud()) {
                $message = trans('filament-media::media.unable_to_write', ['folder' => $this->getUploadPath()]);
            }

            return [
                'error' => true,
                'message' => $message,
            ];
        } catch (Throwable $exception) {
            return [
                'error' => true,
                'message' => $exception->getMessage() ?: trans('filament-media::media.validation.upload_network_error'),
            ];
        }
    }

    /**
     * Returns a file size limit in bytes based on the PHP upload_max_filesize and post_max_size
     */
    public function getServerConfigMaxUploadFileSize(): float
    {
        // Start with post_max_size.
        $maxSize = $this->parseSize(@ini_get('post_max_size'));

        // If upload_max_size is less, then reduce. Except if upload_max_size is
        // zero, which indicates no limit.
        $uploadMax = $this->parseSize(@ini_get('upload_max_filesize'));
        if ($uploadMax > 0 && $uploadMax < $maxSize) {
            $maxSize = $uploadMax;
        }

        return $maxSize;
    }

    public function parseSize(int|string $size): float
    {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
        $size = (int) preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
        if ($unit) {
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        }

        return round($size);
    }

    public function generateThumbnails(MediaFile $file, ?UploadedFile $fileUpload = null): bool
    {
        if (! $file->canGenerateThumbnails()) {
            return false;
        }

        if (! $this->isUsingCloud() && ! File::exists($this->getRealPath($file->url))) {
            return false;
        }

        $folderIds = json_decode(\setting('media_folders_can_add_watermark', ''), true);

        if (
            empty($folderIds) ||
            in_array($file->folder_id, $folderIds) ||
            ! empty(array_intersect($file->folder->parents->pluck('id')->all(), $folderIds))
        ) {
            $this->insertWatermark($file->url);
        }

        if (! \setting('media_enable_thumbnail_sizes', true)) {
            return false;
        }

        foreach ($this->getSizes() as $size) {
            $readableSize = explode('x', $size);

            if (! $fileUpload || $this->isChunkUploadEnabled()) {
                $fileUpload = $this->getRealPath($file->url);

                if ($this->isUsingCloud()) {
                    $fileUpload = @file_get_contents($fileUpload);

                    if (! $fileUpload) {
                        continue;
                    }
                }
            }

            $thumbnailFileName = File::name($file->url) . '-' . $size . '.' . File::extension($file->url);
            $dirName = File::dirname($file->url);
            $thumbnailPath = ($dirName === '.' || ! $dirName) ? $thumbnailFileName : $dirName . '/' . $thumbnailFileName;

            if (! $this->isUsingCloud() && Storage::exists($thumbnailPath)) {
                continue;
            }

            // Get the full destination path for local storage
            // For local storage, we need the full filesystem path
            // For cloud storage, we use the relative path within the bucket
            if ($this->isUsingCloud()) {
                $destinationPath = ($dirName === '.' || ! $dirName) ? '' : $dirName;
            } else {
                // Get the storage disk root path and append the directory
                $disk = $this->getMediaDriver();
                $storagePath = Storage::disk($disk)->path('');
                $destinationPath = ($dirName === '.' || ! $dirName)
                    ? rtrim($storagePath, '/\\')
                    : $storagePath . $dirName;
            }

            $this->thumbnailService
                ->setImage($fileUpload)
                ->setSize($readableSize[0], $readableSize[1])
                ->setDestinationPath($destinationPath)
                ->setFileName($thumbnailFileName)
                ->save();
        }

        return true;
    }

    public function insertWatermark(string $image): bool
    {
        if (! $image || ! \setting('media_watermark_enabled', $this->getConfig('watermark.enabled'))) {
            return false;
        }

        $watermarkImage = \setting('media_watermark_source', $this->getConfig('watermark.source'));

        if (! $watermarkImage) {
            return false;
        }

        $watermarkPath = $this->getRealPath($watermarkImage);

        if ($this->isUsingCloud()) {
            $watermark = $this->imageManager()->read(file_get_contents($watermarkPath));

            $imageSource = $this->imageManager()->read(file_get_contents($this->getRealPath($image)));
        } else {
            if (! File::exists($watermarkPath)) {
                return false;
            }

            $watermark = $this->imageManager()->read($watermarkPath);

            $imageSource = $this->imageManager()->read($this->getRealPath($image));
        }

        // 10% less than an actual image (play with this value)
        // Watermark will be 10 less than the actual width of the image
        $watermarkSize = (int) round(
            $imageSource->width() * ((int) \setting(
                'media_watermark_size',
                $this->getConfig('watermark.size')
            ) / 100),
            2
        );

        // Resize watermark width keep height auto
        $watermark->scale($watermarkSize);

        $imageSource->place(
            $watermark,
            \setting('media_watermark_position', $this->getConfig('watermark.position')),
            (int) \setting(
                'media_watermark_position_x',
                \setting('watermark_position_x') ?: $this->getConfig('watermark.x')
            ),
            (int) \setting(
                'media_watermark_position_y',
                \setting('watermark_position_y') ?: $this->getConfig('watermark.y')
            ),
            (int) \setting(
                'media_watermark_opacity',
                \setting('watermark_opacity') ?: $this->getConfig('watermark.opacity')
            )
        );

        $destinationPath = sprintf(
            '%s/%s',
            trim(File::dirname($image), '/'),
            File::name($image) . '.' . File::extension($image)
        );

        $this->uploadManager->saveFile($destinationPath, $imageSource->encode(new AutoEncoder()));

        return true;
    }

    public function getRealPath(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        try {
            $disk = $this->getMediaDriver();

            $path = $this->isUsingCloud()
                ? Storage::disk($disk)->url($url)
                : Storage::disk($disk)->path($url);

            return Arr::first(explode('?v=', $path));
        } catch (Throwable $exception) {
            logger()->error('Failed to get real path: ' . $exception->getMessage(), [
                'url' => $url,
                'exception' => $exception,
            ]);

            return null;
        }
    }

    public function isImage(string $mimeType): bool
    {
        return Str::startsWith($mimeType, 'image/');
    }

    public function isUsingCloud(): bool
    {
        return ! in_array($this->getMediaDriver(), ['local', 'public']);
    }

    public function uploadFromUrl(
        string $url,
        int|string $folderId = 0,
        ?string $folderSlug = null,
        ?string $defaultMimetype = null
    ): ?array {
        if (empty($url)) {
            return [
                'error' => true,
                'message' => trans('filament-media::media.url_invalid'),
            ];
        }

        // SSRF Protection: Validate URL against allowed domains and block internal addresses
        $ssrfError = $this->validateUrlForSsrf($url);
        if ($ssrfError) {
            return [
                'error' => true,
                'message' => $ssrfError,
            ];
        }

        $info = pathinfo($url);

        try {
            // Use secure HTTP client with SSL verification enabled
            $response = Http::timeout(30)->get($url);

            if ($response->failed() || ! $response->body()) {
                return [
                    'error' => true,
                    'message' => $response->reason() ?: trans(
                        'filament-media::media.unable_download_image_from',
                        ['url' => $url]
                    ),
                ];
            }

            $contents = $response->body();
        } catch (Throwable $exception) {
            logger()->error('Failed to download file from URL: ' . $exception->getMessage(), [
                'url' => $url,
                'exception' => $exception,
            ]);

            return [
                'error' => true,
                'message' => $exception->getMessage() ?: trans('filament-media::media.validation.upload_network_error'),
            ];
        }

        // Use secure temp file creation to prevent path traversal attacks
        $tempDir = sys_get_temp_dir();

        // Sanitize filename - only allow alphanumeric, dots, underscores, and hyphens
        $safeBasename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $info['basename'] ?? 'download');
        $safeBasename = Str::limit($safeBasename, 50, '');

        // Create unique temp file path
        $tempPath = tempnam($tempDir, 'media_');
        if ($tempPath === false) {
            return [
                'error' => true,
                'message' => trans('filament-media::media.unable_to_create_temp_file'),
            ];
        }

        // Write contents to temp file
        if (file_put_contents($tempPath, $contents) === false) {
            @unlink($tempPath);
            return [
                'error' => true,
                'message' => trans('filament-media::media.unable_to_write_temp_file'),
            ];
        }

        $fileUpload = $this->newUploadedFile($tempPath, $defaultMimetype);

        $result = $this->handleUpload($fileUpload, $folderId, $folderSlug);

        // Always clean up temp file
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
            return [
                'error' => true,
                'message' => trans('filament-media::media.path_invalid'),
            ];
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

    protected function newUploadedFile(string $path, ?string $defaultMimeType = null): UploadedFile
    {
        $mimeType = $this->getMimeType($path);

        if (empty($mimeType)) {
            $mimeType = $defaultMimeType;
        }

        $fileName = File::name($path);
        $fileExtension = File::extension($path);

        if (empty($fileExtension) && $mimeType) {
            $mimeTypeDetection = (new MimeTypes())->getExtensions($mimeType);

            $fileExtension = Arr::first($mimeTypeDetection);
        }

        return new UploadedFile($path, $fileName . '.' . $fileExtension, $mimeType, null, true);
    }

    public function getUploadPath(): string
    {
        $customFolder = $this->getConfig('default_upload_folder');

        if (\setting('media_customize_upload_path')) {
            $customFolder = trim(\setting('media_upload_path'), '/');
        }

        if ($customFolder) {
            return public_path($customFolder);
        }

        return is_link(public_path('storage')) ? storage_path('app/public') : public_path('storage');
    }

    public function getUploadURL(): string
    {
        $uploadUrl = $this->getConfig('default_upload_url') ?: asset('storage');

        if (\setting('media_customize_upload_path')) {
            $uploadUrl = trim(asset(\setting('media_upload_path')), '/');
        }

        return str_replace('/index.php', '', $uploadUrl);
    }

    public function setUploadPathAndURLToPublic(): static
    {
        \add_action('init', function (): void {
            config([
                'filesystems.disks.public.root' => $this->getUploadPath(),
                'filesystems.disks.public.url' => $this->getUploadURL(),
            ]);
        }, 124);

        return $this;
    }

    public function getMimeType(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        try {
            // For remote URLs (like S3), determine MIME type from extension
            if (Str::contains($url, ['http://', 'https://'])) {
                $fileExtension = pathinfo($url, PATHINFO_EXTENSION);

                if (! $fileExtension) {
                    $realPath = $this->getRealPath($url);

                    if (empty($realPath)) {
                        return null;
                    }

                    $fileExtension = File::extension($realPath);
                }

                if (! $fileExtension) {
                    return null;
                }

                if ($fileExtension == 'jfif') {
                    return 'image/jpeg';
                }

                $mimeType = match (strtolower($fileExtension)) {
                    'ico' => 'image/x-icon',
                    'png' => 'image/png',
                    'jpg', 'jpeg' => 'image/jpeg',
                    'gif' => 'image/gif',
                    'svg' => 'image/svg+xml',
                    'webp' => 'image/webp',
                    'pdf' => 'application/pdf',
                    'doc' => 'application/msword',
                    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'xls' => 'application/vnd.ms-excel',
                    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'ppt' => 'application/vnd.ms-powerpoint',
                    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                    'mp3' => 'audio/mpeg',
                    'mp4' => 'video/mp4',
                    'zip' => 'application/zip',
                    'rar' => 'application/x-rar-compressed',
                    'txt' => 'text/plain',
                    'csv' => 'text/csv',
                    default => null,
                };

                if (! $mimeType) {
                    $mimeTypeDetection = new MimeTypes();

                    return Arr::first($mimeTypeDetection->getMimeTypes($fileExtension));
                }

                return $mimeType;
            }

            // For local files, use the existing method
            $realPath = $this->getRealPath($url);

            if (empty($realPath)) {
                return null;
            }

            $fileExtension = File::extension($realPath);

            if (! $fileExtension) {
                return null;
            }

            if ($fileExtension == 'jfif') {
                return 'image/jpeg';
            }

            $mimeTypeDetection = new MimeTypes();

            return Arr::first($mimeTypeDetection->getMimeTypes($fileExtension));
        } catch (Throwable $exception) {
            logger()->error('Failed to get MIME type: ' . $exception->getMessage(), [
                'url' => $url,
                'exception' => $exception,
            ]);

            return null;
        }
    }

    public function canGenerateThumbnails(?string $mimeType): bool
    {
        if (! $this->getConfig('generate_thumbnails_enabled')) {
            return false;
        }

        if (! $mimeType) {
            return false;
        }

        return $this->isImage($mimeType) && ! in_array($mimeType, ['image/svg+xml', 'image/x-icon']);
    }

    public function createFolder(string $folderSlug, int|string|null $parentId = 0, bool $force = false): int|string
    {
        $folder = MediaFolder::query()
            ->where([
                'slug' => $folderSlug,
                'parent_id' => $parentId,
            ])
            ->first();

        if (! $folder) {
            if ($force) {
                MediaFolder::query()
                    ->where([
                        'slug' => $folderSlug,
                        'parent_id' => $parentId,
                    ])
                    ->each(fn (MediaFolder $folder) => $folder->forceDelete());
            }

            $folder = MediaFolder::query()->create([
                'user_id' => Auth::guard()->check() ? Auth::guard()->id() : 0,
                'name' => MediaFolder::createName($folderSlug, $parentId),
                'slug' => MediaFolder::createSlug($folderSlug, $parentId),
                'parent_id' => $parentId,
            ]);
        }

        return $folder->id;
    }

    public function handleTargetFolder(int|string|null $folderId = 0, string $filePath = ''): string
    {
        if (str_contains($filePath, '/')) {
            $paths = array_filter(explode('/', $filePath));
            array_pop($paths);
            foreach ($paths as $folder) {
                $folderId = $this->createFolder($folder, $folderId, true);
            }
        }

        return $folderId;
    }

    public function isChunkUploadEnabled(): bool
    {
        return (bool) \setting('media_chunk_enabled', (int) $this->getConfig('chunk.enabled') == 1);
    }

    public function getConfig(?string $key = null, bool|string|null|array $default = null)
    {
        $configs = config('filament-media.media') ?? config('media');

        if (! $key) {
            return $configs;
        }

        return Arr::get($configs, $key, $default);
    }

    public function imageValidationRule(): string
    {
        return 'required|image|mimes:jpg,jpeg,png,webp,gif,bmp';
    }

    public function turnOffAutomaticUrlTranslationIntoLatin(): bool
    {
        return (int) \setting('media_turn_off_automatic_url_translation_into_latin', 0) == 1;
    }

    public function getImageProcessingLibrary(): string
    {
        return \setting('media_image_processing_library') ?: 'gd';
    }

    public function getMediaDriver(): string
    {
        return $this->getConfig('disk', 'public');
    }

    public function setS3Disk(array $config): void
    {
        if (
            ! $config['key'] ||
            ! $config['secret'] ||
            ! $config['region'] ||
            ! $config['bucket'] ||
            ! $config['url']
        ) {
            return;
        }

        config()->set([
            'filesystems.disks.s3' => [
                'driver' => 's3',
                'visibility' => 'public',
                'throw' => true,
                'key' => $config['key'],
                'secret' => $config['secret'],
                'region' => $config['region'],
                'bucket' => $config['bucket'],
                'url' => $config['url'],
                'endpoint' => $config['endpoint'],
                'use_path_style_endpoint' => (bool) $config['use_path_style_endpoint'],
            ],
        ]);
    }

    public function setR2Disk(array $config): void
    {
        if (
            ! $config['key'] ||
            ! $config['secret'] ||
            ! $config['bucket'] ||
            ! $config['endpoint']
        ) {
            return;
        }

        config()->set([
            'filesystems.disks.r2' => [
                'driver' => 's3',
                'visibility' => 'public',
                'throw' => true,
                'key' => $config['key'],
                'secret' => $config['secret'],
                'region' => 'auto',
                'bucket' => $config['bucket'],
                'url' => $config['url'],
                'endpoint' => $config['endpoint'],
                'use_path_style_endpoint' => (bool) $config['use_path_style_endpoint'],
            ],
        ]);
    }

    public function setDoSpacesDisk(array $config): void
    {
        if (
            ! $config['key'] ||
            ! $config['secret'] ||
            ! $config['region'] ||
            ! $config['bucket'] ||
            ! $config['endpoint']
        ) {
            return;
        }

        config()->set([
            'filesystems.disks.do_spaces' => [
                'driver' => 's3',
                'visibility' => 'public',
                'throw' => true,
                'key' => $config['key'],
                'secret' => $config['secret'],
                'region' => $config['region'],
                'bucket' => $config['bucket'],
                'endpoint' => $config['endpoint'],
                'use_path_style_endpoint' => (bool) $config['use_path_style_endpoint'],
            ],
        ]);
    }

    public function setWasabiDisk(array $config): void
    {
        if (
            ! $config['key'] ||
            ! $config['secret'] ||
            ! $config['region'] ||
            ! $config['bucket']
        ) {
            return;
        }

        config()->set([
            'filesystems.disks.wasabi' => [
                'driver' => 'wasabi',
                'visibility' => 'public',
                'throw' => true,
                'key' => $config['key'],
                'secret' => $config['secret'],
                'region' => $config['region'],
                'bucket' => $config['bucket'],
                'root' => $config['root'] ?: '/',
            ],
        ]);
    }

    public function setBunnyCdnDisk(array $config): void
    {
        if (
            ! $config['hostname'] ||
            ! $config['storage_zone'] ||
            ! $config['api_key']
        ) {
            return;
        }

        config()->set([
            'filesystems.disks.bunnycdn' => [
                'driver' => 'bunnycdn',
                'visibility' => 'public',
                'throw' => true,
                'hostname' => $config['hostname'],
                'storage_zone' => $config['storage_zone'],
                'api_key' => $config['api_key'],
                'region' => $config['region'],
            ],
        ]);
    }

    public function setBackblazeDisk(array $config): void
    {
        if (
            ! $config['key'] ||
            ! $config['secret'] ||
            ! $config['region'] ||
            ! $config['bucket'] ||
            ! $config['endpoint']
        ) {
            return;
        }

        config()->set([
            'filesystems.disks.backblaze' => [
                'driver' => 's3',
                'visibility' => 'public',
                'throw' => true,
                'key' => $config['key'],
                'secret' => $config['secret'],
                'region' => $config['region'],
                'bucket' => $config['bucket'],
                'url' => $config['url'],
                'endpoint' => str_starts_with(
                    $config['endpoint'],
                    'https://'
                ) ? $config['endpoint'] : 'https://' . $config['endpoint'],
                'use_path_style_endpoint' => (bool) $config['use_path_style_endpoint'],
                'options' => [
                    'StorageClass' => 'STANDARD',
                ],
                'request_checksum_calculation' => 'when_required',
                'response_checksum_validation' => 'when_required',
            ],
        ]);
    }

    public function image(
        ?string $url,
        ?string $alt = null,
        ?string $size = null,
        bool $useDefaultImage = true,
        array $attributes = [],
        ?bool $secure = null,
        ?bool $lazy = true
    ): HtmlString {
        if (! isset($attributes['loading'])) {
            $attributes['loading'] = 'lazy';
        }

        $defaultImageUrl = $this->getDefaultImage(false, $size);

        if (! $url) {
            $url = $defaultImageUrl;
        }

        $url = $this->getImageUrl($url, $size, false, $useDefaultImage ? $defaultImageUrl : null);

        if ($alt) {
            $alt = BaseHelper::clean(strip_tags($alt));
        }

        $attributes = [
            'data-bb-lazy' => $lazy ? 'true' : 'false',
            ...$attributes,
        ];

        if (Str::startsWith($url, ['data:image/png;base64,', 'data:image/jpeg;base64,', 'data:image/jpg;base64,'])) {
            return Html::tag('img', '', [...$attributes, 'src' => $url, 'alt' => $alt]);
        }

        return \apply_filters(
            'core_media_image',
            Html::image($url, $alt, $attributes, $secure),
            $url,
            $alt,
            $attributes,
            $secure
        );
    }

    public function getFileSize(?string $path): ?string
    {
        try {
            if (! $path || (! $this->isUsingCloud() && ! Storage::exists($path))) {
                return null;
            }

            $size = Storage::size($path);

            if ($size == 0) {
                return '0kB';
            }
        } catch (Throwable) {
            return null;
        }

        return BaseHelper::humanFilesize($size);
    }

    public function renameFile(MediaFile $file, string $newName, bool $renameOnDisk = true): void
    {
        MediaFileRenaming::dispatch($file, $newName, $renameOnDisk);

        $file->name = MediaFile::createName($newName, $file->folder_id);

        if ($renameOnDisk) {
            $filePath = $this->getRealPath($file->url);

            if (File::exists($filePath)) {
                $newFilePath = str_replace(
                    File::name($file->url),
                    File::name($file->name),
                    $file->url
                );

                File::move($filePath, $this->getRealPath($newFilePath));

                $this->deleteFile($file);

                $file->url = str_replace(
                    File::name($file->url),
                    File::name($file->name),
                    $file->url
                );

                $this->generateThumbnails($file);
            }
        }

        $file->save();

        MediaFileRenamed::dispatch($file);
    }

    public function renameFolder(MediaFolder $folder, string $newName, bool $renameOnDisk = true): void
    {
        MediaFolderRenaming::dispatch($folder, $newName, $renameOnDisk);

        $folder->name = MediaFolder::createName($newName, $folder->parent_id);

        if ($renameOnDisk) {
            $folderPath = MediaFolder::getFullPath($folder->id);

            if (Storage::exists($folderPath)) {
                $newFolderName = MediaFolder::createSlug($newName, $folder->parent_id);

                $newFolderPath = str_replace(
                    File::name($folderPath),
                    $newFolderName,
                    $folderPath
                );

                Storage::move($folderPath, $newFolderPath);

                $folder->slug = $newFolderName;

                $folderPath = "$folderPath/";

                MediaFile::query()
                    ->where('url', 'LIKE', "$folderPath%")
                    ->update([
                        'url' => DB::raw(
                            sprintf(
                                'CONCAT(%s, SUBSTRING(url, LOCATE(%s, url) + LENGTH(%s)))',
                                DB::escape("$newFolderPath/"),
                                DB::escape($folderPath),
                                DB::escape($folderPath)
                            )
                        ),
                    ]);
            }
        }

        $folder->save();

        MediaFolderRenamed::dispatch($folder);
    }

    public function refreshCache(): void
    {
        \setting(['media_random_hash' => bin2hex(random_bytes(16))])->save();
    }

    public function getFolderColors(): array
    {
        return $this->getConfig('folder_colors', []);
    }

    public function imageManager(?string $driver = null): ImageManager
    {
        // Use static factory methods for Intervention Image v3
        if ($this->getImageProcessingLibrary() === 'imagick' && extension_loaded('imagick')) {
            return ImageManager::imagick();
        }

        return ImageManager::gd();
    }

    public function canOnlyViewOwnMedia(): bool
    {
        return false;
    }

    /**
     * Get the maximum upload file size in bytes.
     */
    public function getMaxSize(): int
    {
        // Check setting first (stored in bytes in database)
        $maxUploadFilesizeAllowed = \setting('media_max_file_size');
        if ($maxUploadFilesizeAllowed) {
            return (int) $maxUploadFilesizeAllowed;
        }

        // Fall back to server config (parseSize already returns bytes)
        return (int) $this->getServerConfigMaxUploadFileSize();
    }

    /**
     * Get the human-readable maximum upload file size.
     */
    public function getMaxSizeForHumans(): string
    {
        return BaseHelper::humanFilesize($this->getMaxSize());
    }

    /**
     * Get allowed MIME types as an array.
     */
    public function getAllowedMimeTypes(): array
    {
        $allowedMimeTypes = $this->getConfig('allowed_mime_types', '');

        if (empty($allowedMimeTypes)) {
            return [];
        }

        // The config stores file extensions, not MIME types
        $extensions = array_map('trim', explode(',', $allowedMimeTypes));

        return $extensions;
    }

    /**
     * Get allowed MIME types as a comma-separated string.
     */
    public function getAllowedMimeTypesString(): string
    {
        return $this->getConfig('allowed_mime_types', '');
    }

    public function responseDownloadFile(string $filePath)
    {
        $filePath = $this->getRealPath($filePath);
        $fileName = File::basename($filePath);

        if (! $this->isUsingCloud()) {
            if (! File::exists($filePath)) {
                return $this->responseError(trans('filament-media::media.file_not_exists'));
            }
            return response()->download($filePath, $fileName);
        }

        return response()->make(Http::timeout(30)->get($filePath)->body(), 200, [
            'Content-type' => $this->getMimeType($filePath),
            'Content-Disposition' => sprintf('attachment; filename="%s"', $fileName),
        ]);
    }

    public function getAvailableDrivers(): array
    {
        return \apply_filters('core_media_drivers', [
            'public' => trans('core/setting::setting.media.local_disk'),
            's3' => 'Amazon S3',
            'r2' => 'Cloudflare R2',
            'do_spaces' => 'DigitalOcean Spaces',
            'wasabi' => 'Wasabi',
            'bunnycdn' => 'BunnyCDN',
            'backblaze' => 'Backblaze B2',
        ]);
    }

    public function getList($request)
    {
        $folderId = $request->input('folder_id', 0);
        $search = $request->input('search');

        $folders = MediaFolder::query()
            ->where('parent_id', $folderId)
            ->when($search, fn($query) => $query->where('name', 'LIKE', "%$search%"))
            ->orderBy('name', 'asc')
            ->get();

        $files = MediaFile::query()
            ->where('folder_id', $folderId)
            ->when($search, fn($query) => $query->where('name', 'LIKE', "%$search%"))
            ->orderBy('name', 'asc')
            ->get();

        return $this->responseSuccess([
            'folders' => $folders,
            'files' => $files,
            'breadcrumbs' => $this->getBreadcrumbsData($folderId),
        ]);
    }

    protected function getBreadcrumbsData($folderId)
    {
        $breadcrumbs = [
            [
                'id' => 0,
                'name' => trans('filament-media::media.all_media'),
                'icon' => 'heroicon-m-folder',
            ]
        ];

        if ($folderId) {
            $folder = MediaFolder::find($folderId);
            if ($folder) {
                $parents = $folder->parents;
                foreach ($parents->reverse() as $parent) {
                    $breadcrumbs[] = [
                        'id' => $parent->id,
                        'name' => $parent->name,
                    ];
                }
                $breadcrumbs[] = [
                    'id' => $folder->id,
                    'name' => $folder->name,
                ];
            }
        }

        return $breadcrumbs;
    }

    public function postCreateFolder($request)
    {
        $name = $request->input('name');
        $parentId = $request->input('parent_id', 0);

        $folderId = $this->createFolder($name, $parentId);

        return $this->responseSuccess(['id' => $folderId], trans('filament-media::media.folder_created'));
    }

    public function postUploadFile($request)
    {
        $result = $this->handleUpload($request->file('file'), $request->input('folder_id', 0));

        if ($result['error']) {
            return $this->responseError($result['message']);
        }

        return $this->responseSuccess($result['data']->toArray());
    }

    public function postGlobalActions($request)
    {
        $action = $request->input('action');
        $selected = $request->input('selected', []);

        switch ($action) {
            case 'delete':
                foreach ($selected as $item) {
                    if ($item['is_folder']) {
                        MediaFolder::where('id', $item['id'])->delete();
                    } else {
                        MediaFile::where('id', $item['id'])->delete();
                    }
                }
                return $this->responseSuccess([], trans('filament-media::media.delete_success'));
        }

        return $this->responseError(trans('filament-media::media.invalid_action'));
    }

    public function postDownloadUrl($request)
    {
        $urls = $request->input('urls');
        $urls = explode("\n", $urls);
        $folderId = $request->input('folder_id', 0);

        foreach ($urls as $url) {
            $url = trim($url);
            if ($url) {
                $this->uploadFromUrl($url, $folderId);
            }
        }

        return $this->responseSuccess([], trans('filament-media::media.add_success'));
    }

    public function getBreadcrumbs($request)
    {
        return $this->responseSuccess($this->getBreadcrumbsData($request->input('folder_id', 0)));
    }

    public function download($request)
    {
        return $this->responseDownloadFile($request->input('path'));
    }

    public function postUploadFromEditor($request)
    {
        return $this->uploadFromEditor($request);
    }

    /**
     * Validate URL for SSRF attacks.
     *
     * Blocks:
     * - Internal/private IP ranges (10.x, 172.16-31.x, 192.168.x, 127.x, etc.)
     * - Localhost and loopback addresses
     * - Link-local addresses
     * - Cloud metadata endpoints (169.254.169.254, etc.)
     * - Non-HTTP(S) schemes
     *
     * @param string $url The URL to validate
     * @return string|null Error message if URL is blocked, null if valid
     */
    protected function validateUrlForSsrf(string $url): ?string
    {
        // Parse the URL
        $parsed = parse_url($url);

        if ($parsed === false || !isset($parsed['host'])) {
            return trans('filament-media::media.url_invalid');
        }

        // Only allow HTTP and HTTPS schemes
        $scheme = strtolower($parsed['scheme'] ?? '');
        if (!in_array($scheme, ['http', 'https'])) {
            return trans('filament-media::media.url_scheme_not_allowed');
        }

        $host = strtolower($parsed['host']);

        // Block localhost and loopback
        if (in_array($host, ['localhost', '127.0.0.1', '::1', '0.0.0.0', '[::1]'])) {
            return trans('filament-media::media.url_internal_not_allowed');
        }

        // Resolve hostname to IP for additional checks
        $ip = gethostbyname($host);

        // If DNS resolution fails, gethostbyname returns the hostname
        if ($ip === $host && !filter_var($host, FILTER_VALIDATE_IP)) {
            // Could not resolve - allow but log for monitoring
            logger()->warning('SSRF check: Could not resolve hostname', ['url' => $url, 'host' => $host]);
            return null; // Allow but log
        }

        // Check if it's a valid IP
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return trans('filament-media::media.url_invalid');
        }

        // Block private and reserved IP ranges
        $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
        if (!filter_var($ip, FILTER_VALIDATE_IP, $flags)) {
            return trans('filament-media::media.url_internal_not_allowed');
        }

        // Additional check for cloud metadata endpoints
        $blockedIps = [
            '169.254.169.254', // AWS, GCP, Azure metadata
            '169.254.170.2',   // AWS ECS task metadata
            '100.100.100.200', // Alibaba Cloud metadata
        ];

        if (in_array($ip, $blockedIps)) {
            return trans('filament-media::media.url_internal_not_allowed');
        }

        // Check allowed domains from config if set
        $allowedDomains = $this->getConfig('allowed_download_domains', []);
        if (!empty($allowedDomains)) {
            $isAllowed = false;
            foreach ($allowedDomains as $domain) {
                $domain = strtolower($domain);
                if ($host === $domain || Str::endsWith($host, '.' . $domain)) {
                    $isAllowed = true;
                    break;
                }
            }
            if (!$isAllowed) {
                return trans('filament-media::media.url_domain_not_allowed');
            }
        }

        return null; // URL is valid
    }
}
