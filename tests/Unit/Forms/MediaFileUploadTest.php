<?php

use Codenzia\FilamentMedia\Forms\MediaFileUpload;
use Codenzia\FilamentMedia\Services\StorageDriverService;
use Codenzia\FilamentMedia\Services\UploadService;
use Filament\Forms\Components\FileUpload;

describe('MediaFileUpload', function () {
    it('returns a FileUpload instance', function () {
        $field = MediaFileUpload::make();

        expect($field)->toBeInstanceOf(FileUpload::class);
    });

    it('sets the field name to url', function () {
        $field = MediaFileUpload::make();

        expect($field->getName())->toBe('url');
    });

    it('sets the directory when provided', function () {
        $field = MediaFileUpload::make('avatars');

        expect($field->getDirectory())->toBe('avatars');
    });

    it('sets directory to null when not provided', function () {
        $field = MediaFileUpload::make();

        expect($field->getDirectory())->toBeNull();
    });

    it('uses the configured storage disk', function () {
        $field = MediaFileUpload::make();
        $expectedDisk = app(StorageDriverService::class)->getMediaDriver();

        expect($field->getDiskName())->toBe($expectedDisk);
    });

    it('enables openable and downloadable', function () {
        $field = MediaFileUpload::make();

        expect($field->isOpenable())->toBeTrue()
            ->and($field->isDownloadable())->toBeTrue();
    });

    it('preserves filenames', function () {
        $field = MediaFileUpload::make();

        expect($field->shouldPreserveFilenames())->toBeTrue();
    });
});

describe('MediaFileUpload accepted types', function () {
    it('resolves config extensions to MIME types', function () {
        $field = MediaFileUpload::make();
        $acceptedTypes = $field->getAcceptedFileTypes();

        expect($acceptedTypes)->toBeArray()
            ->and($acceptedTypes)->not->toBeEmpty();
    });

    it('includes image MIME types for configured image extensions', function () {
        $field = MediaFileUpload::make();
        $acceptedTypes = $field->getAcceptedFileTypes();

        // jpg and png are in the default config
        expect($acceptedTypes)->toContain('image/jpeg')
            ->and($acceptedTypes)->toContain('image/png');
    });

    it('includes document MIME types for configured document extensions', function () {
        $field = MediaFileUpload::make();
        $acceptedTypes = $field->getAcceptedFileTypes();

        // pdf is in the default config
        expect($acceptedTypes)->toContain('application/pdf');
    });

    it('returns a sequentially indexed array', function () {
        $field = MediaFileUpload::make();
        $acceptedTypes = $field->getAcceptedFileTypes();

        // Keys must be sequential (0, 1, 2, ...) for correct JSON serialization
        expect(array_keys($acceptedTypes))->toBe(range(0, count($acceptedTypes) - 1));
    });

    it('contains no duplicate entries', function () {
        $field = MediaFileUpload::make();
        $acceptedTypes = $field->getAcceptedFileTypes();

        expect($acceptedTypes)->toBe(array_values(array_unique($acceptedTypes)));
    });

    it('returns empty accepted types when config has no allowed extensions', function () {
        config()->set('media.allowed_mime_types', '');

        $field = MediaFileUpload::make();
        $acceptedTypes = $field->getAcceptedFileTypes();

        expect($acceptedTypes)->toBeArray()
            ->and($acceptedTypes)->toBeEmpty();
    });
});

describe('MediaFileUpload max size', function () {
    it('sets max size from UploadService', function () {
        $field = MediaFileUpload::make();
        $expectedKb = (int) (app(UploadService::class)->getMaxSize() / 1024);

        expect($field->getMaxSize())->toBe($expectedKb);
    });

    it('returns max size in kilobytes', function () {
        $field = MediaFileUpload::make();

        // Max size should be a positive integer (in KB)
        expect($field->getMaxSize())->toBeGreaterThan(0)
            ->and($field->getMaxSize())->toBeInt();
    });
});
