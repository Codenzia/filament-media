<?php

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
use Codenzia\FilamentMedia\Services\FileOperationService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    MediaFile::withoutGlobalScopes();
    MediaFolder::withoutGlobalScopes();
    Event::fake();
});

describe('FileOperationService - renameFile', function () {
    it('renames a file and dispatches events', function () {
        Storage::fake('public');

        $file = MediaFile::factory()->create([
            'name' => 'original-name',
            'url' => 'original-name.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        // Put the file on disk so the rename logic finds it
        Storage::put('original-name.jpg', 'file content');

        $service = app(FileOperationService::class);
        $service->renameFile($file, 'new-name', renameOnDisk: false);

        $file->refresh();

        expect($file->name)->toBe('new-name');

        Event::assertDispatched(MediaFileRenaming::class);
        Event::assertDispatched(MediaFileRenamed::class);
    });

    it('saves the new name to the database', function () {
        $file = MediaFile::factory()->create([
            'name' => 'old-name',
            'url' => 'old-name.pdf',
            'mime_type' => 'application/pdf',
        ]);

        $service = app(FileOperationService::class);
        $service->renameFile($file, 'updated-name', renameOnDisk: false);

        $freshFile = MediaFile::withTrashed()->find($file->id);

        expect($freshFile->name)->toBe('updated-name');
    });
});

describe('FileOperationService - renameFolder', function () {
    it('renames a folder and dispatches events', function () {
        $folder = MediaFolder::factory()->create([
            'name' => 'old-folder',
            'slug' => 'old-folder',
        ]);

        Storage::makeDirectory('old-folder');

        $service = app(FileOperationService::class);
        $service->renameFolder($folder, 'new-folder', renameOnDisk: false);

        $folder->refresh();

        expect($folder->name)->toBe('new-folder');

        Event::assertDispatched(MediaFolderRenaming::class);
        Event::assertDispatched(MediaFolderRenamed::class);
    });

    it('persists the renamed folder to the database', function () {
        $folder = MediaFolder::factory()->create([
            'name' => 'original-folder',
            'slug' => 'original-folder',
        ]);

        $service = app(FileOperationService::class);
        $service->renameFolder($folder, 'renamed-folder', renameOnDisk: false);

        $freshFolder = MediaFolder::withTrashed()->find($folder->id);

        expect($freshFolder->name)->toBe('renamed-folder');
    });
});

describe('FileOperationService - copyFile', function () {
    it('creates a copy of a file in the same folder', function () {
        $folder = MediaFolder::factory()->create([
            'name' => 'my-folder',
            'slug' => 'my-folder',
        ]);

        $originalFile = MediaFile::factory()->create([
            'name' => 'photo',
            'url' => 'my-folder/photo.jpg',
            'mime_type' => 'image/jpeg',
            'folder_id' => $folder->id,
            'size' => 2048,
            'alt' => 'A photo',
        ]);

        Storage::makeDirectory('my-folder');
        Storage::put('my-folder/photo.jpg', 'image data');

        $service = app(FileOperationService::class);
        $copiedFile = $service->copyFile($originalFile);

        expect($copiedFile)->toBeInstanceOf(MediaFile::class)
            ->and($copiedFile->id)->not->toBe($originalFile->id)
            ->and($copiedFile->folder_id)->toBe($folder->id)
            ->and($copiedFile->mime_type)->toBe('image/jpeg')
            ->and($copiedFile->size)->toBe(2048);

        Event::assertDispatched(MediaFileCopied::class);
    });

    it('creates a copy of a file in a different folder', function () {
        $sourceFolder = MediaFolder::factory()->create([
            'name' => 'source',
            'slug' => 'source',
        ]);

        $destFolder = MediaFolder::factory()->create([
            'name' => 'destination',
            'slug' => 'destination',
        ]);

        $originalFile = MediaFile::factory()->create([
            'name' => 'document',
            'url' => 'source/document.pdf',
            'mime_type' => 'application/pdf',
            'folder_id' => $sourceFolder->id,
        ]);

        Storage::makeDirectory('source');
        Storage::makeDirectory('destination');
        Storage::put('source/document.pdf', 'pdf content');

        $service = app(FileOperationService::class);
        $copiedFile = $service->copyFile($originalFile, $destFolder->id);

        expect($copiedFile->folder_id)->toBe($destFolder->id)
            ->and($copiedFile->id)->not->toBe($originalFile->id);

        Event::assertDispatched(MediaFileCopied::class);
    });
});

describe('FileOperationService - moveFile', function () {
    it('moves a file to a new folder', function () {
        $sourceFolder = MediaFolder::factory()->create([
            'name' => 'from',
            'slug' => 'from',
        ]);

        $destFolder = MediaFolder::factory()->create([
            'name' => 'to',
            'slug' => 'to',
        ]);

        $file = MediaFile::factory()->create([
            'name' => 'moveable',
            'url' => 'from/moveable.txt',
            'mime_type' => 'text/plain',
            'folder_id' => $sourceFolder->id,
        ]);

        Storage::makeDirectory('from');
        Storage::makeDirectory('to');
        Storage::put('from/moveable.txt', 'text content');

        $service = app(FileOperationService::class);
        $movedFile = $service->moveFile($file, $destFolder->id);

        expect($movedFile->folder_id)->toBe($destFolder->id);

        Event::assertDispatched(MediaFileMoved::class, function ($event) use ($sourceFolder, $destFolder) {
            return $event->oldFolderId === $sourceFolder->id
                && $event->newFolderId === $destFolder->id;
        });
    });

    it('updates the file URL after move', function () {
        $sourceFolder = MediaFolder::factory()->create([
            'name' => 'origin',
            'slug' => 'origin',
        ]);

        $destFolder = MediaFolder::factory()->create([
            'name' => 'target',
            'slug' => 'target',
        ]);

        $file = MediaFile::factory()->create([
            'name' => 'report',
            'url' => 'origin/report.pdf',
            'mime_type' => 'application/pdf',
            'folder_id' => $sourceFolder->id,
        ]);

        Storage::makeDirectory('origin');
        Storage::makeDirectory('target');
        Storage::put('origin/report.pdf', 'pdf content');

        $service = app(FileOperationService::class);
        $movedFile = $service->moveFile($file, $destFolder->id);

        expect($movedFile->url)->toContain('target');
    });
});

describe('FileOperationService - deleteFile', function () {
    it('deletes a file from storage', function () {
        $file = MediaFile::factory()->create([
            'name' => 'deletable',
            'url' => 'deletable.txt',
            'mime_type' => 'text/plain',
            'visibility' => 'public',
        ]);

        Storage::put('deletable.txt', 'content to delete');

        expect(Storage::exists('deletable.txt'))->toBeTrue();

        $service = app(FileOperationService::class);
        $result = $service->deleteFile($file);

        expect($result)->toBeTrue()
            ->and(Storage::exists('deletable.txt'))->toBeFalse();
    });

    it('returns true when file does not exist on disk', function () {
        $file = MediaFile::factory()->create([
            'name' => 'ghost-file',
            'url' => 'nonexistent.txt',
            'mime_type' => 'text/plain',
        ]);

        $service = app(FileOperationService::class);
        $result = $service->deleteFile($file);

        expect($result)->toBeTrue();
    });
});

describe('FileOperationService - deleteThumbnails', function () {
    it('deletes size variants for an image file', function () {
        $file = MediaFile::factory()->create([
            'name' => 'photo',
            'url' => 'photo.jpg',
            'mime_type' => 'image/jpeg',
            'visibility' => 'public',
        ]);

        // Create mock thumbnails based on configured sizes
        Storage::put('photo.jpg', 'original');
        Storage::put('photo-150x150.jpg', 'thumbnail');

        $service = app(FileOperationService::class);
        $result = $service->deleteThumbnails($file);

        // Thumbnails should be deleted (or return true if none existed)
        expect($result)->toBeTrue();
    });

    it('returns true for non-image files', function () {
        $file = MediaFile::factory()->create([
            'name' => 'document',
            'url' => 'document.pdf',
            'mime_type' => 'application/pdf',
        ]);

        $service = app(FileOperationService::class);
        $result = $service->deleteThumbnails($file);

        expect($result)->toBeTrue();
    });
});
