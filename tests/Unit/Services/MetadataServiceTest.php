<?php

use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaMetadataField;
use Codenzia\FilamentMedia\Services\MetadataService;

beforeEach(function () {
    MediaFile::withoutGlobalScopes();
    $this->service = app(MetadataService::class);
});

describe('MetadataService', function () {
    describe('createField', function () {
        it('creates a metadata field', function () {
            $field = $this->service->createField([
                'name' => 'Copyright',
                'slug' => 'copyright',
                'type' => 'text',
                'is_required' => false,
                'is_searchable' => true,
                'sort_order' => 1,
            ]);

            expect($field)->toBeInstanceOf(MediaMetadataField::class)
                ->and($field->name)->toBe('Copyright')
                ->and($field->slug)->toBe('copyright')
                ->and($field->type)->toBe('text')
                ->and($field->is_searchable)->toBeTrue()
                ->and($field->exists)->toBeTrue();
        });

        it('auto-generates slug from name when slug is empty', function () {
            $field = $this->service->createField([
                'name' => 'Camera Model',
                'type' => 'text',
            ]);

            expect($field->slug)->toBe('camera-model');
        });
    });

    describe('updateField', function () {
        it('updates field data', function () {
            $field = $this->service->createField([
                'name' => 'Location',
                'slug' => 'location',
                'type' => 'text',
                'sort_order' => 0,
            ]);

            $updated = $this->service->updateField($field->id, [
                'name' => 'Photo Location',
                'is_required' => true,
                'sort_order' => 5,
            ]);

            expect($updated->name)->toBe('Photo Location')
                ->and($updated->is_required)->toBeTrue()
                ->and($updated->sort_order)->toBe(5);
        });
    });

    describe('deleteField', function () {
        it('removes a field from the database', function () {
            $field = $this->service->createField([
                'name' => 'Temporary',
                'slug' => 'temporary',
                'type' => 'text',
            ]);

            $fieldId = $field->id;
            $result = $this->service->deleteField($fieldId);

            expect($result)->toBeTrue()
                ->and(MediaMetadataField::find($fieldId))->toBeNull();
        });
    });

    describe('getFields', function () {
        it('returns fields ordered by sort_order', function () {
            $this->service->createField(['name' => 'Third', 'slug' => 'third', 'type' => 'text', 'sort_order' => 3]);
            $this->service->createField(['name' => 'First', 'slug' => 'first', 'type' => 'text', 'sort_order' => 1]);
            $this->service->createField(['name' => 'Second', 'slug' => 'second', 'type' => 'text', 'sort_order' => 2]);

            $fields = $this->service->getFields();

            expect($fields)->toHaveCount(3)
                ->and($fields[0]->name)->toBe('First')
                ->and($fields[1]->name)->toBe('Second')
                ->and($fields[2]->name)->toBe('Third');
        });
    });

    describe('setMetadata', function () {
        it('sets field values on a file', function () {
            $file = MediaFile::factory()->create();

            $fieldA = $this->service->createField(['name' => 'Author', 'slug' => 'author', 'type' => 'text']);
            $fieldB = $this->service->createField(['name' => 'Year', 'slug' => 'year', 'type' => 'number']);

            $this->service->setMetadata($file, [
                $fieldA->id => 'John Doe',
                $fieldB->id => '2025',
            ]);

            $file->refresh();

            expect($file->metadata)->toHaveCount(2);
        });
    });

    describe('getMetadata', function () {
        it('returns metadata for a file with pivot values', function () {
            $file = MediaFile::factory()->create();
            $field = $this->service->createField(['name' => 'License', 'slug' => 'license', 'type' => 'text']);

            $this->service->setMetadata($file, [
                $field->id => 'MIT',
            ]);

            $metadata = $this->service->getMetadata($file);

            expect($metadata)->toHaveCount(1)
                ->and($metadata->first()->name)->toBe('License')
                ->and($metadata->first()->pivot->value)->toBe('MIT');
        });
    });

    describe('getMetadataValue', function () {
        it('returns specific field value by slug', function () {
            $file = MediaFile::factory()->create();
            $field = $this->service->createField(['name' => 'Format', 'slug' => 'format', 'type' => 'text']);

            $this->service->setMetadata($file, [
                $field->id => 'RAW',
            ]);

            $value = $this->service->getMetadataValue($file, 'format');

            expect($value)->toBe('RAW');
        });

        it('returns null when field does not exist on file', function () {
            $file = MediaFile::factory()->create();

            $value = $this->service->getMetadataValue($file, 'nonexistent');

            expect($value)->toBeNull();
        });
    });
});
