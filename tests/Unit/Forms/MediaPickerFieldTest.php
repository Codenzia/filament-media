<?php

use Codenzia\FilamentMedia\Forms\MediaPickerField;

describe('MediaPickerField', function () {
    it('can be created', function () {
        $field = MediaPickerField::make('media');

        expect($field)->toBeInstanceOf(MediaPickerField::class);
    });

    it('defaults to single selection', function () {
        $field = MediaPickerField::make('media');

        expect($field->isMultiple())->toBeFalse();
    });

    it('defaults to no file type restrictions', function () {
        $field = MediaPickerField::make('media');

        expect($field->getAcceptedFileTypes())->toBe([]);
    });

    it('defaults to no max files limit', function () {
        $field = MediaPickerField::make('media');

        expect($field->getMaxFiles())->toBe(0);
    });
});

describe('MediaPickerField multiple()', function () {
    it('sets isMultiple to true', function () {
        $field = MediaPickerField::make('media')->multiple();

        expect($field->isMultiple())->toBeTrue();
    });

    it('can be toggled back to false', function () {
        $field = MediaPickerField::make('media')->multiple()->multiple(false);

        expect($field->isMultiple())->toBeFalse();
    });
});

describe('MediaPickerField file type filters', function () {
    it('imageOnly sets accepted file types to image wildcard', function () {
        $field = MediaPickerField::make('media')->imageOnly();

        expect($field->getAcceptedFileTypes())->toBe(['image/*']);
    });

    it('videoOnly sets accepted file types to video wildcard', function () {
        $field = MediaPickerField::make('media')->videoOnly();

        expect($field->getAcceptedFileTypes())->toBe(['video/*']);
    });

    it('documentOnly sets accepted file types to document mime types', function () {
        $field = MediaPickerField::make('media')->documentOnly();

        $types = $field->getAcceptedFileTypes();

        expect($types)->toBeArray()
            ->and($types)->toContain('application/pdf')
            ->and($types)->toContain('application/msword')
            ->and($types)->toContain('application/vnd.openxmlformats-officedocument.wordprocessingml.document')
            ->and($types)->toContain('application/vnd.ms-excel')
            ->and($types)->toContain('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->and($types)->toContain('text/plain')
            ->and($types)->toContain('text/csv');
    });

    it('acceptedFileTypes sets custom types', function () {
        $customTypes = ['image/svg+xml', 'image/webp'];
        $field = MediaPickerField::make('media')->acceptedFileTypes($customTypes);

        expect($field->getAcceptedFileTypes())->toBe($customTypes);
    });
});

describe('MediaPickerField maxFiles()', function () {
    it('sets max files limit', function () {
        $field = MediaPickerField::make('media')->maxFiles(5);

        expect($field->getMaxFiles())->toBe(5);
    });

    it('can set max files to 1', function () {
        $field = MediaPickerField::make('media')->maxFiles(1);

        expect($field->getMaxFiles())->toBe(1);
    });
});

describe('MediaPickerField directory and collection', function () {
    it('sets directory', function () {
        $field = MediaPickerField::make('media')->directory('photos');

        expect($field->getDirectory())->toBe('photos');
    });

    it('sets collection', function () {
        $field = MediaPickerField::make('media')->collection('hero-banners');

        expect($field->getCollection())->toBe('hero-banners');
    });

    it('defaults directory to null', function () {
        $field = MediaPickerField::make('media');

        expect($field->getDirectory())->toBeNull();
    });

    it('defaults collection to null', function () {
        $field = MediaPickerField::make('media');

        expect($field->getCollection())->toBeNull();
    });
});

describe('MediaPickerField fluent interface', function () {
    it('supports method chaining', function () {
        $field = MediaPickerField::make('media')
            ->multiple()
            ->imageOnly()
            ->maxFiles(10)
            ->directory('images')
            ->collection('gallery');

        expect($field)->toBeInstanceOf(MediaPickerField::class)
            ->and($field->isMultiple())->toBeTrue()
            ->and($field->getAcceptedFileTypes())->toBe(['image/*'])
            ->and($field->getMaxFiles())->toBe(10)
            ->and($field->getDirectory())->toBe('images')
            ->and($field->getCollection())->toBe('gallery');
    });
});
