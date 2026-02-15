<?php

namespace Codenzia\FilamentMedia\Services;

use Codenzia\FilamentMedia\Helpers\BaseHelper;
use Codenzia\FilamentMedia\Models\MediaFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Mime\MimeTypes;
use Throwable;

/**
 * Resolves media file URLs, real paths, MIME types, and download responses
 * across local and cloud storage drivers.
 */
class MediaUrlService
{
    public function __construct(
        protected StorageDriverService $storageDriver
    ) {}

    public function url(?string $path): string
    {
        $path = $path ? trim($path) : $path;

        if (Str::contains($path, ['http://', 'https://'])) {
            return $this->normalizeUrl($path);
        }

        $driver = $this->storageDriver->getMediaDriver();

        if ($driver === 'do_spaces' && (int) \setting('media_do_spaces_cdn_enabled')) {
            $customDomain = \setting('media_do_spaces_cdn_custom_domain');

            if ($customDomain) {
                return $this->normalizeUrl(rtrim($customDomain, '/') . '/' . ltrim($path, '/'));
            }

            return $this->normalizeUrl(
                str_replace('.digitaloceanspaces.com', '.cdn.digitaloceanspaces.com', Storage::disk($driver)->url($path))
            );
        }

        if ($driver === 'backblaze' && (int) \setting('media_backblaze_cdn_enabled')) {
            $customDomain = \setting('media_backblaze_cdn_custom_domain');
            $currentEndpoint = \setting('media_backblaze_endpoint');

            if ($customDomain) {
                return $this->normalizeUrl(rtrim($customDomain, '/') . '/' . ltrim($path, '/'));
            }

            return $this->normalizeUrl(str_replace($currentEndpoint, $customDomain, Storage::disk($driver)->url($path)));
        }

        return $this->normalizeUrl(Storage::disk($driver)->url($path));
    }

    public function visibilityAwareUrl(MediaFile $file): string
    {
        if ($file->visibility !== 'private') {
            return $this->url($file->url);
        }

        $id = $file->getKey();
        $hash = sha1($id);

        return route('media.private.url', compact('hash', 'id'));
    }

    public function getRealPath(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        try {
            $disk = $this->storageDriver->getMediaDriver();

            $path = $this->storageDriver->isUsingCloud()
                ? Storage::disk($disk)->url($url)
                : Storage::disk($disk)->path($url);

            return Arr::first(explode('?v=', $path));
        } catch (Throwable $e) {
            logger()->error('Failed to get real path: ' . $e->getMessage(), ['url' => $url]);

            return null;
        }
    }

    public function getMimeType(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        try {
            if (Str::contains($url, ['http://', 'https://'])) {
                return $this->getMimeTypeFromRemoteUrl($url);
            }

            return $this->getMimeTypeFromLocalPath($url);
        } catch (Throwable $e) {
            logger()->error('Failed to get MIME type: ' . $e->getMessage(), ['url' => $url]);

            return null;
        }
    }

    public function isImage(string $mimeType): bool
    {
        return Str::startsWith($mimeType, 'image/');
    }

    public function fileExists(?string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        if ($this->storageDriver->isUsingCloud()) {
            return Storage::exists($url);
        }

        $realPath = $this->getRealPath($url);

        return $realPath && File::exists($realPath);
    }

    public function getDefaultImage(bool $relative = false, ?string $size = null): string
    {
        $default = $this->getConfig('default_image');

        if ($relative) {
            return $default ?? '';
        }

        return $default ? url($default) : '';
    }

    public function getFileSize(?string $path): ?string
    {
        try {
            if (! $path || (! $this->storageDriver->isUsingCloud() && ! Storage::exists($path))) {
                return null;
            }

            $size = Storage::size($path);

            return $size == 0 ? '0kB' : BaseHelper::humanFilesize($size);
        } catch (Throwable) {
            return null;
        }
    }

    public function downloadResponse(string $filePath): mixed
    {
        $realPath = $this->getRealPath($filePath);
        $fileName = File::basename($realPath);

        if (! $this->storageDriver->isUsingCloud()) {
            if (! File::exists($realPath)) {
                return null;
            }

            return response()->download($realPath, $fileName);
        }

        return response()->make(Http::timeout(30)->get($realPath)->body(), 200, [
            'Content-type' => $this->getMimeType($filePath),
            'Content-Disposition' => sprintf('attachment; filename="%s"', $fileName),
        ]);
    }

    protected function normalizeUrl(string $url): string
    {
        return preg_replace('#(?<!:)//+#', '/', $url);
    }

    protected function getMimeTypeFromRemoteUrl(string $url): ?string
    {
        $fileExtension = pathinfo($url, PATHINFO_EXTENSION);

        if (! $fileExtension) {
            $realPath = $this->getRealPath($url);
            $fileExtension = $realPath ? File::extension($realPath) : null;
        }

        if (! $fileExtension) {
            return null;
        }

        if ($fileExtension === 'jfif') {
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
            return Arr::first((new MimeTypes)->getMimeTypes($fileExtension));
        }

        return $mimeType;
    }

    protected function getMimeTypeFromLocalPath(string $url): ?string
    {
        $realPath = $this->getRealPath($url);

        if (empty($realPath)) {
            return null;
        }

        $fileExtension = File::extension($realPath);

        if (! $fileExtension) {
            return null;
        }

        if ($fileExtension === 'jfif') {
            return 'image/jpeg';
        }

        return Arr::first((new MimeTypes)->getMimeTypes($fileExtension));
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
