<?php

use Codenzia\FilamentMedia\Repositories\Interfaces\MediaFolderInterface;
use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaFolder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    MediaFile::withoutGlobalScopes();
    MediaFolder::withoutGlobalScopes();
    // Use interface binding which is set up by service provider
    $this->repository = app(MediaFolderInterface::class);
});

describe('MediaFolderRepository getBreadcrumbs', function () {
    it('returns empty array for null folder', function () {
        $breadcrumbs = $this->repository->getBreadcrumbs(null);

        expect($breadcrumbs)->toBeArray()
            ->and($breadcrumbs)->toBeEmpty();
    });

    it('returns breadcrumbs for nested folder', function () {
        $root = MediaFolder::factory()->create(['name' => 'Root']);
        $child = MediaFolder::factory()->create([
            'name' => 'Child',
            'parent_id' => $root->id,
        ]);
        $grandchild = MediaFolder::factory()->create([
            'name' => 'Grandchild',
            'parent_id' => $child->id,
        ]);

        $breadcrumbs = $this->repository->getBreadcrumbs($grandchild->id);

        expect($breadcrumbs)->toBeArray();
    });
});

describe('MediaFolderRepository getAllChildFolders', function () {
    it('returns child folders', function () {
        $parent = MediaFolder::factory()->create(['name' => 'Parent']);
        $child1 = MediaFolder::factory()->create([
            'name' => 'Child 1',
            'parent_id' => $parent->id,
        ]);
        $child2 = MediaFolder::factory()->create([
            'name' => 'Child 2',
            'parent_id' => $parent->id,
        ]);

        $children = $this->repository->getAllChildFolders($parent->id);

        expect(count($children))->toBe(2);
    });

    it('returns empty array for folder with no children', function () {
        $folder = MediaFolder::factory()->create(['name' => 'Lonely Folder']);

        $children = $this->repository->getAllChildFolders($folder->id);

        expect($children)->toBeEmpty();
    });
});

describe('MediaFolderRepository createName', function () {
    it('creates unique name for folder', function () {
        $parent = MediaFolder::factory()->create(['name' => 'Parent']);
        MediaFolder::factory()->create([
            'name' => 'Existing',
            'parent_id' => $parent->id,
        ]);

        $name = $this->repository->createName('Existing', $parent->id);

        expect($name)->not->toBe('Existing');
    });

    it('returns original name when no duplicate', function () {
        $parent = MediaFolder::factory()->create(['name' => 'Parent']);

        $name = $this->repository->createName('Unique Folder', $parent->id);

        expect($name)->toBe('Unique Folder');
    });
});

describe('MediaFolderRepository deleteFolder', function () {
    it('soft deletes folder', function () {
        $folder = MediaFolder::factory()->create();

        $this->repository->deleteFolder($folder->id);

        expect(MediaFolder::find($folder->id))->toBeNull()
            ->and(MediaFolder::withTrashed()->find($folder->id))->not->toBeNull();
    });

    it('force deletes folder when force is true', function () {
        $folder = MediaFolder::factory()->create();
        $folderId = $folder->id;

        $this->repository->deleteFolder($folderId, true);

        expect(MediaFolder::withTrashed()->find($folderId))->toBeNull();
    });
});

describe('MediaFolderRepository getFullPath', function () {
    it('returns full path for nested folder', function () {
        $root = MediaFolder::factory()->create(['name' => 'Root', 'slug' => 'root']);
        $child = MediaFolder::factory()->create([
            'name' => 'Child',
            'slug' => 'child',
            'parent_id' => $root->id,
        ]);

        $path = $this->repository->getFullPath($child->id);

        expect($path)->toContain('root')
            ->and($path)->toContain('child');
    });

    it('returns null for null folder id', function () {
        $path = $this->repository->getFullPath(null);

        expect($path)->toBeEmpty();
    });
});

describe('MediaFolderRepository restoreFolder', function () {
    it('restores soft deleted folder', function () {
        $folder = MediaFolder::factory()->create();
        $folder->delete();

        expect(MediaFolder::find($folder->id))->toBeNull();

        $this->repository->restoreFolder($folder->id);

        expect(MediaFolder::find($folder->id))->not->toBeNull();
    });
});
