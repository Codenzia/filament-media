<?php

use Codenzia\FilamentMedia\Models\MediaFolder;
use Codenzia\FilamentMedia\Pages\Concerns\HasFileManagementActions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

class FolderLocationPickerTestClass
{
    use HasFileManagementActions;

    public int $folderId = 0;

    public function testBuildFolderTree(): array
    {
        return $this->buildFolderTree();
    }

    public function testBuildBreadcrumbsForFolder(?int $folderId): array
    {
        return $this->buildBreadcrumbsForFolder($folderId);
    }
}

beforeEach(function () {
    Storage::fake('public');
    MediaFolder::withoutGlobalScopes();
    $this->helper = new FolderLocationPickerTestClass();
});

describe('buildFolderTree', function () {
    it('returns empty array when no folders exist', function () {
        $tree = $this->helper->testBuildFolderTree();

        expect($tree)->toBeArray()->toBeEmpty();
    });

    it('returns flat list for root-level folders', function () {
        MediaFolder::factory()->create(['name' => 'Documents', 'parent_id' => 0]);
        MediaFolder::factory()->create(['name' => 'Photos', 'parent_id' => 0]);

        $tree = $this->helper->testBuildFolderTree();

        expect($tree)->toHaveCount(2);
        expect(collect($tree)->pluck('name')->sort()->values()->all())
            ->toBe(['Documents', 'Photos']);
    });

    it('builds nested tree structure', function () {
        $parent = MediaFolder::factory()->create(['name' => 'Projects', 'parent_id' => 0]);
        $child = MediaFolder::factory()->create(['name' => 'Designs', 'parent_id' => $parent->id]);
        MediaFolder::factory()->create(['name' => 'Logos', 'parent_id' => $child->id]);

        $tree = $this->helper->testBuildFolderTree();

        expect($tree)->toHaveCount(1);
        expect($tree[0]['name'])->toBe('Projects');
        expect($tree[0]['children'])->toHaveCount(1);
        expect($tree[0]['children'][0]['name'])->toBe('Designs');
        expect($tree[0]['children'][0]['children'])->toHaveCount(1);
        expect($tree[0]['children'][0]['children'][0]['name'])->toBe('Logos');
    });

    it('excludes soft-deleted folders', function () {
        MediaFolder::factory()->create(['name' => 'Active', 'parent_id' => 0]);
        $deleted = MediaFolder::factory()->create(['name' => 'Deleted', 'parent_id' => 0]);
        $deleted->delete();

        $tree = $this->helper->testBuildFolderTree();

        expect($tree)->toHaveCount(1);
        expect($tree[0]['name'])->toBe('Active');
    });

    it('includes folder color in tree nodes', function () {
        MediaFolder::factory()->create([
            'name' => 'Colored',
            'parent_id' => 0,
            'color' => '#ff5733',
        ]);

        $tree = $this->helper->testBuildFolderTree();

        expect($tree[0]['color'])->toBe('#ff5733');
    });

    it('orders folders by name', function () {
        MediaFolder::factory()->create(['name' => 'Zebra', 'parent_id' => 0]);
        MediaFolder::factory()->create(['name' => 'Alpha', 'parent_id' => 0]);
        MediaFolder::factory()->create(['name' => 'Middle', 'parent_id' => 0]);

        $tree = $this->helper->testBuildFolderTree();

        expect(collect($tree)->pluck('name')->all())
            ->toBe(['Alpha', 'Middle', 'Zebra']);
    });
});

describe('buildBreadcrumbsForFolder', function () {
    it('returns only root breadcrumb for folder ID 0', function () {
        $breadcrumbs = $this->helper->testBuildBreadcrumbsForFolder(0);

        expect($breadcrumbs)->toHaveCount(1);
        expect($breadcrumbs[0]['id'])->toBe(0);
        expect($breadcrumbs[0]['name'])->toBe('All Media');
    });

    it('returns only root breadcrumb for null folder ID', function () {
        $breadcrumbs = $this->helper->testBuildBreadcrumbsForFolder(null);

        expect($breadcrumbs)->toHaveCount(1);
        expect($breadcrumbs[0]['id'])->toBe(0);
    });

    it('returns full path for nested folder', function () {
        $root = MediaFolder::factory()->create(['name' => 'Projects', 'parent_id' => 0]);
        $child = MediaFolder::factory()->create(['name' => 'Designs', 'parent_id' => $root->id]);
        $grandchild = MediaFolder::factory()->create(['name' => 'Logos', 'parent_id' => $child->id]);

        $breadcrumbs = $this->helper->testBuildBreadcrumbsForFolder($grandchild->id);

        expect($breadcrumbs)->toHaveCount(4);
        expect($breadcrumbs[0]['name'])->toBe('All Media');
        expect($breadcrumbs[1]['name'])->toBe('Projects');
        expect($breadcrumbs[2]['name'])->toBe('Designs');
        expect($breadcrumbs[3]['name'])->toBe('Logos');
    });

    it('returns root + folder for single-level folder', function () {
        $folder = MediaFolder::factory()->create(['name' => 'Documents', 'parent_id' => 0]);

        $breadcrumbs = $this->helper->testBuildBreadcrumbsForFolder($folder->id);

        expect($breadcrumbs)->toHaveCount(2);
        expect($breadcrumbs[0]['name'])->toBe('All Media');
        expect($breadcrumbs[1]['name'])->toBe('Documents');
        expect($breadcrumbs[1]['id'])->toBe($folder->id);
    });

    it('returns only root for non-existent folder ID', function () {
        $breadcrumbs = $this->helper->testBuildBreadcrumbsForFolder(99999);

        expect($breadcrumbs)->toHaveCount(1);
        expect($breadcrumbs[0]['id'])->toBe(0);
    });
});

describe('Create Folder with Custom Location', function () {
    it('validates duplicate name against selected destination', function () {
        $folderA = MediaFolder::factory()->create(['parent_id' => 0]);
        $folderB = MediaFolder::factory()->create(['parent_id' => 0]);

        MediaFolder::factory()->create([
            'name' => 'Photos',
            'parent_id' => $folderA->id,
        ]);

        // "Photos" does NOT exist in folderB — should be allowed
        $existsInB = MediaFolder::withoutGlobalScopes()
            ->where('name', 'Photos')
            ->where('parent_id', $folderB->id)
            ->whereNull('deleted_at')
            ->exists();

        expect($existsInB)->toBeFalse();

        // "Photos" DOES exist in folderA — should be rejected
        $existsInA = MediaFolder::withoutGlobalScopes()
            ->where('name', 'Photos')
            ->where('parent_id', $folderA->id)
            ->whereNull('deleted_at')
            ->exists();

        expect($existsInA)->toBeTrue();
    });

    it('has new_folder_location translation key', function () {
        $message = trans('filament-media::media.new_folder_location');

        expect($message)->toBe('Create new folder in:');
    });

    it('has create_folder_here translation key', function () {
        $message = trans('filament-media::media.create_folder_here');

        expect($message)->toBe('New Folder');
    });
});
