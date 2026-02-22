<?php

use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaFolder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    MediaFile::withoutGlobalScopes();
    MediaFolder::withoutGlobalScopes();
});

describe('MediaFile - Automatic Folder Resolution', function () {
    it('auto-resolves a single-segment folder from URL path', function () {
        $file = MediaFile::factory()->create([
            'url' => 'avatars/photo.jpg',
            'folder_id' => 0,
        ]);

        $file->refresh();

        expect($file->folder_id)->not->toBe(0)
            ->and($file->folder)->toBeInstanceOf(MediaFolder::class)
            ->and($file->folder->slug)->toBe('avatars');
    });

    it('auto-resolves nested folders from URL path', function () {
        $file = MediaFile::factory()->create([
            'url' => 'products/gallery/photo.jpg',
            'folder_id' => 0,
        ]);

        $file->refresh();
        $folder = $file->folder;

        expect($folder->slug)->toBe('gallery');

        $parent = $folder->parent;
        expect($parent)->not->toBeNull()
            ->and($parent->slug)->toBe('products');
    });

    it('reuses existing folder instead of creating a duplicate', function () {
        $existingFolder = MediaFolder::factory()->create([
            'name' => 'Documents',
            'slug' => 'documents',
            'parent_id' => 0,
        ]);

        $file = MediaFile::factory()->create([
            'url' => 'documents/report.pdf',
            'folder_id' => 0,
        ]);

        $file->refresh();

        expect($file->folder_id)->toBe($existingFolder->id);

        // Ensure no duplicate folder was created
        $folderCount = MediaFolder::withoutGlobalScopes()
            ->where('slug', 'documents')
            ->where('parent_id', 0)
            ->count();

        expect($folderCount)->toBe(1);
    });

    it('skips resolution when folder_id is already set', function () {
        $folder = MediaFolder::factory()->create([
            'name' => 'My Folder',
            'slug' => 'my-folder',
        ]);

        $file = MediaFile::factory()->create([
            'url' => 'other-path/image.jpg',
            'folder_id' => $folder->id,
        ]);

        $file->refresh();

        // Should keep the explicitly set folder_id, not resolve from URL
        expect($file->folder_id)->toBe($folder->id);

        // The "other-path" folder should NOT have been created
        $otherFolder = MediaFolder::withoutGlobalScopes()
            ->where('slug', 'other-path')
            ->first();

        expect($otherFolder)->toBeNull();
    });

    it('skips resolution when auto_resolve_folders config is false', function () {
        config(['media.auto_resolve_folders' => false]);

        $file = MediaFile::factory()->create([
            'url' => 'some-folder/image.jpg',
            'folder_id' => 0,
        ]);

        $file->refresh();

        expect($file->folder_id)->toBe(0);

        $folder = MediaFolder::withoutGlobalScopes()
            ->where('slug', 'some-folder')
            ->first();

        expect($folder)->toBeNull();
    });

    it('skips resolution when URL has no directory path', function () {
        $file = MediaFile::factory()->create([
            'url' => 'image.jpg',
            'folder_id' => 0,
        ]);

        $file->refresh();

        expect($file->folder_id)->toBe(0);
    });

    it('creates folder with title-cased name from slug segment', function () {
        $file = MediaFile::factory()->create([
            'url' => 'user-uploads/photo.jpg',
            'folder_id' => 0,
        ]);

        $file->refresh();
        $folder = $file->folder;

        expect($folder->name)->toBe('User Uploads')
            ->and($folder->slug)->toBe('user-uploads');
    });

    it('handles deeply nested folder paths', function () {
        $file = MediaFile::factory()->create([
            'url' => 'level1/level2/level3/deep-file.txt',
            'folder_id' => 0,
        ]);

        $file->refresh();
        $folder = $file->folder;

        expect($folder->slug)->toBe('level3');
        expect($folder->parent->slug)->toBe('level2');
        expect($folder->parent->parent->slug)->toBe('level1');
    });

    it('assigns multiple files in same folder to the same folder record', function () {
        $file1 = MediaFile::factory()->create([
            'url' => 'shared/file1.jpg',
            'folder_id' => 0,
        ]);

        $file2 = MediaFile::factory()->create([
            'url' => 'shared/file2.jpg',
            'folder_id' => 0,
        ]);

        $file1->refresh();
        $file2->refresh();

        expect($file1->folder_id)->toBe($file2->folder_id)
            ->and($file1->folder_id)->not->toBe(0);
    });
});
