<?php

use Codenzia\FilamentMedia\Models\MediaFolder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    MediaFolder::withoutGlobalScopes();
});

describe('Create Folder Duplicate Name Validation', function () {
    it('detects duplicate folder name in same parent', function () {
        $parent = MediaFolder::factory()->create();

        MediaFolder::factory()->create([
            'name' => 'Photos',
            'parent_id' => $parent->id,
        ]);

        $exists = MediaFolder::withoutGlobalScopes()
            ->where('name', 'Photos')
            ->where('parent_id', $parent->id)
            ->whereNull('deleted_at')
            ->exists();

        expect($exists)->toBeTrue();
    });

    it('allows same name in different parent', function () {
        $parent1 = MediaFolder::factory()->create();
        $parent2 = MediaFolder::factory()->create();

        MediaFolder::factory()->create([
            'name' => 'Photos',
            'parent_id' => $parent1->id,
        ]);

        $exists = MediaFolder::withoutGlobalScopes()
            ->where('name', 'Photos')
            ->where('parent_id', $parent2->id)
            ->whereNull('deleted_at')
            ->exists();

        expect($exists)->toBeFalse();
    });

    it('allows same name when existing folder is soft deleted', function () {
        $parent = MediaFolder::factory()->create();

        $folder = MediaFolder::factory()->create([
            'name' => 'Photos',
            'parent_id' => $parent->id,
        ]);

        $folder->delete();

        $exists = MediaFolder::withoutGlobalScopes()
            ->where('name', 'Photos')
            ->where('parent_id', $parent->id)
            ->whereNull('deleted_at')
            ->exists();

        expect($exists)->toBeFalse();
    });

    it('detects duplicate folder name in root', function () {
        MediaFolder::factory()->create([
            'name' => 'Documents',
            'parent_id' => 0,
        ]);

        $exists = MediaFolder::withoutGlobalScopes()
            ->where('name', 'Documents')
            ->where('parent_id', 0)
            ->whereNull('deleted_at')
            ->exists();

        expect($exists)->toBeTrue();
    });

    it('does not treat different names as duplicates', function () {
        $parent = MediaFolder::factory()->create();

        MediaFolder::factory()->create([
            'name' => 'Photos',
            'parent_id' => $parent->id,
        ]);

        $exists = MediaFolder::withoutGlobalScopes()
            ->where('name', 'Videos')
            ->where('parent_id', $parent->id)
            ->whereNull('deleted_at')
            ->exists();

        expect($exists)->toBeFalse();
    });

    it('has folder_name_exists translation key', function () {
        $message = trans('filament-media::media.folder_name_exists');

        expect($message)->toBe('A folder with this name already exists.');
    });
});
