<?php

use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Services\OrphanScanService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    MediaFile::withoutGlobalScopes();
    Storage::fake('public');
});

describe('OrphanScanService - scan', function () {
    it('returns empty collection when all files are tracked', function () {
        Storage::put('photo.jpg', 'image data');

        MediaFile::factory()->create([
            'name' => 'photo.jpg',
            'url' => 'photo.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        $service = app(OrphanScanService::class);
        $orphans = $service->scan();

        expect($orphans)->toBeEmpty();
    });

    it('finds files on disk that are not in the database', function () {
        Storage::put('tracked.jpg', 'image data');
        Storage::put('orphan.pdf', 'pdf data');
        Storage::put('another-orphan.txt', 'text data');

        MediaFile::factory()->create([
            'name' => 'tracked.jpg',
            'url' => 'tracked.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        $service = app(OrphanScanService::class);
        $orphans = $service->scan();

        expect($orphans)->toHaveCount(2);

        $paths = $orphans->pluck('path')->toArray();
        expect($paths)->toContain('orphan.pdf')
            ->toContain('another-orphan.txt');
    });

    it('skips hidden files and system files', function () {
        Storage::put('.gitignore', 'node_modules');
        Storage::put('.DS_Store', 'data');
        Storage::put('.hidden-file', 'hidden');
        Storage::put('real-file.jpg', 'image data');

        $service = app(OrphanScanService::class);
        $orphans = $service->scan();

        expect($orphans)->toHaveCount(1);
        expect($orphans->first()['name'])->toBe('real-file.jpg');
    });

    it('includes file metadata in scan results', function () {
        Storage::put('document.pdf', 'pdf content here');

        $service = app(OrphanScanService::class);
        $orphans = $service->scan();

        expect($orphans)->toHaveCount(1);

        $file = $orphans->first();
        expect($file)->toHaveKeys(['path', 'name', 'size', 'mime_type', 'last_modified'])
            ->and($file['name'])->toBe('document.pdf')
            ->and($file['path'])->toBe('document.pdf')
            ->and($file['size'])->toBeGreaterThan(0);
    });

    it('scans subdirectories', function () {
        Storage::makeDirectory('subfolder');
        Storage::put('subfolder/nested.txt', 'nested content');

        $service = app(OrphanScanService::class);
        $orphans = $service->scan();

        expect($orphans)->toHaveCount(1);
        expect($orphans->first()['path'])->toBe('subfolder/nested.txt');
    });

    it('does not include soft-deleted files as orphans', function () {
        Storage::put('deleted-file.jpg', 'image data');

        $file = MediaFile::factory()->create([
            'name' => 'deleted-file.jpg',
            'url' => 'deleted-file.jpg',
            'mime_type' => 'image/jpeg',
        ]);
        $file->delete();

        $service = app(OrphanScanService::class);
        $orphans = $service->scan();

        expect($orphans)->toBeEmpty();
    });
});

describe('OrphanScanService - import', function () {
    it('creates database records for orphaned files', function () {
        Storage::put('orphan.jpg', 'image data');
        Storage::put('orphan.pdf', 'pdf data');

        $service = app(OrphanScanService::class);
        $imported = $service->import(['orphan.jpg', 'orphan.pdf'], 0, 1);

        expect($imported)->toBe(2);
        expect(MediaFile::count())->toBe(2);

        $jpg = MediaFile::where('url', 'orphan.jpg')->first();
        expect($jpg->name)->toBe('orphan.jpg')
            ->and($jpg->folder_id)->toBe(0)
            ->and($jpg->user_id)->toBe(1);
    });

    it('skips files that no longer exist on disk', function () {
        $service = app(OrphanScanService::class);
        $imported = $service->import(['nonexistent.jpg'], 0, 1);

        expect($imported)->toBe(0);
        expect(MediaFile::count())->toBe(0);
    });

    it('skips files already tracked in the database', function () {
        Storage::put('existing.jpg', 'image data');

        MediaFile::factory()->create([
            'name' => 'existing.jpg',
            'url' => 'existing.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        $service = app(OrphanScanService::class);
        $imported = $service->import(['existing.jpg'], 0, 1);

        expect($imported)->toBe(0);
        expect(MediaFile::count())->toBe(1);
    });

    it('assigns the correct folder id', function () {
        Storage::put('file.txt', 'content');

        $service = app(OrphanScanService::class);
        $service->import(['file.txt'], 42, 1);

        $file = MediaFile::where('url', 'file.txt')->first();
        expect($file->folder_id)->toBe(42);
    });
});

describe('OrphanScanService - delete', function () {
    it('deletes orphaned files from disk', function () {
        Storage::put('orphan1.txt', 'data');
        Storage::put('orphan2.txt', 'data');

        $service = app(OrphanScanService::class);
        $deleted = $service->delete(['orphan1.txt', 'orphan2.txt']);

        expect($deleted)->toBe(2);
        expect(Storage::exists('orphan1.txt'))->toBeFalse();
        expect(Storage::exists('orphan2.txt'))->toBeFalse();
    });

    it('does not delete files that are tracked in the database', function () {
        Storage::put('tracked.jpg', 'image data');

        MediaFile::factory()->create([
            'name' => 'tracked.jpg',
            'url' => 'tracked.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        $service = app(OrphanScanService::class);
        $deleted = $service->delete(['tracked.jpg']);

        expect($deleted)->toBe(0);
        expect(Storage::exists('tracked.jpg'))->toBeTrue();
    });

    it('handles nonexistent files gracefully', function () {
        $service = app(OrphanScanService::class);
        $deleted = $service->delete(['nonexistent.txt']);

        expect($deleted)->toBe(0);
    });
});
