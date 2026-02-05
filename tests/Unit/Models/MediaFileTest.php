<?php

use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaFolder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Disable global scopes for testing
    MediaFile::withoutGlobalScopes();
});

describe('MediaFile Model', function () {
    it('can be created with factory', function () {
        $file = MediaFile::factory()->create();

        expect($file)->toBeInstanceOf(MediaFile::class)
            ->and($file->exists)->toBeTrue();
    });

    it('has fillable attributes', function () {
        $file = MediaFile::factory()->create([
            'name' => 'test-image',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'url' => 'test.jpg',
            'alt' => 'Test alt text',
            'visibility' => 'public',
        ]);

        expect($file->name)->toBe('test-image')
            ->and($file->mime_type)->toBe('image/jpeg')
            ->and($file->size)->toBe(1024)
            ->and($file->url)->toBe('test.jpg')
            ->and($file->alt)->toBe('Test alt text')
            ->and($file->visibility)->toBe('public');
    });

    it('uses soft deletes', function () {
        $file = MediaFile::factory()->create();
        $fileId = $file->id;

        $file->delete();

        expect(MediaFile::find($fileId))->toBeNull()
            ->and(MediaFile::withTrashed()->find($fileId))->not->toBeNull();
    });

    it('belongs to a folder', function () {
        $folder = MediaFolder::factory()->create();
        $file = MediaFile::factory()->create(['folder_id' => $folder->id]);

        expect($file->folder)->toBeInstanceOf(MediaFolder::class)
            ->and($file->folder->id)->toBe($folder->id);
    });

    it('returns default folder when none assigned', function () {
        $file = MediaFile::factory()->create(['folder_id' => null]);

        expect($file->folder)->toBeInstanceOf(MediaFolder::class)
            ->and($file->folder->exists)->toBeFalse();
    });

    it('determines type based on mime type', function () {
        $imageFile = MediaFile::factory()->create(['mime_type' => 'image/jpeg']);
        $videoFile = MediaFile::factory()->create(['mime_type' => 'video/mp4']);
        $pdfFile = MediaFile::factory()->create(['mime_type' => 'application/pdf']);

        expect($imageFile->type)->toBe('image')
            ->and($videoFile->type)->toBe('video')
            ->and($pdfFile->type)->toBe('document');
    });

    it('creates unique name when duplicate exists', function () {
        $folder = MediaFolder::factory()->create();

        MediaFile::factory()->create([
            'name' => 'test-file',
            'folder_id' => $folder->id,
        ]);

        $newName = MediaFile::createName('test-file', $folder->id);

        expect($newName)->toBe('test-file-1');
    });

    it('returns original name when no duplicate exists', function () {
        $folder = MediaFolder::factory()->create();

        $newName = MediaFile::createName('unique-file', $folder->id);

        expect($newName)->toBe('unique-file');
    });

    it('has indirect url attribute', function () {
        $file = MediaFile::factory()->create();

        expect($file->indirect_url)->toBeString()
            ->and($file->indirect_url)->toContain('media/files');
    });

    it('casts options to json', function () {
        $options = ['width' => 800, 'height' => 600];
        $file = MediaFile::factory()->create(['options' => $options]);

        $file->refresh();

        expect($file->options)->toBeArray()
            ->and($file->options)->toBe($options);
    });
});

describe('MediaFile Factory States', function () {
    it('can create image files', function () {
        $file = MediaFile::factory()->image()->create();

        expect($file->mime_type)->toBeIn(['image/jpeg', 'image/png', 'image/gif']);
    });

    it('can create document files', function () {
        $file = MediaFile::factory()->document()->create();

        expect($file->mime_type)->toBeIn(['application/pdf', 'application/msword']);
    });

    it('can create video files', function () {
        $file = MediaFile::factory()->video()->create();

        expect($file->mime_type)->toBe('video/mp4');
    });

    it('can create private files', function () {
        $file = MediaFile::factory()->private()->create();

        expect($file->visibility)->toBe('private');
    });

    it('can create files in specific folder', function () {
        $folder = MediaFolder::factory()->create();
        $file = MediaFile::factory()->inFolder($folder)->create();

        expect($file->folder_id)->toBe($folder->id);
    });
});
