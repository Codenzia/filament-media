<?php

use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaFolder;
use Codenzia\FilamentMedia\Services\FileOperationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');
    MediaFile::withoutGlobalScopes();
    MediaFolder::withoutGlobalScopes();

    config(['media.private_files.private_disk' => 'local']);
});

describe('FileOperationService - changeVisibility', function () {
    it('changes file visibility from public to private', function () {
        $file = MediaFile::factory()->create([
            'visibility' => 'public',
            'url' => 'change-vis.txt',
            'mime_type' => 'text/plain',
        ]);

        Storage::disk('public')->put('change-vis.txt', 'file content');

        $service = app(FileOperationService::class);
        $service->changeVisibility($file, 'private');

        $file->refresh();

        expect($file->visibility)->toBe('private');
    });

    it('changes file visibility from private to public', function () {
        $file = MediaFile::factory()->private()->create([
            'url' => 'make-public.txt',
            'mime_type' => 'text/plain',
        ]);

        Storage::disk('local')->put('make-public.txt', 'file content');

        $service = app(FileOperationService::class);
        $service->changeVisibility($file, 'public');

        $file->refresh();

        expect($file->visibility)->toBe('public');
    });

    it('does nothing when visibility is already the same', function () {
        $file = MediaFile::factory()->create([
            'visibility' => 'public',
            'url' => 'same-vis.txt',
            'mime_type' => 'text/plain',
        ]);

        Storage::disk('public')->put('same-vis.txt', 'file content');

        $service = app(FileOperationService::class);
        $service->changeVisibility($file, 'public');

        $file->refresh();

        expect($file->visibility)->toBe('public')
            ->and(Storage::disk('public')->exists('same-vis.txt'))->toBeTrue();
    });

    it('moves file from public to local disk when changing to private', function () {
        $file = MediaFile::factory()->create([
            'visibility' => 'public',
            'url' => 'move-to-private.txt',
            'mime_type' => 'text/plain',
        ]);

        Storage::disk('public')->put('move-to-private.txt', 'secret content');

        $service = app(FileOperationService::class);
        $service->changeVisibility($file, 'private');

        expect(Storage::disk('local')->exists('move-to-private.txt'))->toBeTrue()
            ->and(Storage::disk('public')->exists('move-to-private.txt'))->toBeFalse()
            ->and(Storage::disk('local')->get('move-to-private.txt'))->toBe('secret content');
    });

    it('moves file from local to public disk when changing to public', function () {
        $file = MediaFile::factory()->private()->create([
            'url' => 'move-to-public.txt',
            'mime_type' => 'text/plain',
        ]);

        Storage::disk('local')->put('move-to-public.txt', 'public content');

        $service = app(FileOperationService::class);
        $service->changeVisibility($file, 'public');

        expect(Storage::disk('public')->exists('move-to-public.txt'))->toBeTrue()
            ->and(Storage::disk('local')->exists('move-to-public.txt'))->toBeFalse()
            ->and(Storage::disk('public')->get('move-to-public.txt'))->toBe('public content');
    });

    it('moves thumbnails along with the file when changing visibility', function () {
        $file = MediaFile::factory()->create([
            'visibility' => 'public',
            'url' => 'image-vis.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        Storage::disk('public')->put('image-vis.jpg', 'image data');
        Storage::disk('public')->put('image-vis-150x150.jpg', 'thumb data');

        $service = app(FileOperationService::class);
        $service->changeVisibility($file, 'private');

        expect(Storage::disk('local')->exists('image-vis.jpg'))->toBeTrue()
            ->and(Storage::disk('local')->exists('image-vis-150x150.jpg'))->toBeTrue()
            ->and(Storage::disk('public')->exists('image-vis.jpg'))->toBeFalse()
            ->and(Storage::disk('public')->exists('image-vis-150x150.jpg'))->toBeFalse();
    });

    it('handles file in subdirectory correctly', function () {
        $folder = MediaFolder::factory()->create([
            'name' => 'photos',
            'slug' => 'photos',
        ]);

        $file = MediaFile::factory()->create([
            'visibility' => 'public',
            'url' => 'photos/my-photo.jpg',
            'mime_type' => 'image/jpeg',
            'folder_id' => $folder->id,
        ]);

        Storage::disk('public')->put('photos/my-photo.jpg', 'photo data');
        Storage::disk('public')->put('photos/my-photo-150x150.jpg', 'thumb data');

        $service = app(FileOperationService::class);
        $service->changeVisibility($file, 'private');

        expect(Storage::disk('local')->exists('photos/my-photo.jpg'))->toBeTrue()
            ->and(Storage::disk('local')->exists('photos/my-photo-150x150.jpg'))->toBeTrue()
            ->and(Storage::disk('public')->exists('photos/my-photo.jpg'))->toBeFalse();
    });

    it('saves the updated visibility to the database', function () {
        $file = MediaFile::factory()->create([
            'visibility' => 'public',
            'url' => 'db-check.txt',
            'mime_type' => 'text/plain',
        ]);

        Storage::disk('public')->put('db-check.txt', 'content');

        $service = app(FileOperationService::class);
        $service->changeVisibility($file, 'private');

        $freshFile = MediaFile::withTrashed()->find($file->id);

        expect($freshFile->visibility)->toBe('private');
    });

    it('throws exception when source file does not exist', function () {
        $file = MediaFile::factory()->create([
            'visibility' => 'public',
            'url' => 'nonexistent.txt',
            'mime_type' => 'text/plain',
        ]);

        $service = app(FileOperationService::class);

        expect(fn () => $service->changeVisibility($file, 'private'))
            ->toThrow(RuntimeException::class);
    });
});
