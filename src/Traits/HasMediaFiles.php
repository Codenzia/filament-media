<?php

namespace Codenzia\FilamentMedia\Traits;

use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaFolder;
use Codenzia\FilamentMedia\Support\MediaAdder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\UploadedFile;

/**
 * Provides polymorphic media file relationships and convenience methods for
 * attaching, detaching, querying, and uploading media on Eloquent models.
 */
trait HasMediaFiles
{
    public function files(): MorphMany
    {
        return $this->morphMany(MediaFile::class, 'fileable');
    }

    public function folders(): MorphMany
    {
        return $this->morphMany(MediaFolder::class, 'fileable');
    }

    public function images(): MorphMany
    {
        return $this->files()->where('mime_type', 'like', 'image/%');
    }

    public function videos(): MorphMany
    {
        return $this->files()->where('mime_type', 'like', 'video/%');
    }

    public function documents(): MorphMany
    {
        return $this->files()->where(function ($query) {
            $query->where('mime_type', 'like', 'application/pdf')
                ->orWhere('mime_type', 'like', 'application/msword')
                ->orWhere('mime_type', 'like', 'application/vnd.%')
                ->orWhere('mime_type', 'like', 'text/%');
        });
    }

    public function audio(): MorphMany
    {
        return $this->files()->where('mime_type', 'like', 'audio/%');
    }

    // ──────────────────────────────────────────────────
    // Collection & Tag scoped access
    // ──────────────────────────────────────────────────

    public function mediaByCollection(string $collectionName): MorphMany
    {
        return $this->files()->whereHas('collections', function ($q) use ($collectionName) {
            $q->where('name', $collectionName);
        });
    }

    public function mediaByTag(string $tagName): MorphMany
    {
        return $this->files()->whereHas('tags', function ($q) use ($tagName) {
            $q->where('name', $tagName);
        });
    }

    // ──────────────────────────────────────────────────
    // Upload convenience (fluent builder)
    // ──────────────────────────────────────────────────

    /**
     * Start a fluent media upload from a file or local path.
     *
     * Usage:
     *   $model->addMedia($uploadedFile)->usingName('Photo')->save();
     *   $model->addMedia('/path/to/file.jpg')->toFolder($folderId)->save();
     */
    public function addMedia(UploadedFile|string $source): MediaAdder
    {
        $type = $source instanceof UploadedFile ? 'file' : 'path';

        return new MediaAdder($this, $source, $type);
    }

    /**
     * Start a fluent media upload from a URL.
     *
     * Usage:
     *   $model->addMediaFromUrl('https://example.com/photo.jpg')->withAlt('Sunset')->save();
     */
    public function addMediaFromUrl(string $url): MediaAdder
    {
        return new MediaAdder($this, $url, 'url');
    }

    // ──────────────────────────────────────────────────
    // Attach / Detach / Sync
    // ──────────────────────────────────────────────────

    public function attachMediaFile(MediaFile $file): MediaFile
    {
        $file->fileable()->associate($this);
        $file->save();

        return $file;
    }

    public function attachMediaFiles($files): static
    {
        foreach ($files as $file) {
            $this->attachMediaFile($file);
        }

        return $this;
    }

    public function attachMediaWithMeta(MediaFile $file, array $metadata = []): MediaFile
    {
        $this->attachMediaFile($file);

        if (! empty($metadata)) {
            app(\Codenzia\FilamentMedia\Services\MetadataService::class)
                ->setMetadata($file, $metadata);
        }

        return $file;
    }

    public function detachMediaFile(MediaFile $file): MediaFile
    {
        $file->fileable()->dissociate();
        $file->save();

        return $file;
    }

    /**
     * Find a media file by ID, verify it belongs to this model, and delete it with its physical file.
     */
    public function deleteMediaFile(int $fileId, ?string $successMessage = null, ?string $failedMessage = null): bool
    {
        $file = $this->files()->find($fileId);

        if (! $file) {
            return false;
        }

        $result = $file->deleteWithFile($successMessage, $failedMessage);

        if ($result) {
            $this->refresh();
        }

        return $result;
    }

    public function detachAllMediaFiles(): static
    {
        $this->files()->update([
            'fileable_type' => null,
            'fileable_id' => null,
        ]);

        return $this;
    }

    public function syncMediaFiles($files): static
    {
        $this->detachAllMediaFiles();
        $this->attachMediaFiles($files);

        return $this;
    }

    /**
     * Sync attached media by file IDs.
     * Detaches files no longer in the list, attaches new ones via morph.
     *
     * @param  array<int>  $ids  Media file IDs to keep attached
     * @param  string|null  $scope  Optional relationship method to scope detach (e.g. 'images', 'videos')
     */
    public function syncMediaByIds(array $ids, ?string $scope = null): static
    {
        $query = $scope ? $this->{$scope}() : $this->files();

        $query->whereNotIn('id', $ids)
            ->update(['fileable_type' => null, 'fileable_id' => null]);

        if (! empty($ids)) {
            MediaFile::withoutGlobalScopes()
                ->whereIn('id', $ids)
                ->update([
                    'fileable_type' => $this->getMorphClass(),
                    'fileable_id' => $this->getKey(),
                ]);
        }

        return $this;
    }

    // ──────────────────────────────────────────────────
    // Queries
    // ──────────────────────────────────────────────────

    public function getFirstMediaFile(): ?MediaFile
    {
        return $this->files()->first();
    }

    public function getFirstImage(): ?MediaFile
    {
        return $this->images()->first();
    }

    /**
     * Get the first media URL for a given scope, with optional fallback.
     *
     * @param  string  $scope  Relationship method name (e.g. 'files', 'images', 'videos')
     * @param  string|null  $fallback  URL to return if no media exists
     */
    public function getFirstMediaUrl(string $scope = 'files', ?string $fallback = null): ?string
    {
        return $this->{$scope}()->first()?->preview_url ?? $fallback;
    }

    public function getFirstImageUrl(?string $fallback = null): ?string
    {
        return $this->getFirstMediaUrl('images', $fallback);
    }

    public function getMediaUrls(?string $collection = null): array
    {
        $query = $collection ? $this->mediaByCollection($collection) : $this->files();

        return $query->get()->map(fn ($file): ?string => $file->preview_url)->filter()->values()->toArray();
    }

    public function clearMedia(?string $collection = null): static
    {
        if ($collection) {
            $this->mediaByCollection($collection)->update([
                'fileable_type' => null,
                'fileable_id' => null,
            ]);
        } else {
            $this->detachAllMediaFiles();
        }

        return $this;
    }

    /**
     * Check if the model has any media of a specific scope.
     *
     * @param  string  $scope  Relationship method name (e.g. 'files', 'images', 'videos')
     */
    public function hasMedia(string $scope = 'files'): bool
    {
        return $this->{$scope}()->exists();
    }

    public function hasMediaFiles(): bool
    {
        return $this->hasMedia('files');
    }

    public function hasImages(): bool
    {
        return $this->hasMedia('images');
    }
}
