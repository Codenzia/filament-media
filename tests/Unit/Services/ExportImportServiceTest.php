<?php

use Codenzia\FilamentMedia\Services\ExportImportService;
use Codenzia\FilamentMedia\Services\StorageDriverService;
use Codenzia\FilamentMedia\Services\MediaUrlService;
use Codenzia\FilamentMedia\Services\UploadService;
use Codenzia\FilamentMedia\Services\TagService;
use Codenzia\FilamentMedia\Services\MetadataService;
use Codenzia\FilamentMedia\Models\MediaFile;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

beforeEach(function () {
    MediaFile::withoutGlobalScopes();
});

describe('ExportImportService', function () {
    it('can be instantiated', function () {
        $service = app(ExportImportService::class);

        expect($service)->toBeInstanceOf(ExportImportService::class);
    });

    it('exportFiles returns a streamed response', function () {
        $file = MediaFile::factory()->create();

        $service = app(ExportImportService::class);
        $response = $service->exportFiles([$file->id]);

        expect($response)->toBeInstanceOf(StreamedResponse::class)
            ->and($response->headers->get('Content-Type'))->toBe('application/zip');
    });

    it('exportFiles handles empty file ids gracefully', function () {
        $service = app(ExportImportService::class);
        $response = $service->exportFiles([]);

        expect($response)->toBeInstanceOf(StreamedResponse::class);
    });

    it('importFromZip returns error for invalid zip file', function () {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, 'not a zip file');

        $uploadedFile = new UploadedFile(
            $tempFile,
            'invalid.zip',
            'application/zip',
            null,
            true
        );

        $service = app(ExportImportService::class);
        $result = $service->importFromZip($uploadedFile);

        expect($result)->toBeArray()
            ->and($result['error'])->toBeTrue()
            ->and($result['imported'])->toBe(0);

        @unlink($tempFile);
    });

    it('importFromZip processes a valid zip file', function () {
        // Create a real ZIP file with a simple text file inside
        $tempDir = sys_get_temp_dir();
        $zipPath = $tempDir . '/test_import_' . uniqid() . '.zip';

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $zip->addFromString('test-file.txt', 'Hello World');
            $zip->close();
        }

        $uploadedFile = new UploadedFile(
            $zipPath,
            'test-import.zip',
            'application/zip',
            null,
            true
        );

        $service = app(ExportImportService::class);
        $result = $service->importFromZip($uploadedFile);

        expect($result)->toBeArray()
            ->and($result)->toHaveKey('error')
            ->and($result)->toHaveKey('imported');

        @unlink($zipPath);
    });

    it('importFromFolder returns error for non-existent directory', function () {
        $service = app(ExportImportService::class);
        $result = $service->importFromFolder('/nonexistent/path/that/does/not/exist');

        expect($result)->toBeArray()
            ->and($result['error'])->toBeTrue()
            ->and($result['message'])->toBe('Directory not found')
            ->and($result['imported'])->toBe(0);
    });
});
