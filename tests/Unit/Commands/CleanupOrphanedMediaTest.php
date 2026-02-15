<?php

use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaFolder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    MediaFile::withoutGlobalScopes();
    MediaFolder::withoutGlobalScopes();
});

describe('CleanupOrphanedMedia Command', function () {
    it('finds orphaned media entries', function () {
        MediaFile::factory()->create([
            'name' => 'missing-file',
            'url' => 'missing-file.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        $this->artisan('media:cleanup', ['--dry-run' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('1 orphaned');
    });

    it('does not delete entries when file exists on disk', function () {
        Storage::disk('public')->put('existing.jpg', 'image content');

        MediaFile::factory()->create([
            'name' => 'existing',
            'url' => 'existing.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        $this->artisan('media:cleanup', ['--force' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('No orphaned');

        expect(MediaFile::count())->toBe(1);
    });

    it('removes orphaned entries with force flag', function () {
        MediaFile::factory()->create([
            'name' => 'orphan-one',
            'url' => 'orphan-one.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        MediaFile::factory()->create([
            'name' => 'orphan-two',
            'url' => 'orphan-two.pdf',
            'mime_type' => 'application/pdf',
        ]);

        $this->artisan('media:cleanup', ['--force' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('deleted 2');

        expect(MediaFile::withTrashed()->count())->toBe(0);
    });

    it('dry run shows orphans without deleting', function () {
        MediaFile::factory()->create([
            'name' => 'orphaned',
            'url' => 'orphaned.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        $this->artisan('media:cleanup', ['--dry-run' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('Dry run complete');

        expect(MediaFile::count())->toBe(1);
    });

    it('returns success when no orphans found', function () {
        Storage::disk('public')->put('safe.jpg', 'image data');

        MediaFile::factory()->create([
            'name' => 'safe',
            'url' => 'safe.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        $this->artisan('media:cleanup', ['--force' => true])
            ->assertSuccessful()
            ->assertExitCode(0)
            ->expectsOutputToContain('No orphaned');
    });
});
