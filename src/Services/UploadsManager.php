<?php

namespace Codenzia\FilamentMedia\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToRetrieveMetadata;
use Codenzia\FilamentMedia\Facades\FilamentMedia;

class UploadsManager
{
    public function fileDetails(string $path): array
    {
        return [
            'filename' => File::basename($path),
            'url' => $path,
            'mime_type' => $this->fileMimeType(FilamentMedia::getRealPath($path)),
            'size' => $this->fileSize($path),
            'modified' => $this->fileModified($path),
        ];
    }

    public function fileMimeType(string $path): ?string
    {
        return FilamentMedia::getMimeType($path);
    }

    public function fileSize(string $path): int
    {
        try {
            return Storage::size($path);
        } catch (UnableToRetrieveMetadata) {
            return 0;
        }
    }

    public function fileModified(string $path): string
    {
        try {
            return Carbon::createFromTimestamp(Storage::lastModified($path));
        } catch (UnableToRetrieveMetadata) {
            return Carbon::now();
        }
    }

    public function createDirectory(string $folder): bool|string
    {
        $folder = $this->cleanFolder($folder);

        if (Storage::exists($folder)) {
            return trans('filament-media::media.folder_exists', compact('folder'));
        }

        return Storage::makeDirectory($folder);
    }

    protected function cleanFolder(string $folder): string
    {
        return trim(str_replace(['..', '\\'], ['', '/'], $folder), '/');
    }

    public function deleteDirectory(string $folder): bool|string
    {
        $folder = $this->cleanFolder($folder);

        $filesFolders = array_merge(Storage::directories($folder), Storage::files($folder));

        if (! empty($filesFolders)) {
            return trans('filament-media::media.directory_must_empty');
        }

        return Storage::deleteDirectory($folder);
    }

    public function deleteFile(string $path): bool
    {
        $path = $this->cleanFolder($path);

        return Storage::delete($path);
    }

    public function saveFile(
        string $path,
        string $content,
        ?UploadedFile $file = null,
        string $visibility = 'public'
    ): bool {
        $storage = Storage::disk(FilamentMedia::getConfig('disk', 'public'));

        if ($visibility === 'private' && ! FilamentMedia::isUsingCloud()) {
            $storage = Storage::disk('local');
        }

        if (! FilamentMedia::isChunkUploadEnabled() || ! $file) {
            try {
                return $storage->put($this->cleanFolder($path), $content, ['visibility' => $visibility]);
            } catch (Exception | FilesystemException) {
                return $storage->put($this->cleanFolder($path), $content);
            }
        }

        $currentChunksPath = FilamentMedia::getConfig('chunk.storage.chunks') . '/' . $file->getFilename();
        $disk = Storage::disk(FilamentMedia::getConfig('chunk.storage.disk'));

        try {
            $stream = $disk->getDriver()->readStream($currentChunksPath);

            try {
                $result = Storage::writeStream($path, $stream, ['visibility' => $visibility]);
            } catch (Exception | FilesystemException) {
                $result = Storage::writeStream($path, $stream);
            }

            if ($result) {
                $disk->delete($currentChunksPath);
            }
        } catch (Exception | FilesystemException) {
            return $storage->put($this->cleanFolder($path), $content);
        }

        return $result;
    }
}
