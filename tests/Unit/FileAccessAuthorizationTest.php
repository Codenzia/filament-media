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
    MediaFile::withoutGlobalScopes();
    MediaFolder::withoutGlobalScopes();
});

function createAuthUser(int $id = 1): object
{
    $user = new class extends Authenticatable
    {
        protected $table = 'users';

        public $timestamps = false;
    };
    $user->id = $id;

    return $user;
}

describe('FilamentMedia - canAccessFile', function () {
    it('allows access to public files without any user', function () {
        $media = app(FilamentMedia::class);
        $file = MediaFile::factory()->create(['visibility' => 'public']);

        expect($media->canAccessFile($file))->toBeTrue();
    });

    it('allows access to public files even with null user', function () {
        $media = app(FilamentMedia::class);
        $file = MediaFile::factory()->create(['visibility' => 'public']);

        expect($media->canAccessFile($file, null))->toBeTrue();
    });

    it('denies access to private files without user (default callback)', function () {
        $media = app(FilamentMedia::class);
        $file = MediaFile::factory()->private()->create();

        expect($media->canAccessFile($file, null))->toBeFalse();
    });

    it('allows access to private files with authenticated user (default callback)', function () {
        $media = app(FilamentMedia::class);
        $file = MediaFile::factory()->private()->create();
        $user = createAuthUser();

        expect($media->canAccessFile($file, $user))->toBeTrue();
    });
});

describe('FilamentMedia - authorizeFileAccessUsing', function () {
    it('uses custom callback when set', function () {
        $media = app(FilamentMedia::class);

        $media->authorizeFileAccessUsing(function (MediaFile $file, $user) {
            return $user !== null && $user->id === 42;
        });

        $file = MediaFile::factory()->private()->create();

        $allowedUser = createAuthUser(42);
        $deniedUser = createAuthUser(99);

        expect($media->canAccessFile($file, $allowedUser))->toBeTrue()
            ->and($media->canAccessFile($file, $deniedUser))->toBeFalse();
    });

    it('still allows public files regardless of custom callback', function () {
        $media = app(FilamentMedia::class);

        $media->authorizeFileAccessUsing(function (MediaFile $file, $user) {
            return false;
        });

        $file = MediaFile::factory()->create(['visibility' => 'public']);

        expect($media->canAccessFile($file, null))->toBeTrue();
    });

    it('passes file model to custom callback', function () {
        $media = app(FilamentMedia::class);
        $capturedFile = null;

        $media->authorizeFileAccessUsing(function (MediaFile $file, $user) use (&$capturedFile) {
            $capturedFile = $file;

            return true;
        });

        $file = MediaFile::factory()->private()->create();
        $user = createAuthUser();

        $media->canAccessFile($file, $user);

        expect($capturedFile)->not->toBeNull()
            ->and($capturedFile->id)->toBe($file->id);
    });

    it('can use file properties in callback for role-based access', function () {
        $media = app(FilamentMedia::class);

        $media->authorizeFileAccessUsing(function (MediaFile $file, $user) {
            if ($file->folder_id === null) {
                return true;
            }

            return $user !== null;
        });

        $privateInRoot = MediaFile::factory()->private()->create(['folder_id' => null]);
        $privateInFolder = MediaFile::factory()->private()->create(['folder_id' => 1]);

        expect($media->canAccessFile($privateInRoot, null))->toBeTrue()
            ->and($media->canAccessFile($privateInFolder, null))->toBeFalse()
            ->and($media->canAccessFile($privateInFolder, createAuthUser()))->toBeTrue();
    });

    it('denies null user with custom callback that requires user', function () {
        $media = app(FilamentMedia::class);

        $media->authorizeFileAccessUsing(function (MediaFile $file, $user) {
            return $user !== null;
        });

        $file = MediaFile::factory()->private()->create();

        expect($media->canAccessFile($file, null))->toBeFalse();
    });
});
