<?php

use Codenzia\FilamentMedia\Services\MediaUrlService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->service = app(MediaUrlService::class);
});

describe('MediaUrlService - url', function () {
    it('returns a Storage URL for relative paths', function () {
        $url = $this->service->url('images/photo.jpg');

        expect($url)->toBeString()
            ->and($url)->toContain('images/photo.jpg');
    });

    it('returns the URL as-is for absolute http URLs', function () {
        $absoluteUrl = 'https://example.com/images/photo.jpg';

        $url = $this->service->url($absoluteUrl);

        expect($url)->toBe($absoluteUrl);
    });

    it('returns the URL as-is for http URLs', function () {
        $absoluteUrl = 'http://example.com/images/photo.jpg';

        $url = $this->service->url($absoluteUrl);

        expect($url)->toBe($absoluteUrl);
    });

    it('trims whitespace from the path', function () {
        $url = $this->service->url('  images/photo.jpg  ');

        expect($url)->toContain('images/photo.jpg')
            ->and($url)->not->toContain('  ');
    });

    it('handles null path gracefully', function () {
        $url = $this->service->url(null);

        expect($url)->toBeString();
    });
});

describe('MediaUrlService - getMimeType', function () {
    it('returns correct MIME type for known image extensions', function () {
        Storage::put('test-image.jpg', 'fake image content');

        $mimeType = $this->service->getMimeType('test-image.jpg');

        expect($mimeType)->toBe('image/jpeg');
    });

    it('returns correct MIME type for png files', function () {
        Storage::put('test-image.png', 'fake png content');

        $mimeType = $this->service->getMimeType('test-image.png');

        expect($mimeType)->toBe('image/png');
    });

    it('returns correct MIME type for pdf files', function () {
        Storage::put('document.pdf', 'fake pdf content');

        $mimeType = $this->service->getMimeType('document.pdf');

        expect($mimeType)->toBe('application/pdf');
    });

    it('returns null for null url', function () {
        $mimeType = $this->service->getMimeType(null);

        expect($mimeType)->toBeNull();
    });

    it('returns MIME type for remote URLs with known extensions', function () {
        $mimeType = $this->service->getMimeType('https://example.com/photo.jpg');

        expect($mimeType)->toBe('image/jpeg');
    });

    it('returns correct MIME for svg files in remote URLs', function () {
        $mimeType = $this->service->getMimeType('https://example.com/icon.svg');

        expect($mimeType)->toBe('image/svg+xml');
    });
});

describe('MediaUrlService - isImage', function () {
    it('returns true for image MIME types', function () {
        expect($this->service->isImage('image/jpeg'))->toBeTrue()
            ->and($this->service->isImage('image/png'))->toBeTrue()
            ->and($this->service->isImage('image/gif'))->toBeTrue()
            ->and($this->service->isImage('image/webp'))->toBeTrue()
            ->and($this->service->isImage('image/svg+xml'))->toBeTrue();
    });

    it('returns false for non-image MIME types', function () {
        expect($this->service->isImage('application/pdf'))->toBeFalse()
            ->and($this->service->isImage('video/mp4'))->toBeFalse()
            ->and($this->service->isImage('text/plain'))->toBeFalse()
            ->and($this->service->isImage('audio/mpeg'))->toBeFalse();
    });
});

describe('MediaUrlService - fileExists', function () {
    it('returns false for empty URL', function () {
        expect($this->service->fileExists(null))->toBeFalse()
            ->and($this->service->fileExists(''))->toBeFalse();
    });

    it('returns true when file exists on disk', function () {
        Storage::put('existing-file.txt', 'content');

        $result = $this->service->fileExists('existing-file.txt');

        expect($result)->toBeTrue();
    });

    it('returns false when file does not exist on disk', function () {
        $result = $this->service->fileExists('nonexistent-file.txt');

        expect($result)->toBeFalse();
    });
});

describe('MediaUrlService - getFileSize', function () {
    it('returns formatted size for an existing file', function () {
        Storage::put('sized-file.txt', str_repeat('a', 2048));

        $size = $this->service->getFileSize('sized-file.txt');

        expect($size)->toBeString()
            ->and($size)->toContain('kB');
    });

    it('returns null for a non-existent file', function () {
        $size = $this->service->getFileSize('missing-file.txt');

        expect($size)->toBeNull();
    });

    it('returns null for null path', function () {
        $size = $this->service->getFileSize(null);

        expect($size)->toBeNull();
    });

    it('returns 0kB for an empty file', function () {
        Storage::put('empty-file.txt', '');

        $size = $this->service->getFileSize('empty-file.txt');

        expect($size)->toBe('0kB');
    });
});
