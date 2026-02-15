<?php

namespace Codenzia\FilamentMedia\Services;

use Codenzia\FilamentMedia\Models\MediaFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Encoders\AutoEncoder;
use Intervention\Image\ImageManager;

class ImageService
{
    public function __construct(
        protected StorageDriverService $storageDriver,
        protected MediaUrlService $urlService,
        protected ThumbnailService $thumbnailService,
        protected UploadsManager $uploadManager
    ) {}

    public function generateThumbnails(MediaFile $file, ?UploadedFile $fileUpload = null): bool
    {
        if (! $file->canGenerateThumbnails()) {
            return false;
        }

        if (! $this->storageDriver->isUsingCloud() && ! File::exists($this->urlService->getRealPath($file->url))) {
            return false;
        }

        $this->applyWatermarkIfNeeded($file);

        if (! \setting('media_enable_thumbnail_sizes', true)) {
            return false;
        }

        foreach ($this->getSizes() as $size) {
            $this->generateSingleThumbnail($file, $size, $fileUpload);
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

        $watermarkPath = $this->urlService->getRealPath($watermarkImage);

        if ($this->storageDriver->isUsingCloud()) {
            $watermark = $this->imageManager()->read(file_get_contents($watermarkPath));
            $imageSource = $this->imageManager()->read(
                file_get_contents($this->urlService->getRealPath($image))
            );
        } else {
            if (! File::exists($watermarkPath)) {
                return false;
            }
            $watermark = $this->imageManager()->read($watermarkPath);
            $imageSource = $this->imageManager()->read($this->urlService->getRealPath($image));
        }

        $watermarkSize = (int) round(
            $imageSource->width() * ((int) \setting('media_watermark_size', $this->getConfig('watermark.size')) / 100),
            2
        );

        $watermark->scale($watermarkSize);

        $imageSource->place(
            $watermark,
            \setting('media_watermark_position', $this->getConfig('watermark.position')),
            (int) \setting('media_watermark_position_x', $this->getConfig('watermark.x')),
            (int) \setting('media_watermark_position_y', $this->getConfig('watermark.y')),
            (int) \setting('media_watermark_opacity', $this->getConfig('watermark.opacity'))
        );

        $destinationPath = sprintf(
            '%s/%s',
            trim(File::dirname($image), '/'),
            File::name($image) . '.' . File::extension($image)
        );

        $this->uploadManager->saveFile($destinationPath, $imageSource->encode(new AutoEncoder));

        return true;
    }

    public function canGenerateThumbnails(?string $mimeType): bool
    {
        if (! $this->getConfig('generate_thumbnails_enabled')) {
            return false;
        }

        if (! $mimeType) {
            return false;
        }

        return $this->urlService->isImage($mimeType) && ! in_array($mimeType, ['image/svg+xml', 'image/x-icon']);
    }

    public function imageManager(): ImageManager
    {
        if ($this->getImageProcessingLibrary() === 'imagick' && extension_loaded('imagick')) {
            return ImageManager::imagick();
        }

        return ImageManager::gd();
    }

    public function getImageUrl(
        ?string $url,
        $size = null,
        bool $relativePath = false,
        $default = null
    ): ?string {
        if (empty($url) || empty(trim($url))) {
            return $default;
        }

        $url = trim($url);

        if (str_starts_with($url, 'data:image/') || str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        if (empty($size) || $url === '__value__') {
            return $relativePath ? $url : $this->urlService->url($url);
        }

        if (
            \setting('media_enable_thumbnail_sizes', true)
            && array_key_exists($size, $this->getSizes())
            && $this->canGenerateThumbnails($this->urlService->getMimeType($this->urlService->getRealPath($url)))
        ) {
            $fileName = File::name($url);
            $fileExtension = File::extension($url);
            $sizeValue = $this->getSize($size);

            $url = str_replace(
                $fileName . '.' . $fileExtension,
                $fileName . '-' . $sizeValue . '.' . $fileExtension,
                $url
            );
        }

        if ($relativePath) {
            return $url;
        }

        if ($url === '__image__') {
            return $this->urlService->url($default);
        }

        return $this->urlService->url($url);
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

            $sizes[$name] = ($width ?: 'auto') . 'x' . ($height ?: 'auto');
        }

        return $sizes;
    }

    public function getSize(string $name): ?string
    {
        return Arr::get($this->getSizes(), $name);
    }

    protected function applyWatermarkIfNeeded(MediaFile $file): void
    {
        $folderIds = json_decode(\setting('media_folders_can_add_watermark', ''), true);

        if (
            empty($folderIds)
            || in_array($file->folder_id, $folderIds)
            || ! empty(array_intersect($file->folder->parents->pluck('id')->all(), $folderIds))
        ) {
            $this->insertWatermark($file->url);
        }
    }

    protected function generateSingleThumbnail(MediaFile $file, string $size, mixed $fileUpload): void
    {
        $readableSize = explode('x', $size);

        if (! $fileUpload || $this->isChunkUploadEnabled()) {
            $fileUpload = $this->urlService->getRealPath($file->url);

            if ($this->storageDriver->isUsingCloud()) {
                $fileUpload = @file_get_contents($fileUpload);
                if (! $fileUpload) {
                    return;
                }
            }
        }

        $thumbnailFileName = File::name($file->url) . '-' . $size . '.' . File::extension($file->url);
        $dirName = File::dirname($file->url);
        $thumbnailPath = ($dirName === '.' || ! $dirName) ? $thumbnailFileName : $dirName . '/' . $thumbnailFileName;

        if (! $this->storageDriver->isUsingCloud() && Storage::exists($thumbnailPath)) {
            return;
        }

        if ($this->storageDriver->isUsingCloud()) {
            $destinationPath = ($dirName === '.' || ! $dirName) ? '' : $dirName;
        } else {
            $disk = $this->storageDriver->getMediaDriver();
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

    protected function isChunkUploadEnabled(): bool
    {
        return (bool) \setting('media_chunk_enabled', (int) $this->getConfig('chunk.enabled') == 1);
    }

    protected function getImageProcessingLibrary(): string
    {
        return \setting('media_image_processing_library') ?: 'gd';
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
