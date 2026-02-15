<?php

use Codenzia\FilamentMedia\Models\MediaTag;
use Codenzia\FilamentMedia\Models\MediaFile;

beforeEach(function () {
    MediaFile::withoutGlobalScopes();
});

describe('MediaTag Model', function () {
    it('can create a tag', function () {
        $tag = MediaTag::create([
            'name' => 'Nature',
            'slug' => 'nature',
            'type' => 'tag',
        ]);

        expect($tag)->toBeInstanceOf(MediaTag::class)
            ->and($tag->exists)->toBeTrue()
            ->and($tag->name)->toBe('Nature')
            ->and($tag->slug)->toBe('nature')
            ->and($tag->type)->toBe('tag');
    });

    it('can create a collection', function () {
        $collection = MediaTag::create([
            'name' => 'Hero Images',
            'slug' => 'hero-images',
            'type' => 'collection',
        ]);

        expect($collection)->toBeInstanceOf(MediaTag::class)
            ->and($collection->exists)->toBeTrue()
            ->and($collection->type)->toBe('collection');
    });
});

describe('MediaTag findOrCreateByName', function () {
    it('creates a new tag when it does not exist', function () {
        $tag = MediaTag::findOrCreateByName('Landscape');

        expect($tag)->toBeInstanceOf(MediaTag::class)
            ->and($tag->name)->toBe('Landscape')
            ->and($tag->slug)->toBe('landscape')
            ->and($tag->type)->toBe('tag');
    });

    it('returns an existing tag when it already exists', function () {
        $existing = MediaTag::create([
            'name' => 'Portrait',
            'slug' => 'portrait',
            'type' => 'tag',
        ]);

        $found = MediaTag::findOrCreateByName('Portrait');

        expect($found->id)->toBe($existing->id)
            ->and(MediaTag::where('slug', 'portrait')->count())->toBe(1);
    });

    it('creates a collection when type is collection', function () {
        $collection = MediaTag::findOrCreateByName('Banner Images', 'collection');

        expect($collection->type)->toBe('collection')
            ->and($collection->slug)->toBe('banner-images');
    });
});

describe('MediaTag Scopes', function () {
    it('scopeTags only returns tags', function () {
        MediaTag::create(['name' => 'Tag One', 'slug' => 'tag-one', 'type' => 'tag']);
        MediaTag::create(['name' => 'Tag Two', 'slug' => 'tag-two', 'type' => 'tag']);
        MediaTag::create(['name' => 'Collection One', 'slug' => 'collection-one', 'type' => 'collection']);

        $tags = MediaTag::tags()->get();

        expect($tags)->toHaveCount(2)
            ->and($tags->pluck('type')->unique()->toArray())->toBe(['tag']);
    });

    it('scopeCollections only returns collections', function () {
        MediaTag::create(['name' => 'Tag One', 'slug' => 'tag-one', 'type' => 'tag']);
        MediaTag::create(['name' => 'Collection One', 'slug' => 'collection-one', 'type' => 'collection']);
        MediaTag::create(['name' => 'Collection Two', 'slug' => 'collection-two', 'type' => 'collection']);

        $collections = MediaTag::collections()->get();

        expect($collections)->toHaveCount(2)
            ->and($collections->pluck('type')->unique()->toArray())->toBe(['collection']);
    });
});

describe('MediaTag Relations', function () {
    it('has a files relation that returns media files', function () {
        $tag = MediaTag::create(['name' => 'Photos', 'slug' => 'photos', 'type' => 'tag']);
        $file1 = MediaFile::factory()->create();
        $file2 = MediaFile::factory()->create();

        $tag->files()->attach([$file1->id, $file2->id]);

        expect($tag->files)->toHaveCount(2)
            ->and($tag->files->first())->toBeInstanceOf(MediaFile::class);
    });

    it('has a parent relation', function () {
        $parent = MediaTag::create(['name' => 'Parent', 'slug' => 'parent', 'type' => 'tag']);
        $child = MediaTag::create([
            'name' => 'Child',
            'slug' => 'child',
            'type' => 'tag',
            'parent_id' => $parent->id,
        ]);

        expect($child->parent)->toBeInstanceOf(MediaTag::class)
            ->and($child->parent->id)->toBe($parent->id);
    });

    it('has a children relation', function () {
        $parent = MediaTag::create(['name' => 'Parent', 'slug' => 'parent-tag', 'type' => 'tag']);
        MediaTag::create(['name' => 'Child 1', 'slug' => 'child-1', 'type' => 'tag', 'parent_id' => $parent->id]);
        MediaTag::create(['name' => 'Child 2', 'slug' => 'child-2', 'type' => 'tag', 'parent_id' => $parent->id]);

        expect($parent->children)->toHaveCount(2);
    });
});

describe('MediaTag createSlug', function () {
    it('generates a slug from a name', function () {
        $slug = MediaTag::createSlug('My New Tag');

        expect($slug)->toBe('my-new-tag');
    });

    it('generates a unique slug when duplicate exists', function () {
        MediaTag::create(['name' => 'Travel', 'slug' => 'travel', 'type' => 'tag']);

        $slug = MediaTag::createSlug('Travel');

        expect($slug)->toBe('travel-1');
    });

    it('increments slug counter for multiple duplicates', function () {
        MediaTag::create(['name' => 'Travel', 'slug' => 'travel', 'type' => 'tag']);
        MediaTag::create(['name' => 'Travel', 'slug' => 'travel-1', 'type' => 'tag']);

        $slug = MediaTag::createSlug('Travel');

        expect($slug)->toBe('travel-2');
    });
});
