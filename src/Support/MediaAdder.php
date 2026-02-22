<?php

namespace Codenzia\FilamentMedia\Support;

use Codenzia\FilamentMedia\Exceptions\MediaUploadException;
use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaTag;
use Codenzia\FilamentMedia\Services\MetadataService;
use Codenzia\FilamentMedia\Services\UploadService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

/**
 * Fluent builder for adding media to models.
 *
 * Usage:
 *   $model->addMedia($file)->usingName('Photo')->toFolder($folderId)->save();
 *   $model->addMediaFromUrl($url)->withAlt('Description')->toCollection('gallery')->save();
 *   $model->addMedia('/path/to/file.jpg')->usingName('Local file')->save();
 *
 * @throws MediaUploadException
 */
class MediaAdder
{
    protected ?string $name = null;

    protected ?string $alt = null;

    protected ?string $description = null;

    protected array $customProperties = [];

    protected int|string $folderId = 0;

    protected ?string $folderSlug = null;

    protected string $visibility = 'public';

    protected ?string $collection = null;

    public function __construct(
        protected Model $model,
        protected UploadedFile|string $source,
        protected string $sourceType = 'file',
    ) {}

    public function usingName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function withAlt(string $alt): static
    {
        $this->alt = $alt;

        return $this;
    }

    public function withDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Set custom metadata key-value pairs (stored via MetadataService).
     *
     * @param  array<string, mixed>  $properties  Field ID => value pairs
     */
    public function withProperties(array $properties): static
    {
        $this->customProperties = $properties;

        return $this;
    }

    public function toFolder(int|string $folderId): static
    {
        $this->folderId = $folderId;

        return $this;
    }

    /**
     * Specify a folder path slug (e.g. 'properties/villa-1').
     * The UploadService will resolve or create the folder hierarchy.
     */
    public function toFolderPath(string $folderSlug): static
    {
        $this->folderSlug = $folderSlug;

        return $this;
    }

    public function withVisibility(string $visibility): static
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Tag the uploaded file with a named collection.
     */
    public function toCollection(string $collection): static
    {
        $this->collection = $collection;

        return $this;
    }

    /**
     * Execute the upload, attach to the model, and return the MediaFile.
     *
     * @throws MediaUploadException
     */
    public function save(): MediaFile
    {
        $uploadService = app(UploadService::class);

        $mediaFile = match ($this->sourceType) {
            'url' => $uploadService->uploadFromUrl(
                $this->source,
                $this->folderId,
                $this->folderSlug,
            ),
            'path' => $uploadService->uploadFromPath(
                $this->source,
                $this->folderId,
                $this->folderSlug,
            ),
            default => $uploadService->handleUpload(
                $this->source,
                $this->folderId,
                $this->folderSlug,
                visibility: $this->visibility,
            ),
        };

        // Apply custom attributes
        $updates = array_filter([
            'name' => $this->name,
            'alt' => $this->alt,
            'description' => $this->description,
        ]);

        if (! empty($updates)) {
            $mediaFile->update($updates);
        }

        // Attach to model via morph
        $mediaFile->fileable()->associate($this->model);
        $mediaFile->save();

        // Tag with collection and enforce limits
        if ($this->collection) {
            $tag = MediaTag::findOrCreateByName($this->collection, 'collection');
            $mediaFile->tags()->syncWithoutDetaching([$tag->id]);

            // Enforce collection constraints (singleFile, onlyKeepLatest) if the model uses InteractsWithMediaCollections
            if (method_exists($this->model, 'enforceCollectionLimits')) {
                $this->model->enforceCollectionLimits($this->collection);
            }
        }

        // Set metadata via MetadataService
        if (! empty($this->customProperties)) {
            app(MetadataService::class)->setMetadata($mediaFile, $this->customProperties);
        }

        return $mediaFile;
    }
}
