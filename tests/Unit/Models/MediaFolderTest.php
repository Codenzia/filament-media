<?php

use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaFolder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Disable global scopes for testing
    MediaFolder::withoutGlobalScopes();
    MediaFile::withoutGlobalScopes();
});

describe('MediaFolder Model', function () {
    it('can be created with factory', function () {
        $folder = MediaFolder::factory()->create();

        expect($folder)->toBeInstanceOf(MediaFolder::class)
            ->and($folder->exists)->toBeTrue();
    });

    it('has fillable attributes', function () {
        $folder = MediaFolder::factory()->create([
            'name' => 'Test Folder',
            'slug' => 'test-folder',
            'color' => '#ff0000',
        ]);

        expect($folder->name)->toBe('Test Folder')
            ->and($folder->slug)->toBe('test-folder')
            ->and($folder->color)->toBe('#ff0000');
    });

    it('uses soft deletes', function () {
        $folder = MediaFolder::factory()->create();
        $folderId = $folder->id;

        $folder->delete();

        expect(MediaFolder::find($folderId))->toBeNull()
            ->and(MediaFolder::withTrashed()->find($folderId))->not->toBeNull();
    });

    it('has many files', function () {
        $folder = MediaFolder::factory()->create();
        MediaFile::factory()->count(3)->create(['folder_id' => $folder->id]);

        expect($folder->files)->toHaveCount(3)
            ->and($folder->files->first())->toBeInstanceOf(MediaFile::class);
    });

    it('can have a parent folder', function () {
        $parent = MediaFolder::factory()->create();
        $child = MediaFolder::factory()->create(['parent_id' => $parent->id]);

        expect($child->parent)->toBeInstanceOf(MediaFolder::class)
            ->and($child->parent->id)->toBe($parent->id);
    });

    it('returns default when no parent', function () {
        $folder = MediaFolder::factory()->create(['parent_id' => null]);

        expect($folder->parent)->toBeInstanceOf(MediaFolder::class)
            ->and($folder->parent->exists)->toBeFalse();
    });

    it('has many child folders', function () {
        $parent = MediaFolder::factory()->create();
        MediaFolder::factory()->count(2)->create(['parent_id' => $parent->id]);

        expect($parent->children)->toHaveCount(2)
            ->and($parent->children->first())->toBeInstanceOf(MediaFolder::class);
    });

    it('creates unique slug when duplicate exists', function () {
        $parent = MediaFolder::factory()->create();

        MediaFolder::factory()->create([
            'slug' => 'test-folder',
            'parent_id' => $parent->id,
        ]);

        $newSlug = MediaFolder::createSlug('test-folder', $parent->id);

        expect($newSlug)->toBe('test-folder-1');
    });

    it('returns original slug when no duplicate exists', function () {
        $parent = MediaFolder::factory()->create();

        $newSlug = MediaFolder::createSlug('unique-folder', $parent->id);

        expect($newSlug)->toBe('unique-folder');
    });

    it('creates unique name when duplicate exists', function () {
        $parent = MediaFolder::factory()->create();

        MediaFolder::factory()->create([
            'name' => 'Test Folder',
            'parent_id' => $parent->id,
        ]);

        $newName = MediaFolder::createName('Test Folder', $parent->id);

        expect($newName)->toBe('Test Folder-1');
    });

    it('gets full path for nested folders', function () {
        $parent = MediaFolder::factory()->create(['slug' => 'parent']);
        $child = MediaFolder::factory()->create([
            'slug' => 'child',
            'parent_id' => $parent->id,
        ]);
        $grandchild = MediaFolder::factory()->create([
            'slug' => 'grandchild',
            'parent_id' => $child->id,
        ]);

        $path = MediaFolder::getFullPath($grandchild->id);

        expect($path)->toContain('parent')
            ->and($path)->toContain('child')
            ->and($path)->toContain('grandchild');
    });

    it('returns empty path for null folder id', function () {
        $path = MediaFolder::getFullPath(null);

        expect($path)->toBe('');
    });
});

describe('MediaFolder Cascade Delete', function () {
    it('soft deletes files when folder is soft deleted', function () {
        $folder = MediaFolder::factory()->create();
        $file = MediaFile::factory()->create(['folder_id' => $folder->id]);

        $folder->delete();

        expect(MediaFile::find($file->id))->toBeNull()
            ->and(MediaFile::withTrashed()->find($file->id))->not->toBeNull();
    });

    it('restores files when folder is restored', function () {
        $folder = MediaFolder::factory()->create();
        $file = MediaFile::factory()->create(['folder_id' => $folder->id]);

        $folder->delete();
        $folder->restore();

        expect(MediaFile::find($file->id))->not->toBeNull();
    });
});

describe('MediaFolder Factory States', function () {
    it('can create folder with parent', function () {
        $parent = MediaFolder::factory()->create();
        $child = MediaFolder::factory()->withParent($parent)->create();

        expect($child->parent_id)->toBe($parent->id);
    });

    it('can create folder with specific color', function () {
        $folder = MediaFolder::factory()->withColor('#123456')->create();

        expect($folder->color)->toBe('#123456');
    });
});
