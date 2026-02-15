<?php

use Codenzia\FilamentMedia\Facades\FilamentMedia;
use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaFolder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    MediaFile::withoutGlobalScopes();
    MediaFolder::withoutGlobalScopes();

    FilamentMedia::partialMock()
        ->shouldReceive('getMediaDriver')->andReturn('public')
        ->shouldReceive('getSizes')->andReturn(['thumb' => '150x150']);
});

describe('SyncMediaCommand', function () {
    it('syncs a file from storage to database', function () {
        Storage::disk('public')->put('photo.jpg', 'image content');

        $this->artisan('filament-media:sync')
            ->assertSuccessful();

        $file = MediaFile::where('url', 'photo.jpg')->first();

        expect($file)->not->toBeNull()
            ->and($file->name)->toBe('photo')
            ->and($file->mime_type)->toBe('image/jpeg')
            ->and($file->visibility)->toBe('public');
    });

    it('syncs a folder from storage to database', function () {
        Storage::disk('public')->makeDirectory('documents');
        Storage::disk('public')->put('documents/file.txt', 'text content');

        $this->artisan('filament-media:sync')
            ->assertSuccessful();

        $folder = MediaFolder::where('name', 'documents')->first();

        expect($folder)->not->toBeNull()
            ->and($folder->name)->toBe('documents');
    });

    it('skips hidden files', function () {
        Storage::disk('public')->put('.gitignore', 'node_modules');
        Storage::disk('public')->put('.DS_Store', 'data');
        Storage::disk('public')->put('visible.jpg', 'image content');

        $this->artisan('filament-media:sync')
            ->assertSuccessful();

        expect(MediaFile::count())->toBe(1);
        expect(MediaFile::first()->name)->toBe('visible');
    });

    it('skips the thumbnails directory', function () {
        Storage::disk('public')->makeDirectory('thumbnails');
        Storage::disk('public')->put('thumbnails/thumb.jpg', 'thumbnail data');
        Storage::disk('public')->makeDirectory('photos');
        Storage::disk('public')->put('photos/real.jpg', 'real data');

        $this->artisan('filament-media:sync')
            ->assertSuccessful();

        $folder = MediaFolder::where('name', 'thumbnails')->first();
        expect($folder)->toBeNull();

        $photosFolder = MediaFolder::where('name', 'photos')->first();
        expect($photosFolder)->not->toBeNull();
    });

    it('does not duplicate existing files', function () {
        Storage::disk('public')->put('existing.jpg', 'image content');

        MediaFile::factory()->create([
            'name' => 'existing',
            'url' => 'existing.jpg',
            'folder_id' => 0,
            'mime_type' => 'image/jpeg',
        ]);

        $this->artisan('filament-media:sync')
            ->assertSuccessful();

        expect(MediaFile::where('url', 'existing.jpg')->count())->toBe(1);
    });

    it('returns SUCCESS exit code', function () {
        $this->artisan('filament-media:sync')
            ->assertSuccessful()
            ->assertExitCode(0);
    });

    it('skips thumbnail variant files', function () {
        Storage::disk('public')->put('photo.jpg', 'original image');
        Storage::disk('public')->put('photo-150x150.jpg', 'thumbnail variant');

        $this->artisan('filament-media:sync')
            ->assertSuccessful();

        expect(MediaFile::count())->toBe(1);
        expect(MediaFile::first()->name)->toBe('photo');
    });
});
