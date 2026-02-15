<?php

use Codenzia\FilamentMedia\Http\Resources\FolderResource;
use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaFolder;
use Codenzia\FilamentMedia\Models\MediaTag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    MediaFolder::withoutGlobalScopes();
});

describe('FolderResource', function () {
    it('transforms a folder to array with expected keys', function () {
        $folder = MediaFolder::factory()->create([
            'name' => 'Photos',
            'color' => '#3498db',
        ]);

        $resource = (new FolderResource($folder))->toArray(Request::create('/'));

        expect($resource)->toBeArray()
            ->and($resource)->toHaveKeys([
                'id', 'is_folder', 'name', 'color', 'size', 'created_at', 'updated_at', 'tags',
            ])
            ->and($resource['id'])->toBe($folder->id)
            ->and($resource['is_folder'])->toBeTrue()
            ->and($resource['name'])->toBe('Photos')
            ->and($resource['created_at'])->not->toBeNull()
            ->and($resource['updated_at'])->not->toBeNull();
    });

    it('includes color attribute', function () {
        $folder = MediaFolder::factory()->create([
            'color' => '#e74c3c',
        ]);

        $resource = (new FolderResource($folder))->toArray(Request::create('/'));

        expect($resource['color'])->toBe('#e74c3c');
    });

    it('returns null size for empty folder', function () {
        $folder = MediaFolder::factory()->create();

        $resource = (new FolderResource($folder))->toArray(Request::create('/'));

        expect($resource['size'])->toBeNull();
    });

    it('returns human readable size when files_sum_size is loaded', function () {
        $folder = MediaFolder::factory()->create();
        MediaFile::factory()->create(['folder_id' => $folder->id, 'size' => 1024]);
        MediaFile::factory()->create(['folder_id' => $folder->id, 'size' => 2048]);

        $folder->loadSum('files', 'size');

        $resource = (new FolderResource($folder))->toArray(Request::create('/'));

        expect($resource['size'])->toBe('3 kB');
    });
});
