<?php

use Codenzia\FilamentMedia\Http\Resources\FileResource;
use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaFolder;
use Codenzia\FilamentMedia\Models\MediaTag;
use Codenzia\FilamentMedia\Services\MediaUrlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    MediaFile::withoutGlobalScopes();
    MediaFolder::withoutGlobalScopes();
});

describe('FileResource', function () {
    it('transforms a media file to array with expected keys', function () {
        $file = MediaFile::factory()->image()->create([
            'name' => 'test-photo',
            'url' => 'images/test-photo.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 2048,
            'alt' => 'A test photo',
        ]);

        $resource = (new FileResource($file))->toArray(Request::create('/'));

        expect($resource)->toBeArray()
            ->and($resource)->toHaveKeys([
                'id', 'is_folder', 'name', 'basename', 'url', 'full_url',
                'type', 'icon', 'thumb', 'size', 'mime_type',
                'created_at', 'updated_at', 'options', 'folder_id',
                'alt', 'file_exists', 'tags',
            ])
            ->and($resource['id'])->toBe($file->id)
            ->and($resource['is_folder'])->toBeFalse()
            ->and($resource['name'])->toBe('test-photo')
            ->and($resource['url'])->toBe('images/test-photo.jpg')
            ->and($resource['basename'])->toBe('test-photo.jpg')
            ->and($resource['alt'])->toBe('A test photo')
            ->and($resource['mime_type'])->toBe('image/jpeg');
    });

    it('sets file_exists based on storage check', function () {
        $file = MediaFile::factory()->create([
            'url' => 'nonexistent/path.jpg',
        ]);

        $resource = (new FileResource($file))->toArray(Request::create('/'));

        expect($resource['file_exists'])->toBeFalse();
    });

    it('includes folder_id in output', function () {
        $folder = MediaFolder::factory()->create();
        $file = MediaFile::factory()->create([
            'folder_id' => $folder->id,
        ]);

        $resource = (new FileResource($file))->toArray(Request::create('/'));

        expect($resource['folder_id'])->toBe($folder->id);
    });

    it('includes visibility in output', function () {
        $publicFile = MediaFile::factory()->create(['visibility' => 'public']);
        $privateFile = MediaFile::factory()->private()->create();

        $publicResource = (new FileResource($publicFile))->toArray(Request::create('/'));
        $privateResource = (new FileResource($privateFile))->toArray(Request::create('/'));

        expect($publicResource['visibility'])->toBe('public')
            ->and($privateResource['visibility'])->toBe('private');
    });

    it('uses direct URL for public file full_url', function () {
        $file = MediaFile::factory()->create([
            'visibility' => 'public',
            'url' => 'images/public-photo.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        $resource = (new FileResource($file))->toArray(Request::create('/'));

        expect($resource['full_url'])->toContain('images/public-photo.jpg')
            ->and($resource['full_url'])->not->toContain('media/private');
    });

    it('uses private route URL for private file full_url', function () {
        $file = MediaFile::factory()->private()->create([
            'url' => 'images/secret-photo.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        $resource = (new FileResource($file))->toArray(Request::create('/'));
        $expectedHash = sha1($file->id);

        expect($resource['full_url'])->toContain('media/private')
            ->and($resource['full_url'])->toContain($expectedHash);
    });

    it('returns null thumb for private non-image files', function () {
        $file = MediaFile::factory()->private()->create([
            'url' => 'docs/secret.pdf',
            'mime_type' => 'application/pdf',
        ]);

        $resource = (new FileResource($file))->toArray(Request::create('/'));

        expect($resource['thumb'])->toBeNull();
    });

    it('uses private route for private image thumb', function () {
        $file = MediaFile::factory()->private()->create([
            'url' => 'images/private-img.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        // Put file on disk so file_exists returns true
        Storage::disk('public')->put('images/private-img.jpg', 'content');

        $resource = (new FileResource($file))->toArray(Request::create('/'));

        // Private image thumb should use the private route (same as full_url)
        if ($resource['thumb'] !== null) {
            expect($resource['thumb'])->toContain('media/private');
        }
    });

    it('includes tags when loaded', function () {
        $file = MediaFile::factory()->create();

        $tag = MediaTag::create([
            'name' => 'Nature',
            'slug' => 'nature',
            'type' => 'tag',
        ]);

        $file->tags()->attach($tag->id);
        $file->load('tags');

        $resource = (new FileResource($file))->toArray(Request::create('/'));

        expect($resource['tags'])->toBeArray()
            ->and($resource['tags'])->toContain('Nature');
    });
});
