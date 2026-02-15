<?php

namespace Codenzia\FilamentMedia\Services;

use Codenzia\FilamentMedia\Models\MediaFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Mime\MimeTypes;

/**
 * Detects files on the storage disk that have no corresponding database record,
 * and provides options to import or delete them.
 */
class OrphanScanService
{
    public function __construct(
        protected StorageDriverService $storageDriver
    ) {}

    /**
     * Scan the storage disk and return files that have no matching DB record.
     */
    public function scan(): Collection
    {
        $disk = $this->getDisk();
        $allFiles = $disk->allFiles();

        $trackedUrls = MediaFile::withTrashed()
            ->pluck('url')
            ->map(fn (string $url) => ltrim($url, '/'))
            ->flip();

        $orphans = [];

        foreach ($allFiles as $filePath) {
            $normalized = ltrim($filePath, '/');

            if ($this->shouldSkip($normalized)) {
                continue;
            }

            if (! $trackedUrls->has($normalized)) {
                $orphans[] = $this->buildFileInfo($disk, $filePath);
            }
        }

        return collect($orphans);
    }

    /**
     * Import orphaned files into the database.
     *
     * @param  array  $paths  Relative paths on the storage disk
     * @return int Number of files imported
     */
    public function import(array $paths, int $folderId = 0, ?int $userId = null): int
    {
        $disk = $this->getDisk();
        $imported = 0;

        foreach ($paths as $path) {
            if (! $disk->exists($path)) {
                continue;
            }

            if (MediaFile::where('url', $path)->exists()) {
                continue;
            }

            $mimeType = $this->guessMimeType($path, $disk);
            $size = $disk->size($path);

            MediaFile::create([
                'name' => basename($path),
                'url' => $path,
                'mime_type' => $mimeType,
                'size' => $size,
                'folder_id' => $folderId,
                'user_id' => $userId ?? 0,
            ]);

            $imported++;
        }

        return $imported;
    }

    /**
     * Delete orphaned files from disk.
     *
     * @param  array  $paths  Relative paths on the storage disk
     * @return int Number of files deleted
     */
    public function delete(array $paths): int
    {
        $disk = $this->getDisk();
        $deleted = 0;

        foreach ($paths as $path) {
            if (MediaFile::where('url', $path)->exists()) {
                continue;
            }

            if ($disk->exists($path) && $disk->delete($path)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    protected function getDisk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk($this->storageDriver->getMediaDriver());
    }

    protected function shouldSkip(string $path): bool
    {
        $skipPatterns = ['.gitignore', '.htaccess', 'index.html', 'index.php', '.DS_Store', 'Thumbs.db'];

        $basename = basename($path);

        if (in_array($basename, $skipPatterns)) {
            return true;
        }

        // Skip hidden files
        if (str_starts_with($basename, '.')) {
            return true;
        }

        return false;
    }

    protected function buildFileInfo($disk, string $path): array
    {
        $size = 0;

        try {
            $size = $disk->size($path);
        } catch (\Throwable) {
        }

        return [
            'path' => $path,
            'name' => basename($path),
            'size' => $size,
            'mime_type' => $this->guessMimeType($path, $disk),
            'last_modified' => $disk->lastModified($path),
        ];
    }

    protected function guessMimeType(string $path, $disk): string
    {
        try {
            return $disk->mimeType($path) ?: 'application/octet-stream';
        } catch (\Throwable) {
            $extension = pathinfo($path, PATHINFO_EXTENSION);

            if ($extension) {
                $mimeTypes = (new MimeTypes)->getMimeTypes($extension);

                return $mimeTypes[0] ?? 'application/octet-stream';
            }

            return 'application/octet-stream';
        }
    }
}
