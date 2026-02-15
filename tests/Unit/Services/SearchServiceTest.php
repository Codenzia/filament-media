<?php

use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaTag;
use Codenzia\FilamentMedia\Services\SearchService;

beforeEach(function () {
    MediaFile::withoutGlobalScopes();
    $this->service = app(SearchService::class);
});

describe('SearchService', function () {
    describe('search', function () {
        it('returns files matching the query by name', function () {
            MediaFile::factory()->create(['name' => 'sunset-photo']);
            MediaFile::factory()->create(['name' => 'mountain-landscape']);
            MediaFile::factory()->create(['name' => 'sunset-beach']);

            $results = $this->service->search('sunset');

            expect($results)->toHaveCount(2)
                ->and($results->pluck('name')->toArray())->each->toContain('sunset');
        });

        it('returns empty collection when no files match', function () {
            MediaFile::factory()->create(['name' => 'photo-one']);

            $results = $this->service->search('nonexistent-term');

            expect($results)->toHaveCount(0);
        });
    });

    describe('searchByTag', function () {
        it('returns files that have a matching tag', function () {
            $file1 = MediaFile::factory()->create(['name' => 'tagged-file']);
            $file2 = MediaFile::factory()->create(['name' => 'untagged-file']);

            $tag = MediaTag::findOrCreateByName('wildlife');
            $file1->tags()->attach($tag->id);

            $results = $this->service->searchByTag('wildlife');

            expect($results)->toHaveCount(1)
                ->and($results->first()->name)->toBe('tagged-file');
        });

        it('supports partial tag name matching', function () {
            $file = MediaFile::factory()->create();
            $tag = MediaTag::findOrCreateByName('photography');
            $file->tags()->attach($tag->id);

            $results = $this->service->searchByTag('photo');

            expect($results)->toHaveCount(1);
        });
    });

    describe('advancedSearch', function () {
        it('filters by name criterion', function () {
            MediaFile::factory()->create(['name' => 'annual-report']);
            MediaFile::factory()->create(['name' => 'meeting-notes']);

            $results = $this->service->advancedSearch(['name' => 'report']);

            expect($results)->toHaveCount(1)
                ->and($results->first()->name)->toBe('annual-report');
        });

        it('filters by type criterion', function () {
            MediaFile::factory()->create(['name' => 'photo', 'mime_type' => 'image/jpeg']);
            MediaFile::factory()->create(['name' => 'video', 'mime_type' => 'video/mp4']);
            MediaFile::factory()->create(['name' => 'another-photo', 'mime_type' => 'image/png']);

            $results = $this->service->advancedSearch(['type' => 'image']);

            expect($results)->toHaveCount(2)
                ->and($results->pluck('mime_type')->toArray())->each->toStartWith('image/');
        });

        it('filters by date range', function () {
            MediaFile::factory()->create([
                'name' => 'old-file',
                'created_at' => '2024-01-15 10:00:00',
            ]);
            MediaFile::factory()->create([
                'name' => 'recent-file',
                'created_at' => '2025-06-15 10:00:00',
            ]);
            MediaFile::factory()->create([
                'name' => 'future-file',
                'created_at' => '2025-12-01 10:00:00',
            ]);

            $results = $this->service->advancedSearch([
                'date_from' => '2025-01-01',
                'date_to' => '2025-06-30',
            ]);

            expect($results)->toHaveCount(1)
                ->and($results->first()->name)->toBe('recent-file');
        });
    });

    describe('isScoutEnabled', function () {
        it('returns false when search driver is database', function () {
            config()->set('media.search.driver', 'database');

            $result = $this->service->isScoutEnabled();

            expect($result)->toBeFalse();
        });

        it('returns false when Scout class is not installed', function () {
            // Default config uses 'database' driver, and Scout is not installed in test env
            $result = $this->service->isScoutEnabled();

            expect($result)->toBeFalse();
        });
    });
});
