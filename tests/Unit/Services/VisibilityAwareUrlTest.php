<?php

use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaFolder;
use Codenzia\FilamentMedia\Services\MediaUrlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    MediaFile::withoutGlobalScopes();
    MediaFolder::withoutGlobalScopes();
    $this->service = app(MediaUrlService::class);
});

describe('MediaUrlService - visibilityAwareUrl', function () {
    it('returns direct storage URL for public files', function () {
        $file = MediaFile::factory()->create([
            'visibility' => 'public',
            'url' => 'photos/beach.jpg',
        ]);

        $url = $this->service->visibilityAwareUrl($file);

        expect($url)->toContain('photos/beach.jpg')
            ->and($url)->not->toContain('/media/private/');
    });

    it('returns private route URL for private files', function () {
        $file = MediaFile::factory()->private()->create([
            'url' => 'docs/secret.pdf',
        ]);

        $url = $this->service->visibilityAwareUrl($file);
        $expectedHash = sha1($file->id);

        expect($url)->toContain('/media/private/')
            ->and($url)->toContain($expectedHash)
            ->and($url)->toContain((string) $file->id);
    });

    it('generates different URLs for public vs private versions of same path', function () {
        $publicFile = MediaFile::factory()->create([
            'visibility' => 'public',
            'url' => 'same-path/file.jpg',
        ]);

        $privateFile = MediaFile::factory()->private()->create([
            'url' => 'same-path/file.jpg',
        ]);

        $publicUrl = $this->service->visibilityAwareUrl($publicFile);
        $privateUrl = $this->service->visibilityAwareUrl($privateFile);

        expect($publicUrl)->not->toBe($privateUrl)
            ->and($publicUrl)->toContain('same-path/file.jpg')
            ->and($privateUrl)->toContain('/media/private/');
    });

    it('includes correct hash in private URL', function () {
        $file = MediaFile::factory()->private()->create();

        $url = $this->service->visibilityAwareUrl($file);
        $expectedHash = sha1($file->id);

        expect($url)->toContain($expectedHash);
    });

    it('generates unique private URLs for different files', function () {
        $file1 = MediaFile::factory()->private()->create();
        $file2 = MediaFile::factory()->private()->create();

        $url1 = $this->service->visibilityAwareUrl($file1);
        $url2 = $this->service->visibilityAwareUrl($file2);

        expect($url1)->not->toBe($url2);
    });

    it('treats default public visibility correctly', function () {
        $file = MediaFile::factory()->create([
            'url' => 'default-vis/test.jpg',
        ]);

        // Default visibility is 'public', should use direct URL
        $url = $this->service->visibilityAwareUrl($file);

        expect($url)->not->toContain('/media/private/')
            ->and($url)->toContain('default-vis/test.jpg');
    });
});
