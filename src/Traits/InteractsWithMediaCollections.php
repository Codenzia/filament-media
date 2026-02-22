<?php

namespace Codenzia\FilamentMedia\Traits;

use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaTag;
use Codenzia\FilamentMedia\Support\MediaCollection;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Adds model-level named media collections with constraints like singleFile(),
 * onlyKeepLatest(), MIME type validation, and fallback URLs.
 *
 * Usage in your model:
 *
 *   use HasMediaFiles, InteractsWithMediaCollections;
 *
 *   public function registerMediaCollections(): void
 *   {
 *       $this->addMediaCollection('avatar')
 *           ->singleFile()
 *           ->acceptsMimeTypes(['image/*'])
 *           ->useFallbackUrl('/images/default-avatar.png');
 *
 *       $this->addMediaCollection('gallery')
 *           ->onlyKeepLatest(20);
 *   }
 *
 * Then use via:
 *   $model->addMedia($file)->toCollection('avatar')->save();
 *   $model->getMedia('avatar');
 *   $model->getFirstMediaUrl('avatar');
 */
trait InteractsWithMediaCollections
{
    /** @var array<string, MediaCollection> */
    protected array $mediaCollections = [];

    /**
     * Override in models to define named collections with constraints.
     */
    public function registerMediaCollections(): void
    {
        // Override in model
    }

    /**
     * Register a named media collection and return it for fluent configuration.
     */
    public function addMediaCollection(string $name): MediaCollection
    {
        $collection = new MediaCollection($name);
        $this->mediaCollections[$name] = $collection;

        return $collection;
    }

    /**
     * Get a registered collection definition by name.
     */
    public function getMediaCollection(string $name): ?MediaCollection
    {
        $this->ensureCollectionsRegistered();

        return $this->mediaCollections[$name] ?? null;
    }

    /**
     * Get all registered collection definitions.
     *
     * @return array<string, MediaCollection>
     */
    public function getRegisteredMediaCollections(): array
    {
        $this->ensureCollectionsRegistered();

        return $this->mediaCollections;
    }

    /**
     * Get media files belonging to a named collection.
     */
    public function getMedia(string $collectionName = 'default'): MorphMany
    {
        return $this->files()->whereHas('collections', function ($q) use ($collectionName): void {
            $q->where('name', $collectionName);
        });
    }

    /**
     * Get the first media URL for a named collection, with automatic fallback.
     */
    public function getFirstCollectionUrl(string $collection = 'default'): ?string
    {
        $url = $this->getMedia($collection)->first()?->preview_url;

        if ($url) {
            return $url;
        }

        $definition = $this->getMediaCollection($collection);

        return $definition?->getFallbackUrl();
    }

    /**
     * Enforce collection constraints after adding a file to a collection.
     * Called automatically by MediaAdder when saving to a collection.
     */
    public function enforceCollectionLimits(string $collectionName): void
    {
        $definition = $this->getMediaCollection($collectionName);

        if (! $definition) {
            return;
        }

        $maxFiles = $definition->getMaxFiles();

        if ($maxFiles === null) {
            return;
        }

        $collectionQuery = $this->getMedia($collectionName);
        $count = $collectionQuery->count();

        if ($count <= $maxFiles) {
            return;
        }

        // Detach oldest files beyond the limit
        $toDetach = $collectionQuery
            ->orderBy('created_at', 'asc')
            ->limit($count - $maxFiles)
            ->pluck('id');

        MediaFile::withoutGlobalScopes()
            ->whereIn('id', $toDetach)
            ->update([
                'fileable_type' => null,
                'fileable_id' => null,
            ]);
    }

    /**
     * Validate that a MIME type is accepted by the collection.
     */
    public function validateCollectionMimeType(string $collectionName, string $mimeType): bool
    {
        $definition = $this->getMediaCollection($collectionName);

        if (! $definition) {
            return true;
        }

        $accepted = $definition->getAcceptedMimeTypes();

        if (empty($accepted)) {
            return true;
        }

        foreach ($accepted as $pattern) {
            if ($pattern === $mimeType) {
                return true;
            }

            // Support wildcard patterns like 'image/*'
            if (str_ends_with($pattern, '/*')) {
                $prefix = substr($pattern, 0, -1);
                if (str_starts_with($mimeType, $prefix)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Ensure registerMediaCollections() has been called.
     */
    protected function ensureCollectionsRegistered(): void
    {
        if (empty($this->mediaCollections)) {
            $this->registerMediaCollections();
        }
    }
}
