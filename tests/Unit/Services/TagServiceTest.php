<?php

use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaTag;
use Codenzia\FilamentMedia\Services\TagService;

beforeEach(function () {
    MediaFile::withoutGlobalScopes();
    $this->service = app(TagService::class);
});

describe('TagService', function () {
    describe('findOrCreate', function () {
        it('creates a new tag when it does not exist', function () {
            $tag = $this->service->findOrCreate('Nature');

            expect($tag)->toBeInstanceOf(MediaTag::class)
                ->and($tag->name)->toBe('Nature')
                ->and($tag->slug)->toBe('nature')
                ->and($tag->type)->toBe('tag')
                ->and($tag->exists)->toBeTrue();
        });

        it('returns existing tag when one already exists', function () {
            $original = $this->service->findOrCreate('Landscape');
            $found = $this->service->findOrCreate('Landscape');

            expect($found->id)->toBe($original->id)
                ->and(MediaTag::where('slug', 'landscape')->count())->toBe(1);
        });
    });

    describe('attachTags', function () {
        it('attaches tags to a media file', function () {
            $file = MediaFile::factory()->create();

            $this->service->attachTags($file, ['Red', 'Blue']);

            $file->refresh();
            $tagNames = $file->tags->pluck('name')->sort()->values()->toArray();

            expect($tagNames)->toBe(['Blue', 'Red']);
        });

        it('does not duplicate tags when attaching the same names again', function () {
            $file = MediaFile::factory()->create();

            $this->service->attachTags($file, ['Alpha']);
            $this->service->attachTags($file, ['Alpha', 'Beta']);

            $file->refresh();

            expect($file->tags)->toHaveCount(2);
        });
    });

    describe('syncTags', function () {
        it('syncs tags on a file replacing previous associations', function () {
            $file = MediaFile::factory()->create();

            $this->service->attachTags($file, ['One', 'Two', 'Three']);
            $this->service->syncTags($file, ['Two', 'Four']);

            $file->refresh();
            $tagNames = $file->tags->pluck('name')->sort()->values()->toArray();

            expect($tagNames)->toBe(['Four', 'Two']);
        });
    });

    describe('detachTags', function () {
        it('removes specified tags from a file', function () {
            $file = MediaFile::factory()->create();

            $this->service->attachTags($file, ['Keep', 'Remove']);

            $file->refresh();
            $removeTag = $file->tags->firstWhere('name', 'Remove');

            $this->service->detachTags($file, [$removeTag->id]);

            $file->refresh();
            $tagNames = $file->tags->pluck('name')->toArray();

            expect($tagNames)->toBe(['Keep']);
        });
    });

    describe('getPopularTags', function () {
        it('returns tags ordered by file count descending', function () {
            $files = MediaFile::factory()->count(3)->create();

            $popular = $this->service->findOrCreate('Popular');
            $medium = $this->service->findOrCreate('Medium');
            $rare = $this->service->findOrCreate('Rare');

            // Attach Popular to 3 files, Medium to 2, Rare to 1
            foreach ($files as $file) {
                $file->tags()->attach($popular->id);
            }
            $files[0]->tags()->attach($medium->id);
            $files[1]->tags()->attach($medium->id);
            $files[0]->tags()->attach($rare->id);

            $result = $this->service->getPopularTags(10);

            expect($result->first()->name)->toBe('Popular')
                ->and($result->first()->files_count)->toBe(3)
                ->and($result[1]->name)->toBe('Medium')
                ->and($result[1]->files_count)->toBe(2)
                ->and($result->last()->name)->toBe('Rare')
                ->and($result->last()->files_count)->toBe(1);
        });
    });

    describe('createCollection', function () {
        it('creates a tag with type collection', function () {
            $collection = $this->service->createCollection('My Gallery', 'A curated gallery');

            expect($collection)->toBeInstanceOf(MediaTag::class)
                ->and($collection->name)->toBe('My Gallery')
                ->and($collection->type)->toBe('collection')
                ->and($collection->description)->toBe('A curated gallery')
                ->and($collection->exists)->toBeTrue();
        });
    });

    describe('addToCollection', function () {
        it('attaches files to a collection', function () {
            $collection = $this->service->createCollection('Album');
            $files = MediaFile::factory()->count(3)->create();
            $fileIds = $files->pluck('id')->toArray();

            $this->service->addToCollection($collection->id, $fileIds);

            $collection->refresh();

            expect($collection->files)->toHaveCount(3);
        });
    });

    describe('getCollections', function () {
        it('returns only collections ordered by name', function () {
            // Create regular tags and collections
            $this->service->findOrCreate('regular-tag');
            $this->service->createCollection('Zebra Collection');
            $this->service->createCollection('Alpha Collection');

            $collections = $this->service->getCollections();

            expect($collections)->toHaveCount(2)
                ->and($collections->first()->name)->toBe('Alpha Collection')
                ->and($collections->last()->name)->toBe('Zebra Collection')
                ->and($collections->every(fn ($c) => $c->type === 'collection'))->toBeTrue();
        });
    });
});
