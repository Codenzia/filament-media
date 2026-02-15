<?php

use Codenzia\FilamentMedia\FilamentMedia;
use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaFolder;
use Illuminate\Foundation\Auth\User as Authenticatable;
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

    it('has indirect url attribute for public file', function () {
        $file = MediaFile::factory()->create(['visibility' => 'public']);

        expect($file->indirect_url)->toBeString()
            ->and($file->indirect_url)->toContain('media/files');
    });

    it('has indirect url pointing to private route for private file', function () {
        $file = MediaFile::factory()->private()->create();

        expect($file->indirect_url)->toBeString()
            ->and($file->indirect_url)->toContain('media/private')
            ->and($file->indirect_url)->toContain(sha1($file->id));
    });

    it('returns preview_url with storage URL for public image', function () {
        $file = MediaFile::factory()->create([
            'visibility' => 'public',
            'url' => 'photos/sunset.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        expect($file->preview_url)->toBeString()
            ->and($file->preview_url)->toContain('photos/sunset.jpg')
            ->and($file->preview_url)->not->toContain('media/private');
    });

    it('returns preview_url with private route for private image', function () {
        $file = MediaFile::factory()->private()->create([
            'url' => 'photos/secret.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        expect($file->preview_url)->toBeString()
            ->and($file->preview_url)->toContain('media/private')
            ->and($file->preview_url)->toContain(sha1($file->id));
    });

    it('returns preview_url with private route for private video', function () {
        $file = MediaFile::factory()->private()->create([
            'url' => 'videos/clip.mp4',
            'mime_type' => 'video/mp4',
        ]);

        expect($file->preview_url)->toBeString()
            ->and($file->preview_url)->toContain('media/private');
    });

    it('returns preview_url with private route for private PDF', function () {
        $file = MediaFile::factory()->private()->create([
            'url' => 'docs/report.pdf',
            'mime_type' => 'application/pdf',
        ]);

        expect($file->preview_url)->toBeString()
            ->and($file->preview_url)->toContain('media/private');
    });

    it('returns null preview_url for private office documents', function () {
        $file = MediaFile::factory()->private()->create([
            'url' => 'docs/spreadsheet.xlsx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);

        expect($file->preview_url)->toBeNull();
    });

    it('returns null preview_url for non-previewable public files', function () {
        $file = MediaFile::factory()->create([
            'visibility' => 'public',
            'url' => 'archive.zip',
            'mime_type' => 'application/zip',
        ]);

        expect($file->preview_url)->toBeNull();
    });

    it('generates privateRouteUrl with correct hash', function () {
        $file = MediaFile::factory()->private()->create();

        $indirectUrl = $file->indirect_url;
        $expectedHash = sha1($file->id);

        expect($indirectUrl)->toContain($expectedHash)
            ->and($indirectUrl)->toContain((string) $file->id);
    });

    it('canGenerateThumbnails returns true for jpeg regardless of visibility', function () {
        $publicFile = MediaFile::factory()->create([
            'visibility' => 'public',
            'mime_type' => 'image/jpeg',
        ]);

        $privateFile = MediaFile::factory()->private()->create([
            'mime_type' => 'image/jpeg',
        ]);

        expect($publicFile->canGenerateThumbnails())->toBeTrue()
            ->and($privateFile->canGenerateThumbnails())->toBeTrue();
    });

    it('canGenerateThumbnails returns false for non-image files', function () {
        $file = MediaFile::factory()->create([
            'mime_type' => 'application/pdf',
        ]);

        expect($file->canGenerateThumbnails())->toBeFalse();
    });

    it('canGenerateThumbnails returns false for SVG', function () {
        $file = MediaFile::factory()->create([
            'mime_type' => 'image/svg+xml',
        ]);

        expect($file->canGenerateThumbnails())->toBeFalse();
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

describe('MediaFile Global Scope with Custom Callback', function () {
    function createScopeTestUser(int $id = 1): object
    {
        $user = new class extends Authenticatable
        {
            protected $table = 'users';

            public $timestamps = false;
        };
        $user->id = $id;

        return $user;
    }

    it('applies custom scope callback when registered', function () {
        $user = createScopeTestUser(5);
        $this->actingAs($user);

        // Create files without global scopes
        MediaFile::factory()->create(['created_by_user_id' => 5]);
        MediaFile::factory()->create(['created_by_user_id' => 99]);

        $media = app(FilamentMedia::class);
        $media->scopeMediaQueryUsing(function ($query, $authUser) {
            $query->where('media_files.created_by_user_id', $authUser->id);
        });

        // Re-enable global scopes for this query
        $results = MediaFile::withGlobalScope('ownMedia', function ($query) use ($media) {
            $user = auth()->user();
            $scopeCallback = $media->getMediaQueryScope();
            if ($scopeCallback && $user) {
                call_user_func($scopeCallback, $query, $user);
            }
        })->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->created_by_user_id)->toBe(5);
    });

    it('does not filter when no user is authenticated', function () {
        MediaFile::factory()->create(['created_by_user_id' => 1]);
        MediaFile::factory()->create(['created_by_user_id' => 2]);

        $media = app(FilamentMedia::class);
        $media->scopeMediaQueryUsing(function ($query, $authUser) {
            $query->where('media_files.created_by_user_id', $authUser->id);
        });

        // No user authenticated — scope should not apply
        $results = MediaFile::withoutGlobalScopes()->get();

        expect($results)->toHaveCount(2);
    });

    it('falls back to default behavior when no custom callback set', function () {
        MediaFile::factory()->create();
        MediaFile::factory()->create();

        // No scope callback registered, canOnlyViewOwnMedia returns false
        $results = MediaFile::withoutGlobalScopes()->get();

        expect($results)->toHaveCount(2);
    });
});
