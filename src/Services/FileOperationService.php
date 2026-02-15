<?php

namespace Codenzia\FilamentMedia\Services;

use Codenzia\FilamentMedia\Events\MediaFileCopied;
use Codenzia\FilamentMedia\Events\MediaFileDeleted;
use Codenzia\FilamentMedia\Events\MediaFileDeleting;
use Codenzia\FilamentMedia\Events\MediaFileMoved;
use Codenzia\FilamentMedia\Events\MediaFileRenamed;
use Codenzia\FilamentMedia\Events\MediaFileRenaming;
use Codenzia\FilamentMedia\Events\MediaFolderRenamed;
use Codenzia\FilamentMedia\Events\MediaFolderRenaming;
use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaFolder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class FileOperationService
{
    public function __construct(
        protected StorageDriverService $storageDriver,
        protected MediaUrlService $urlService,
        protected ImageService $imageService
    ) {}

    public function deleteFile(MediaFile $file): bool
    {
        $this->deleteThumbnails($file);

        if (! $this->storageDriver->isUsingCloud() && ! Storage::exists($file->url)) {
            return true;
        }

        try {
            return Storage::delete($file->url);
        } catch (\Throwable $e) {
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

        foreach ($this->imageService->getSizes() as $size) {
            $files[] = str_replace($filename, $filename . '-' . $size, $file->url);
        }

        try {
            return Storage::delete($files);
        } catch (\Throwable) {
            return true;
        }
    }

    public function renameFile(MediaFile $file, string $newName, bool $renameOnDisk = true): void
    {
        MediaFileRenaming::dispatch($file, $newName, $renameOnDisk);

        $file->name = MediaFile::createName($newName, $file->folder_id);

        if ($renameOnDisk) {
            $filePath = $this->urlService->getRealPath($file->url);

            if (File::exists($filePath)) {
                $newFilePath = str_replace(
                    File::name($file->url),
                    File::name($file->name),
                    $file->url
                );

                File::move($filePath, $this->urlService->getRealPath($newFilePath));

                $this->deleteFile($file);

                $file->url = str_replace(
                    File::name($file->url),
                    File::name($file->name),
                    $file->url
                );

                $this->imageService->generateThumbnails($file);
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

    public function copyFile(MediaFile $file, ?int $newFolderId = null): MediaFile
    {
        $newFile = $file->replicate();
        $newFile->folder_id = $newFolderId ?? $file->folder_id;
        $newFile->name = MediaFile::createName($file->name, $newFile->folder_id);

        // Copy the physical file
        $folderPath = MediaFolder::getFullPath($newFile->folder_id);
        $extension = pathinfo($file->url, PATHINFO_EXTENSION);
        $newSlug = MediaFile::createSlug($newFile->name, $extension, $folderPath ?: '');
        $newUrl = $folderPath ? $folderPath . '/' . $newSlug : $newSlug;

        try {
            Storage::copy($file->url, $newUrl);
            $newFile->url = $newUrl;
        } catch (\Throwable $e) {
            logger()->warning('Failed to copy file on disk', ['file' => $file->url, 'error' => $e->getMessage()]);
            $newFile->url = $file->url;
        }

        $newFile->save();

        // Copy tag associations
        if ($file->tags->isNotEmpty()) {
            $newFile->tags()->sync($file->tags->pluck('id'));
        }

        MediaFileCopied::dispatch($newFile, $file);

        return $newFile;
    }

    public function moveFile(MediaFile $file, int $newFolderId): MediaFile
    {
        $oldFolderId = $file->folder_id;
        $folderPath = MediaFolder::getFullPath($newFolderId);
        $extension = pathinfo($file->url, PATHINFO_EXTENSION);
        $newSlug = MediaFile::createSlug($file->name, $extension, $folderPath ?: '');
        $newUrl = $folderPath ? $folderPath . '/' . $newSlug : $newSlug;

        try {
            Storage::move($file->url, $newUrl);
            $this->deleteThumbnails($file);
            $file->url = $newUrl;
        } catch (\Throwable $e) {
            logger()->warning('Failed to move file on disk', ['file' => $file->url, 'error' => $e->getMessage()]);
        }

        $file->folder_id = $newFolderId;
        $file->name = MediaFile::createName($file->name, $newFolderId);
        $file->save();

        $this->imageService->generateThumbnails($file);

        MediaFileMoved::dispatch($file, $oldFolderId, $newFolderId);

        return $file;
    }
}
