<?php

use Codenzia\FilamentMedia\FilamentMedia;
use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaFolder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');
    MediaFile::withoutGlobalScopes();
    MediaFolder::withoutGlobalScopes();

    config(['media.private_files.private_disk' => 'local']);
});

function createTestUser(int $id = 1): Authenticatable
{
    $user = new class extends Authenticatable
    {
        protected $table = 'users';

        public $timestamps = false;
    };
    $user->id = $id;

    return $user;
}

describe('PrivateMediaController - show', function () {
    it('denies unauthenticated access via auth middleware', function () {
        $file = MediaFile::factory()->private()->create();
        $hash = sha1($file->id);

        $response = $this->get("/media/private/{$hash}/{$file->id}");

        // Auth middleware returns non-200 for unauthenticated users
        expect($response->status())->not->toBe(200);
    });

    it('returns 404 for invalid hash', function () {
        $user = createTestUser();
        $file = MediaFile::factory()->private()->create();

        $response = $this->actingAs($user)->get("/media/private/invalid-hash/{$file->id}");

        $response->assertNotFound();
    });

    it('returns 404 for non-existent file', function () {
        $user = createTestUser();
        $nonExistentId = 99999;
        $hash = sha1($nonExistentId);

        $response = $this->actingAs($user)->get("/media/private/{$hash}/{$nonExistentId}");

        $response->assertNotFound();
    });

    it('returns 403 when custom callback denies access', function () {
        $filamentMedia = app(FilamentMedia::class);
        $filamentMedia->authorizeFileAccessUsing(function (MediaFile $file, $user) {
            return false;
        });

        $user = createTestUser();
        $file = MediaFile::factory()->private()->create();
        $hash = sha1($file->id);

        $response = $this->actingAs($user)->get("/media/private/{$hash}/{$file->id}");

        $response->assertForbidden();
    });

    it('serves public file to authenticated user via private route', function () {
        $user = createTestUser();
        $file = MediaFile::factory()->create([
            'visibility' => 'public',
            'url' => 'test-public-file.jpg',
            'mime_type' => 'image/jpeg',
            'name' => 'test-public-file',
        ]);

        Storage::disk('public')->put('test-public-file.jpg', 'image content');

        $hash = sha1($file->id);

        $response = $this->actingAs($user)->get("/media/private/{$hash}/{$file->id}");

        $response->assertOk();
    });

    it('serves private file to authenticated user with default callback', function () {
        $user = createTestUser();

        $file = MediaFile::factory()->private()->create([
            'url' => 'private-test-file.txt',
            'mime_type' => 'text/plain',
            'name' => 'private-test-file',
        ]);

        Storage::disk('local')->put('private-test-file.txt', 'private content');

        $hash = sha1($file->id);

        $response = $this->actingAs($user)->get("/media/private/{$hash}/{$file->id}");

        $response->assertOk();
    });

    it('serves private file when custom callback allows access', function () {
        $filamentMedia = app(FilamentMedia::class);
        $filamentMedia->authorizeFileAccessUsing(function (MediaFile $file, $user) {
            return $user !== null && $user->id === 1;
        });

        $user = createTestUser(1);

        $file = MediaFile::factory()->private()->create([
            'url' => 'callback-test-file.txt',
            'mime_type' => 'text/plain',
            'name' => 'callback-test-file',
        ]);

        Storage::disk('local')->put('callback-test-file.txt', 'secure content');

        $hash = sha1($file->id);

        $response = $this->actingAs($user)->get("/media/private/{$hash}/{$file->id}");

        $response->assertOk();
    });

    it('sets content-disposition to attachment when download query param is set', function () {
        $user = createTestUser();

        $file = MediaFile::factory()->private()->create([
            'url' => 'download-test.pdf',
            'mime_type' => 'application/pdf',
            'name' => 'download-test',
        ]);

        Storage::disk('local')->put('download-test.pdf', 'pdf content');

        $hash = sha1($file->id);

        $response = $this->actingAs($user)->get("/media/private/{$hash}/{$file->id}?download=1");

        $response->assertOk();

        $contentDisposition = $response->headers->get('Content-Disposition');
        expect($contentDisposition)->toContain('attachment')
            ->and($contentDisposition)->toContain('download-test.pdf');
    });
});

describe('PrivateMediaController - showThumbnail', function () {
    it('denies unauthenticated access to thumbnails via auth middleware', function () {
        $file = MediaFile::factory()->private()->create();
        $hash = sha1($file->id);

        $response = $this->get("/media/private/{$hash}/{$file->id}/thumb/150x150");

        expect($response->status())->not->toBe(200);
    });

    it('returns 404 for invalid hash', function () {
        $user = createTestUser();
        $file = MediaFile::factory()->private()->create();

        $response = $this->actingAs($user)->get("/media/private/invalid-hash/{$file->id}/thumb/150x150");

        $response->assertNotFound();
    });

    it('returns 404 for non-existent file', function () {
        $user = createTestUser();
        $nonExistentId = 99999;
        $hash = sha1($nonExistentId);

        $response = $this->actingAs($user)->get("/media/private/{$hash}/{$nonExistentId}/thumb/150x150");

        $response->assertNotFound();
    });

    it('returns 403 when custom callback denies access', function () {
        $filamentMedia = app(FilamentMedia::class);
        $filamentMedia->authorizeFileAccessUsing(function (MediaFile $file, $user) {
            return false;
        });

        $user = createTestUser();
        $file = MediaFile::factory()->private()->create();
        $hash = sha1($file->id);

        $response = $this->actingAs($user)->get("/media/private/{$hash}/{$file->id}/thumb/150x150");

        $response->assertForbidden();
    });

    it('serves thumbnail for authenticated user', function () {
        $user = createTestUser();

        $file = MediaFile::factory()->private()->create([
            'url' => 'thumb-test.jpg',
            'mime_type' => 'image/jpeg',
            'name' => 'thumb-test',
        ]);

        Storage::disk('local')->put('thumb-test-150x150.jpg', 'thumbnail content');

        $hash = sha1($file->id);

        $response = $this->actingAs($user)->get("/media/private/{$hash}/{$file->id}/thumb/150x150");

        $response->assertOk();
    });

    it('returns 404 when thumbnail does not exist on disk', function () {
        $user = createTestUser();

        $file = MediaFile::factory()->private()->create([
            'url' => 'no-thumb.jpg',
            'mime_type' => 'image/jpeg',
            'name' => 'no-thumb',
        ]);

        $hash = sha1($file->id);

        $response = $this->actingAs($user)->get("/media/private/{$hash}/{$file->id}/thumb/150x150");

        $response->assertNotFound();
    });
});
