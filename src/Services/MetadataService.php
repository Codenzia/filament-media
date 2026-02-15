<?php

namespace Codenzia\FilamentMedia\Services;

use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaMetadataField;
use Illuminate\Support\Collection;

/**
 * Manages custom metadata fields and their values on media files,
 * including EXIF data extraction from images.
 */
class MetadataService
{
    // ──────────────────────────────────────────────────
    // Field management
    // ──────────────────────────────────────────────────

    public function createField(array $data): MediaMetadataField
    {
        return MediaMetadataField::create($data);
    }

    public function updateField(int $fieldId, array $data): MediaMetadataField
    {
        $field = MediaMetadataField::findOrFail($fieldId);
        $field->update($data);

        return $field;
    }

    public function deleteField(int $fieldId): bool
    {
        return MediaMetadataField::findOrFail($fieldId)->delete();
    }

    public function getFields(): Collection
    {
        return MediaMetadataField::ordered()->get();
    }

    // ──────────────────────────────────────────────────
    // File metadata
    // ──────────────────────────────────────────────────

    public function setMetadata(MediaFile $file, array $fieldValues): void
    {
        $syncData = [];

        foreach ($fieldValues as $fieldId => $value) {
            $syncData[$fieldId] = ['value' => $value];
        }

        $file->metadata()->sync($syncData);
    }

    public function getMetadata(MediaFile $file): Collection
    {
        return $file->metadata()->withPivot('value')->get();
    }

    public function getMetadataValue(MediaFile $file, string $fieldSlug): ?string
    {
        $field = $file->metadata()
            ->where('media_metadata_fields.slug', $fieldSlug)
            ->first();

        return $field?->pivot->value;
    }

    public function bulkSetMetadata(array $fileIds, array $fieldValues): void
    {
        $files = MediaFile::whereIn('id', $fileIds)->get();

        $syncData = [];
        foreach ($fieldValues as $fieldId => $value) {
            $syncData[$fieldId] = ['value' => $value];
        }

        foreach ($files as $file) {
            $file->metadata()->syncWithoutDetaching($syncData);
        }
    }

    // ──────────────────────────────────────────────────
    // Auto-extraction (EXIF, PDF metadata, etc.)
    // ──────────────────────────────────────────────────

    public function extractMetadata(MediaFile $file): array
    {
        $extracted = [];

        if (str_starts_with($file->mime_type, 'image/') && function_exists('exif_read_data')) {
            $extracted = $this->extractExifData($file);
        }

        return $extracted;
    }

    protected function extractExifData(MediaFile $file): array
    {
        try {
            $realPath = app(MediaUrlService::class)->getRealPath($file->url);

            if (! $realPath || ! file_exists($realPath)) {
                return [];
            }

            $exif = @exif_read_data($realPath, 'ANY_TAG', true);

            if (! $exif) {
                return [];
            }

            $data = [];

            if (isset($exif['IFD0']['Make'])) {
                $data['camera_make'] = $exif['IFD0']['Make'];
            }
            if (isset($exif['IFD0']['Model'])) {
                $data['camera_model'] = $exif['IFD0']['Model'];
            }
            if (isset($exif['EXIF']['DateTimeOriginal'])) {
                $data['date_taken'] = $exif['EXIF']['DateTimeOriginal'];
            }
            if (isset($exif['COMPUTED']['Width'])) {
                $data['width'] = $exif['COMPUTED']['Width'];
            }
            if (isset($exif['COMPUTED']['Height'])) {
                $data['height'] = $exif['COMPUTED']['Height'];
            }
            if (isset($exif['EXIF']['ExposureTime'])) {
                $data['exposure_time'] = $exif['EXIF']['ExposureTime'];
            }
            if (isset($exif['EXIF']['FNumber'])) {
                $data['f_number'] = $exif['EXIF']['FNumber'];
            }
            if (isset($exif['EXIF']['ISOSpeedRatings'])) {
                $data['iso'] = $exif['EXIF']['ISOSpeedRatings'];
            }
            if (isset($exif['GPS'])) {
                $data['has_gps'] = true;
            }

            return $data;
        } catch (\Throwable) {
            return [];
        }
    }
}
