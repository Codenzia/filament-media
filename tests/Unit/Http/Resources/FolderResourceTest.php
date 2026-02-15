<?php

use Codenzia\FilamentMedia\Http\Resources\FolderResource;
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
                'id', 'name', 'color', 'created_at', 'updated_at', 'tags',
            ])
            ->and($resource['id'])->toBe($folder->id)
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
});
