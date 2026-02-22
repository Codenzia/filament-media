<?php

namespace Codenzia\FilamentMedia\Http\Controllers;

use Codenzia\FilamentMedia\FilamentMedia;
use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Support\MediaHash;
use Codenzia\FilamentMedia\Services\StorageDriverService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivateMediaController extends Controller
{
    public function show(string $hash, string $id): BinaryFileResponse|RedirectResponse|StreamedResponse
    {
        $expectedHash = MediaHash::generate($id);

        if ($hash !== $expectedHash) {
            abort(404);
        }

        $file = MediaFile::withoutGlobalScopes()->find($id);

        if (! $file) {
            abort(404);
        }

        $filamentMedia = app(FilamentMedia::class);

        if (! $filamentMedia->canAccessFile($file, auth()->user())) {
            abort(403, trans('filament-media::media.access_denied'));
        }

        $storageDriver = app(StorageDriverService::class);

        if ($storageDriver->isUsingCloud()) {
            return $this->serveCloudFile($file, $storageDriver);
        }

        return $this->serveLocalFile($file, $storageDriver);
    }

    public function showThumbnail(string $hash, string $id, string $size): BinaryFileResponse|RedirectResponse|StreamedResponse
    {
        $expectedHash = MediaHash::generate($id);

        if ($hash !== $expectedHash) {
            abort(404);
        }

        $file = MediaFile::withoutGlobalScopes()->find($id);

        if (! $file) {
            abort(404);
        }

        $filamentMedia = app(FilamentMedia::class);

        if (! $filamentMedia->canAccessFile($file, auth()->user())) {
            abort(403, trans('filament-media::media.access_denied'));
        }

        $storageDriver = app(StorageDriverService::class);
        $thumbUrl = $this->buildThumbnailPath($file->url, $size);

        if ($storageDriver->isUsingCloud()) {
            return $this->serveCloudThumbnail($thumbUrl, $storageDriver);
        }

        return $this->serveLocalThumbnail($thumbUrl, $file);
    }

    protected function serveCloudFile(MediaFile $file, StorageDriverService $storageDriver): RedirectResponse|StreamedResponse
    {
        $disk = $storageDriver->getMediaDriver();
        $expiryMinutes = static::getSignedUrlExpiry();

        try {
            $temporaryUrl = Storage::disk($disk)->temporaryUrl(
                $file->url,
                now()->addMinutes($expiryMinutes)
            );

            return redirect($temporaryUrl);
        } catch (\RuntimeException) {
            return $this->streamFromDisk($disk, $file->url, $file->mime_type, $file->name, $file->url);
        }
    }

    protected function serveLocalFile(MediaFile $file, StorageDriverService $storageDriver): BinaryFileResponse
    {
        $disk = $file->visibility === 'private'
            ? (FilamentMedia::getConfig('private_files.private_disk') ?? 'local')
            : $storageDriver->getMediaDriver();

        $path = Storage::disk($disk)->path($file->url);

        if (! file_exists($path)) {
            abort(404);
        }

        $mimeType = $file->mime_type ?? mime_content_type($path);
        $fileName = $this->buildFileName($file->name, $file->url);
        $disposition = request()->query('download') ? 'attachment' : 'inline';

        $response = response()->file($path, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => $disposition . '; filename="' . $fileName . '"',
        ]);

        $response->setPrivate();
        $response->setMaxAge(3600);

        return $response;
    }

    protected function serveCloudThumbnail(string $thumbUrl, StorageDriverService $storageDriver): RedirectResponse|StreamedResponse
    {
        $disk = $storageDriver->getMediaDriver();
        $expiryMinutes = static::getSignedUrlExpiry();

        try {
            $temporaryUrl = Storage::disk($disk)->temporaryUrl(
                $thumbUrl,
                now()->addMinutes($expiryMinutes)
            );

            return redirect($temporaryUrl);
        } catch (\RuntimeException) {
            return $this->streamFromDisk($disk, $thumbUrl, null, null, $thumbUrl);
        }
    }

    protected function serveLocalThumbnail(string $thumbUrl, MediaFile $file): BinaryFileResponse
    {
        $disk = $file->visibility === 'private'
            ? (FilamentMedia::getConfig('private_files.private_disk') ?? 'local')
            : app(StorageDriverService::class)->getMediaDriver();

        $path = Storage::disk($disk)->path($thumbUrl);

        if (! file_exists($path)) {
            abort(404);
        }

        $mimeType = $file->mime_type ?? mime_content_type($path);

        $response = response()->file($path, [
            'Content-Type' => $mimeType,
        ]);

        $response->setPrivate();
        $response->setMaxAge(3600);

        return $response;
    }

    protected function streamFromDisk(string $disk, string $filePath, ?string $mimeType, ?string $name, string $url): StreamedResponse
    {
        if (! Storage::disk($disk)->exists($filePath)) {
            abort(404);
        }

        $mimeType = $mimeType ?? 'application/octet-stream';
        $fileName = $name ? $this->buildFileName($name, $url) : basename($filePath);

        return Storage::disk($disk)->response($filePath, $fileName, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    protected function buildThumbnailPath(string $url, string $size): string
    {
        $info = pathinfo($url);
        $dir = ($info['dirname'] && $info['dirname'] !== '.') ? $info['dirname'] . '/' : '';

        return $dir . $info['filename'] . '-' . $size . '.' . ($info['extension'] ?? '');
    }

    protected function buildFileName(string $name, string $url): string
    {
        $extension = pathinfo($url, PATHINFO_EXTENSION);

        if ($extension && ! str_ends_with($name, '.' . $extension)) {
            return $name . '.' . $extension;
        }

        return $name;
    }

    protected static function getSignedUrlExpiry(): int
    {
        return (int) (FilamentMedia::getConfig('private_files.signed_url_expiry') ?? 30);
    }
}
