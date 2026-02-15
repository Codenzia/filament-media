<?php

use Codenzia\FilamentMedia\Models\MediaMetadataField;

describe('MediaMetadataField Model', function () {
    it('can be created with type text', function () {
        $field = MediaMetadataField::create([
            'name' => 'Copyright',
            'slug' => 'copyright',
            'type' => 'text',
            'is_required' => false,
            'is_searchable' => true,
            'sort_order' => 1,
        ]);

        expect($field)->toBeInstanceOf(MediaMetadataField::class)
            ->and($field->exists)->toBeTrue()
            ->and($field->name)->toBe('Copyright')
            ->and($field->slug)->toBe('copyright')
            ->and($field->type)->toBe('text')
            ->and($field->is_required)->toBeFalse()
            ->and($field->is_searchable)->toBeTrue()
            ->and($field->sort_order)->toBe(1);
    });

    it('can be created with type select and options', function () {
        $options = ['landscape', 'portrait', 'square'];

        $field = MediaMetadataField::create([
            'name' => 'Orientation',
            'slug' => 'orientation',
            'type' => 'select',
            'options' => $options,
            'is_required' => true,
            'is_searchable' => true,
            'sort_order' => 2,
        ]);

        $field->refresh();

        expect($field->type)->toBe('select')
            ->and($field->options)->toBeArray()
            ->and($field->options)->toBe($options)
            ->and($field->is_required)->toBeTrue();
    });

    it('auto-generates slug from name on creation', function () {
        $field = MediaMetadataField::create([
            'name' => 'Photo Credit',
            'type' => 'text',
        ]);

        expect($field->slug)->toBe('photo-credit');
    });

    it('does not overwrite a manually set slug', function () {
        $field = MediaMetadataField::create([
            'name' => 'Photo Credit',
            'slug' => 'custom-slug',
            'type' => 'text',
        ]);

        expect($field->slug)->toBe('custom-slug');
    });

    it('casts boolean fields correctly', function () {
        $field = MediaMetadataField::create([
            'name' => 'Boolean Test',
            'slug' => 'boolean-test',
            'type' => 'text',
            'is_required' => 1,
            'is_searchable' => 0,
        ]);

        expect($field->is_required)->toBeBool()->toBeTrue()
            ->and($field->is_searchable)->toBeBool()->toBeFalse();
    });
});

describe('MediaMetadataField Scopes', function () {
    it('scopeOrdered returns fields sorted by sort_order', function () {
        MediaMetadataField::create(['name' => 'Third', 'slug' => 'third', 'type' => 'text', 'sort_order' => 30]);
        MediaMetadataField::create(['name' => 'First', 'slug' => 'first', 'type' => 'text', 'sort_order' => 10]);
        MediaMetadataField::create(['name' => 'Second', 'slug' => 'second', 'type' => 'text', 'sort_order' => 20]);

        $fields = MediaMetadataField::ordered()->get();

        expect($fields->first()->name)->toBe('First')
            ->and($fields->get(1)->name)->toBe('Second')
            ->and($fields->last()->name)->toBe('Third');
    });

    it('scopeSearchable only returns searchable fields', function () {
        MediaMetadataField::create([
            'name' => 'Searchable Field',
            'slug' => 'searchable-field',
            'type' => 'text',
            'is_searchable' => true,
        ]);

        MediaMetadataField::create([
            'name' => 'Non-Searchable Field',
            'slug' => 'non-searchable-field',
            'type' => 'text',
            'is_searchable' => false,
        ]);

        MediaMetadataField::create([
            'name' => 'Another Searchable',
            'slug' => 'another-searchable',
            'type' => 'select',
            'is_searchable' => true,
        ]);

        $searchable = MediaMetadataField::searchable()->get();

        expect($searchable)->toHaveCount(2)
            ->and($searchable->pluck('is_searchable')->unique()->toArray())->toBe([true]);
    });

    it('can chain scopes together', function () {
        MediaMetadataField::create([
            'name' => 'B Field',
            'slug' => 'b-field',
            'type' => 'text',
            'is_searchable' => true,
            'sort_order' => 20,
        ]);

        MediaMetadataField::create([
            'name' => 'A Field',
            'slug' => 'a-field',
            'type' => 'text',
            'is_searchable' => true,
            'sort_order' => 10,
        ]);

        MediaMetadataField::create([
            'name' => 'C Field Not Searchable',
            'slug' => 'c-field',
            'type' => 'text',
            'is_searchable' => false,
            'sort_order' => 5,
        ]);

        $results = MediaMetadataField::searchable()->ordered()->get();

        expect($results)->toHaveCount(2)
            ->and($results->first()->name)->toBe('A Field')
            ->and($results->last()->name)->toBe('B Field');
    });
});

describe('MediaMetadataField Relations', function () {
    it('has a files relation', function () {
        $field = MediaMetadataField::create([
            'name' => 'Location',
            'slug' => 'location',
            'type' => 'text',
        ]);

        expect($field->files())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
    });
});
