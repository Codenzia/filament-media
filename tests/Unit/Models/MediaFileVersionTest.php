<?php

use Codenzia\FilamentMedia\Models\MediaFileVersion;
use Codenzia\FilamentMedia\Models\MediaFile;

beforeEach(function () {
    MediaFile::withoutGlobalScopes();
});

describe('MediaFileVersion Model', function () {
    it('can be created with valid attributes', function () {
        $file = MediaFile::factory()->create();

        $version = MediaFileVersion::create([
            'media_file_id' => $file->id,
            'version_number' => 1,
            'url' => 'uploads/test-v1.jpg',
            'size' => 2048,
            'mime_type' => 'image/jpeg',
            'changelog' => 'Initial version',
        ]);

        expect($version)->toBeInstanceOf(MediaFileVersion::class)
            ->and($version->exists)->toBeTrue()
            ->and($version->version_number)->toBe(1)
            ->and($version->url)->toBe('uploads/test-v1.jpg')
            ->and($version->size)->toBe(2048)
            ->and($version->mime_type)->toBe('image/jpeg')
            ->and($version->changelog)->toBe('Initial version');
    });

    it('casts version_number to integer', function () {
        $file = MediaFile::factory()->create();

        $version = MediaFileVersion::create([
            'media_file_id' => $file->id,
            'version_number' => 3,
            'url' => 'uploads/test-v3.jpg',
            'size' => 1024,
            'mime_type' => 'image/png',
        ]);

        expect($version->version_number)->toBeInt();
    });

    it('casts size to integer', function () {
        $file = MediaFile::factory()->create();

        $version = MediaFileVersion::create([
            'media_file_id' => $file->id,
            'version_number' => 1,
            'url' => 'uploads/test.jpg',
            'size' => 5120,
            'mime_type' => 'image/jpeg',
        ]);

        expect($version->size)->toBeInt();
    });
});

describe('MediaFileVersion Relations', function () {
    it('belongs to a media file', function () {
        $file = MediaFile::factory()->create();

        $version = MediaFileVersion::create([
            'media_file_id' => $file->id,
            'version_number' => 1,
            'url' => 'uploads/test-v1.jpg',
            'size' => 2048,
            'mime_type' => 'image/jpeg',
        ]);

        expect($version->file)->toBeInstanceOf(MediaFile::class)
            ->and($version->file->id)->toBe($file->id);
    });
});

describe('MediaFileVersion Scopes', function () {
    it('scopeLatestVersion returns versions ordered by version_number descending', function () {
        $file = MediaFile::factory()->create();

        MediaFileVersion::create([
            'media_file_id' => $file->id,
            'version_number' => 1,
            'url' => 'uploads/test-v1.jpg',
            'size' => 1024,
            'mime_type' => 'image/jpeg',
        ]);

        MediaFileVersion::create([
            'media_file_id' => $file->id,
            'version_number' => 3,
            'url' => 'uploads/test-v3.jpg',
            'size' => 3072,
            'mime_type' => 'image/jpeg',
        ]);

        MediaFileVersion::create([
            'media_file_id' => $file->id,
            'version_number' => 2,
            'url' => 'uploads/test-v2.jpg',
            'size' => 2048,
            'mime_type' => 'image/jpeg',
        ]);

        $versions = MediaFileVersion::latestVersion()->get();

        expect($versions->first()->version_number)->toBe(3)
            ->and($versions->last()->version_number)->toBe(1);
    });

    it('scopeForFile scopes to a specific file', function () {
        $file1 = MediaFile::factory()->create();
        $file2 = MediaFile::factory()->create();

        MediaFileVersion::create([
            'media_file_id' => $file1->id,
            'version_number' => 1,
            'url' => 'uploads/file1-v1.jpg',
            'size' => 1024,
            'mime_type' => 'image/jpeg',
        ]);

        MediaFileVersion::create([
            'media_file_id' => $file1->id,
            'version_number' => 2,
            'url' => 'uploads/file1-v2.jpg',
            'size' => 2048,
            'mime_type' => 'image/jpeg',
        ]);

        MediaFileVersion::create([
            'media_file_id' => $file2->id,
            'version_number' => 1,
            'url' => 'uploads/file2-v1.jpg',
            'size' => 512,
            'mime_type' => 'image/png',
        ]);

        $file1Versions = MediaFileVersion::forFile($file1->id)->get();
        $file2Versions = MediaFileVersion::forFile($file2->id)->get();

        expect($file1Versions)->toHaveCount(2)
            ->and($file2Versions)->toHaveCount(1)
            ->and($file1Versions->pluck('media_file_id')->unique()->first())->toBe($file1->id);
    });
});
