<?php

namespace Codenzia\FilamentMedia\Traits;

use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaFolder;
use Codenzia\FilamentMedia\Services\UploadService;
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
    // Upload convenience
    // ──────────────────────────────────────────────────

    public function addMedia(UploadedFile $file, ?string $collection = null): MediaFile
    {
        $result = app(UploadService::class)->handleUpload($file);

        if ($result['error']) {
            throw new \RuntimeException($result['message']);
        }

        $mediaFile = MediaFile::find($result['data']->id);
        $this->attachMediaFile($mediaFile);

        if ($collection) {
            $tag = \Codenzia\FilamentMedia\Models\MediaTag::findOrCreateByName($collection, 'collection');
            $mediaFile->tags()->syncWithoutDetaching([$tag->id]);
        }

        return $mediaFile;
    }

    public function addMediaFromUrl(string $url, ?string $collection = null): MediaFile
    {
        $result = app(UploadService::class)->uploadFromUrl($url);

        if ($result['error']) {
            throw new \RuntimeException($result['message']);
        }

        $mediaFile = MediaFile::find($result['data']->id);
        $this->attachMediaFile($mediaFile);

        if ($collection) {
            $tag = \Codenzia\FilamentMedia\Models\MediaTag::findOrCreateByName($collection, 'collection');
            $mediaFile->tags()->syncWithoutDetaching([$tag->id]);
        }

        return $mediaFile;
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

    public function attachMediaFiles($files): void
    {
        foreach ($files as $file) {
            $this->attachMediaFile($file);
        }
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

    public function detachAllMediaFiles(): void
    {
        $this->files()->update([
            'fileable_type' => null,
            'fileable_id' => null,
        ]);
    }

    public function syncMediaFiles($files): void
    {
        $this->detachAllMediaFiles();
        $this->attachMediaFiles($files);
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

    public function getFirstMediaUrl(): ?string
    {
        return $this->getFirstMediaFile()?->url;
    }

    public function getFirstImageUrl(): ?string
    {
        return $this->getFirstImage()?->url;
    }

    public function getMediaUrls(?string $collection = null): array
    {
        $query = $collection ? $this->mediaByCollection($collection) : $this->files();

        return $query->pluck('url')->toArray();
    }

    public function clearMedia(?string $collection = null): void
    {
        if ($collection) {
            $this->mediaByCollection($collection)->update([
                'fileable_type' => null,
                'fileable_id' => null,
            ]);
        } else {
            $this->detachAllMediaFiles();
        }
    }

    public function hasMediaFiles(): bool
    {
        return $this->files()->exists();
    }

    public function hasImages(): bool
    {
        return $this->images()->exists();
    }
}
