<?php

use Codenzia\FilamentMedia\Events\MediaFileVersionCreated;
use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaFileVersion;
use Codenzia\FilamentMedia\Services\MediaUrlService;
use Codenzia\FilamentMedia\Services\UploadService;
use Codenzia\FilamentMedia\Services\VersionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    MediaFile::withoutGlobalScopes();
    $this->service = app(VersionService::class);
});

describe('VersionService', function () {
    describe('createVersion', function () {
        it('snapshots current file state and creates a new version', function () {
            Event::fake([MediaFileVersionCreated::class]);

            $file = MediaFile::factory()->create([
                'url' => 'original.jpg',
                'size' => 5000,
                'mime_type' => 'image/jpeg',
            ]);

            // Mock the UploadService to avoid real file system operations
            $mockUpload = Mockery::mock(UploadService::class);
            $mockUpload->shouldReceive('handleUpload')->once()->andReturn(
                MediaFile::factory()->create([
                    'url' => 'new-version.png',
                    'size' => 8000,
                    'mime_type' => 'image/png',
                ])
            );

            $service = new VersionService($mockUpload, app(MediaUrlService::class));

            $uploadedFile = UploadedFile::fake()->image('new-version.png', 200, 200);
            $version = $service->createVersion($file, $uploadedFile, 'Updated image');

            expect($version)->toBeInstanceOf(MediaFileVersion::class)
                ->and($version->media_file_id)->toBe($file->id)
                ->and($version->version_number)->toBe(1)
                ->and($version->url)->toBe('original.jpg')
                ->and($version->size)->toBe(5000)
                ->and($version->mime_type)->toBe('image/jpeg')
                ->and($version->changelog)->toBe('Updated image');

            // The file should have been updated with new data
            $file->refresh();
            expect($file->url)->toBe('new-version.png')
                ->and($file->size)->toBe(8000)
                ->and($file->mime_type)->toBe('image/png');

            Event::assertDispatched(MediaFileVersionCreated::class);
        });
    });

    describe('getVersions', function () {
        it('returns version list for a file', function () {
            $file = MediaFile::factory()->create();

            MediaFileVersion::create([
                'media_file_id' => $file->id,
                'version_number' => 1,
                'url' => 'v1.jpg',
                'size' => 1000,
                'mime_type' => 'image/jpeg',
                'changelog' => 'First version',
            ]);
            MediaFileVersion::create([
                'media_file_id' => $file->id,
                'version_number' => 2,
                'url' => 'v2.jpg',
                'size' => 2000,
                'mime_type' => 'image/jpeg',
                'changelog' => 'Second version',
            ]);

            $versions = $this->service->getVersions($file);

            expect($versions)->toHaveCount(2)
                ->and($versions->first()->version_number)->toBe(2)
                ->and($versions->last()->version_number)->toBe(1);
        });

        it('returns empty collection when file has no versions', function () {
            $file = MediaFile::factory()->create();

            $versions = $this->service->getVersions($file);

            expect($versions)->toHaveCount(0);
        });
    });

    describe('revertToVersion', function () {
        it('creates a snapshot of current state and reverts to specified version', function () {
            $file = MediaFile::factory()->create([
                'url' => 'current.jpg',
                'size' => 5000,
                'mime_type' => 'image/jpeg',
            ]);

            $oldVersion = MediaFileVersion::create([
                'media_file_id' => $file->id,
                'version_number' => 1,
                'url' => 'old-version.jpg',
                'size' => 3000,
                'mime_type' => 'image/png',
                'changelog' => 'Original upload',
            ]);

            $result = $this->service->revertToVersion($file, $oldVersion->id);

            // File should be reverted to the old version's data
            expect($result->url)->toBe('old-version.jpg')
                ->and($result->size)->toBe(3000)
                ->and($result->mime_type)->toBe('image/png');

            // A new snapshot version should have been created for the current state
            $versions = MediaFileVersion::where('media_file_id', $file->id)
                ->orderBy('version_number')
                ->get();
            expect($versions)->toHaveCount(2);

            // Version 2 is the snapshot of the state before reverting
            $snapshotVersion = $versions->firstWhere('version_number', 2);
            expect($snapshotVersion)->not->toBeNull()
                ->and($snapshotVersion->url)->toBe('current.jpg')
                ->and($snapshotVersion->size)->toBe(5000)
                ->and($snapshotVersion->changelog)->toContain('Reverted to version 1');
        });
    });

    describe('deleteVersion', function () {
        it('removes a version from the database', function () {
            $file = MediaFile::factory()->create();

            $version = MediaFileVersion::create([
                'media_file_id' => $file->id,
                'version_number' => 1,
                'url' => 'v1.jpg',
                'size' => 1000,
                'mime_type' => 'image/jpeg',
            ]);

            $versionId = $version->id;
            $result = $this->service->deleteVersion($versionId);

            expect($result)->toBeTrue()
                ->and(MediaFileVersion::find($versionId))->toBeNull();
        });
    });

    describe('pruneOldVersions', function () {
        it('keeps only the specified number of most recent versions', function () {
            $file = MediaFile::factory()->create();

            // Create 5 versions
            for ($i = 1; $i <= 5; $i++) {
                MediaFileVersion::create([
                    'media_file_id' => $file->id,
                    'version_number' => $i,
                    'url' => "v{$i}.jpg",
                    'size' => $i * 1000,
                    'mime_type' => 'image/jpeg',
                ]);
            }

            $deleted = $this->service->pruneOldVersions($file, 2);

            expect($deleted)->toBe(3);

            $remaining = $file->versions()->orderByDesc('version_number')->get();
            expect($remaining)->toHaveCount(2)
                ->and($remaining->first()->version_number)->toBe(5)
                ->and($remaining->last()->version_number)->toBe(4);
        });

        it('returns zero when version count is within the keep limit', function () {
            $file = MediaFile::factory()->create();

            MediaFileVersion::create([
                'media_file_id' => $file->id,
                'version_number' => 1,
                'url' => 'v1.jpg',
                'size' => 1000,
                'mime_type' => 'image/jpeg',
            ]);

            $deleted = $this->service->pruneOldVersions($file, 5);

            expect($deleted)->toBe(0)
                ->and($file->versions()->count())->toBe(1);
        });
    });
});
