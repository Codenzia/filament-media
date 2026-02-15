<?php

namespace Codenzia\FilamentMedia\Services;

use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaFolder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class ExportImportService
{
    public function __construct(
        protected StorageDriverService $storageDriver,
        protected MediaUrlService $urlService,
        protected UploadService $uploadService,
        protected TagService $tagService,
        protected MetadataService $metadataService
    ) {}

    public function exportFiles(array $fileIds, string $format = 'zip'): StreamedResponse
    {
        $files = MediaFile::whereIn('id', $fileIds)->get();

        return $this->createZipResponse($files, 'media-export');
    }

    public function exportFolder(int $folderId, bool $includeSubfolders = true): StreamedResponse
    {
        $query = MediaFile::where('folder_id', $folderId);

        if ($includeSubfolders) {
            $folderIds = $this->getDescendantFolderIds($folderId);
            $folderIds[] = $folderId;
            $query = MediaFile::whereIn('folder_id', $folderIds);
        }

        $files = $query->get();
        $folder = MediaFolder::find($folderId);

        return $this->createZipResponse($files, $folder?->name ?? 'folder-export');
    }

    public function exportWithMetadata(array $fileIds): StreamedResponse
    {
        $files = MediaFile::with(['tags', 'metadata'])->whereIn('id', $fileIds)->get();

        $manifest = $this->buildManifest($files);

        return $this->createZipResponseWithManifest($files, $manifest, 'media-export-with-metadata');
    }

    public function importFromZip(UploadedFile $zipFile, int $folderId = 0): array
    {
        $tempDir = sys_get_temp_dir() . '/media_import_' . uniqid();
        mkdir($tempDir, 0755, true);

        $zip = new ZipArchive;
        if ($zip->open($zipFile->getRealPath()) !== true) {
            return ['error' => true, 'message' => 'Failed to open ZIP file', 'imported' => 0];
        }

        $zip->extractTo($tempDir);
        $zip->close();

        // Check for manifest.json for metadata import
        $manifestPath = $tempDir . '/manifest.json';
        if (file_exists($manifestPath)) {
            $result = $this->importWithManifest($tempDir, $manifestPath, $folderId);
        } else {
            $result = $this->importDirectory($tempDir, $folderId);
        }

        $this->deleteDirectory($tempDir);

        return $result;
    }

    public function importFromFolder(string $path, int $folderId = 0): array
    {
        if (! is_dir($path)) {
            return ['error' => true, 'message' => 'Directory not found', 'imported' => 0];
        }

        return $this->importDirectory($path, $folderId);
    }

    protected function createZipResponse($files, string $name): StreamedResponse
    {
        return response()->streamDownload(function () use ($files) {
            $tempFile = tempnam(sys_get_temp_dir(), 'media_export_');
            $zip = new ZipArchive;

            if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                return;
            }

            foreach ($files as $file) {
                $this->addFileToZip($zip, $file);
            }

            $zip->close();

            readfile($tempFile);
            @unlink($tempFile);
        }, $name . '.zip', ['Content-Type' => 'application/zip']);
    }

    protected function createZipResponseWithManifest($files, array $manifest, string $name): StreamedResponse
    {
        return response()->streamDownload(function () use ($files, $manifest) {
            $tempFile = tempnam(sys_get_temp_dir(), 'media_export_');
            $zip = new ZipArchive;

            if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                return;
            }

            foreach ($files as $file) {
                $this->addFileToZip($zip, $file);
            }

            $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $zip->close();

            readfile($tempFile);
            @unlink($tempFile);
        }, $name . '.zip', ['Content-Type' => 'application/zip']);
    }

    protected function addFileToZip(ZipArchive $zip, MediaFile $file): void
    {
        try {
            if ($this->storageDriver->isUsingCloud()) {
                $content = Storage::get($file->url);
                if ($content) {
                    $zip->addFromString(basename($file->url), $content);
                }
            } else {
                $realPath = $this->urlService->getRealPath($file->url);
                if ($realPath && file_exists($realPath)) {
                    $zip->addFile($realPath, basename($file->url));
                }
            }
        } catch (\Throwable $e) {
            logger()->warning('Failed to add file to ZIP', ['file' => $file->url, 'error' => $e->getMessage()]);
        }
    }

    protected function buildManifest($files): array
    {
        $manifestFiles = [];

        foreach ($files as $file) {
            $entry = [
                'path' => $file->url,
                'name' => $file->name,
                'alt' => $file->alt,
                'description' => $file->description,
            ];

            if ($file->tags->isNotEmpty()) {
                $entry['tags'] = $file->tags->where('type', 'tag')->pluck('name')->toArray();
            }

            $collections = $file->tags->where('type', 'collection');
            if ($collections->isNotEmpty()) {
                $entry['collections'] = $collections->pluck('name')->toArray();
            }

            if ($file->metadata->isNotEmpty()) {
                $entry['metadata'] = [];
                foreach ($file->metadata as $field) {
                    $entry['metadata'][$field->slug] = $field->pivot->value;
                }
            }

            $manifestFiles[] = $entry;
        }

        return ['version' => '1.0', 'files' => $manifestFiles];
    }

    protected function importDirectory(string $path, int $folderId): array
    {
        $imported = 0;
        $errors = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $fileInfo) {
            if (! $fileInfo->isFile() || $fileInfo->getFilename() === 'manifest.json') {
                continue;
            }

            $result = $this->uploadService->uploadFromPath($fileInfo->getRealPath(), $folderId);

            if (! $result['error']) {
                $imported++;
            } else {
                $errors[] = $fileInfo->getFilename() . ': ' . $result['message'];
            }
        }

        return [
            'error' => false,
            'imported' => $imported,
            'errors' => $errors,
        ];
    }

    protected function importWithManifest(string $tempDir, string $manifestPath, int $folderId): array
    {
        $manifest = json_decode(file_get_contents($manifestPath), true);

        if (! $manifest || ! isset($manifest['files'])) {
            return $this->importDirectory($tempDir, $folderId);
        }

        $imported = 0;
        $errors = [];

        foreach ($manifest['files'] as $entry) {
            $filePath = $tempDir . '/' . basename($entry['path']);

            if (! file_exists($filePath)) {
                $errors[] = ($entry['name'] ?? basename($entry['path'])) . ': File not found in ZIP';
                continue;
            }

            $result = $this->uploadService->uploadFromPath($filePath, $folderId);

            if ($result['error']) {
                $errors[] = ($entry['name'] ?? '') . ': ' . $result['message'];
                continue;
            }

            $imported++;
            $file = MediaFile::find($result['data']->id);

            if (! $file) {
                continue;
            }

            // Apply metadata from manifest
            if (! empty($entry['alt'])) {
                $file->update(['alt' => $entry['alt']]);
            }
            if (! empty($entry['description'])) {
                $file->update(['description' => $entry['description']]);
            }

            if (! empty($entry['tags'])) {
                $this->tagService->attachTags($file, $entry['tags']);
            }

            if (! empty($entry['collections'])) {
                foreach ($entry['collections'] as $collectionName) {
                    $tag = \Codenzia\FilamentMedia\Models\MediaTag::findOrCreateByName($collectionName, 'collection');
                    $file->tags()->syncWithoutDetaching([$tag->id]);
                }
            }

            if (! empty($entry['metadata'])) {
                $fieldValues = [];
                foreach ($entry['metadata'] as $slug => $value) {
                    $field = \Codenzia\FilamentMedia\Models\MediaMetadataField::where('slug', $slug)->first();
                    if ($field) {
                        $fieldValues[$field->id] = $value;
                    }
                }
                if (! empty($fieldValues)) {
                    $this->metadataService->setMetadata($file, $fieldValues);
                }
            }
        }

        return [
            'error' => false,
            'imported' => $imported,
            'errors' => $errors,
        ];
    }

    protected function getDescendantFolderIds(int $folderId): array
    {
        $ids = [];
        $children = MediaFolder::where('parent_id', $folderId)->pluck('id')->toArray();

        foreach ($children as $childId) {
            $ids[] = $childId;
            $ids = array_merge($ids, $this->getDescendantFolderIds($childId));
        }

        return $ids;
    }

    protected function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getRealPath()) : unlink($item->getRealPath());
        }

        rmdir($dir);
    }
}
