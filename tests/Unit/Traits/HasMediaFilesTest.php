<?php

use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaFolder;
use Codenzia\FilamentMedia\Traits\HasMediaFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    MediaFile::withoutGlobalScopes();
    MediaFolder::withoutGlobalScopes();

    // Create a test table for our fake model
    if (!Schema::hasTable('test_models')) {
        Schema::create('test_models', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }
});

afterEach(function () {
    Schema::dropIfExists('test_models');
});

// Create a test model that uses the trait
class TestModelWithMedia extends Model
{
    use HasMediaFiles;

    protected $table = 'test_models';
    protected $fillable = ['name'];
}

describe('HasMediaFiles Trait', function () {
    it('provides files relationship', function () {
        $model = TestModelWithMedia::create(['name' => 'Test']);

        expect($model->files())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphMany::class);
    });

    it('provides folders relationship', function () {
        $model = TestModelWithMedia::create(['name' => 'Test']);

        expect($model->folders())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphMany::class);
    });

    it('can attach media file', function () {
        $model = TestModelWithMedia::create(['name' => 'Test']);
        $file = MediaFile::factory()->create();

        $model->attachMediaFile($file);

        $file->refresh();

        expect($file->fileable_type)->toBe(TestModelWithMedia::class)
            ->and($file->fileable_id)->toBe($model->id);
    });

    it('can attach multiple media files', function () {
        $model = TestModelWithMedia::create(['name' => 'Test']);
        $files = MediaFile::factory()->count(3)->create();

        $model->attachMediaFiles($files);

        expect($model->files()->count())->toBe(3);
    });

    it('can detach media file', function () {
        $model = TestModelWithMedia::create(['name' => 'Test']);
        $file = MediaFile::factory()->create([
            'fileable_type' => TestModelWithMedia::class,
            'fileable_id' => $model->id,
        ]);

        $model->detachMediaFile($file);

        $file->refresh();

        expect($file->fileable_type)->toBeNull()
            ->and($file->fileable_id)->toBeNull();
    });

    it('can detach all media files', function () {
        $model = TestModelWithMedia::create(['name' => 'Test']);
        MediaFile::factory()->count(3)->create([
            'fileable_type' => TestModelWithMedia::class,
            'fileable_id' => $model->id,
        ]);

        $model->detachAllMediaFiles();

        expect($model->files()->count())->toBe(0);
    });

    it('filters images by mime_type', function () {
        $model = TestModelWithMedia::create(['name' => 'Test']);

        MediaFile::factory()->create([
            'fileable_type' => TestModelWithMedia::class,
            'fileable_id' => $model->id,
            'mime_type' => 'image/jpeg',
        ]);

        MediaFile::factory()->create([
            'fileable_type' => TestModelWithMedia::class,
            'fileable_id' => $model->id,
            'mime_type' => 'application/pdf',
        ]);

        expect($model->images()->count())->toBe(1);
    });

    it('filters videos by mime_type', function () {
        $model = TestModelWithMedia::create(['name' => 'Test']);

        MediaFile::factory()->create([
            'fileable_type' => TestModelWithMedia::class,
            'fileable_id' => $model->id,
            'mime_type' => 'video/mp4',
        ]);

        MediaFile::factory()->create([
            'fileable_type' => TestModelWithMedia::class,
            'fileable_id' => $model->id,
            'mime_type' => 'image/jpeg',
        ]);

        expect($model->videos()->count())->toBe(1);
    });

    it('filters documents by mime_type', function () {
        $model = TestModelWithMedia::create(['name' => 'Test']);

        MediaFile::factory()->create([
            'fileable_type' => TestModelWithMedia::class,
            'fileable_id' => $model->id,
            'mime_type' => 'application/pdf',
        ]);

        MediaFile::factory()->create([
            'fileable_type' => TestModelWithMedia::class,
            'fileable_id' => $model->id,
            'mime_type' => 'text/plain',
        ]);

        MediaFile::factory()->create([
            'fileable_type' => TestModelWithMedia::class,
            'fileable_id' => $model->id,
            'mime_type' => 'image/jpeg',
        ]);

        expect($model->documents()->count())->toBe(2);
    });

    it('filters audio by mime_type', function () {
        $model = TestModelWithMedia::create(['name' => 'Test']);

        MediaFile::factory()->create([
            'fileable_type' => TestModelWithMedia::class,
            'fileable_id' => $model->id,
            'mime_type' => 'audio/mpeg',
        ]);

        MediaFile::factory()->create([
            'fileable_type' => TestModelWithMedia::class,
            'fileable_id' => $model->id,
            'mime_type' => 'video/mp4',
        ]);

        expect($model->audio()->count())->toBe(1);
    });

    it('can sync media files', function () {
        $model = TestModelWithMedia::create(['name' => 'Test']);

        // Attach initial files
        $initialFiles = MediaFile::factory()->count(2)->create();
        $model->attachMediaFiles($initialFiles);

        // Create new files to sync
        $newFiles = MediaFile::factory()->count(3)->create();

        $model->syncMediaFiles($newFiles);

        expect($model->files()->count())->toBe(3);

        // Old files should be detached
        $initialFiles->each(function ($file) {
            $file->refresh();
            expect($file->fileable_id)->toBeNull();
        });
    });

    it('returns first image', function () {
        $model = TestModelWithMedia::create(['name' => 'Test']);

        $firstImage = MediaFile::factory()->create([
            'fileable_type' => TestModelWithMedia::class,
            'fileable_id' => $model->id,
            'mime_type' => 'image/jpeg',
            'name' => 'First Image',
        ]);

        MediaFile::factory()->create([
            'fileable_type' => TestModelWithMedia::class,
            'fileable_id' => $model->id,
            'mime_type' => 'image/png',
            'name' => 'Second Image',
        ]);

        $image = $model->getFirstImage();

        expect($image)->not->toBeNull()
            ->and($image->id)->toBe($firstImage->id);
    });

    it('returns null when no images exist', function () {
        $model = TestModelWithMedia::create(['name' => 'Test']);

        $image = $model->getFirstImage();

        expect($image)->toBeNull();
    });
});
