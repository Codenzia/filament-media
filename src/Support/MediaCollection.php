<?php

namespace Codenzia\FilamentMedia\Support;

/**
 * Defines a named media collection with constraints and defaults.
 *
 * Register collections in your model's registerMediaCollections() method:
 *
 *   $this->addMediaCollection('avatar')
 *       ->singleFile()
 *       ->acceptsMimeTypes(['image/*']);
 *
 *   $this->addMediaCollection('gallery')
 *       ->onlyKeepLatest(20)
 *       ->useFallbackUrl('/images/placeholder.jpg');
 */
class MediaCollection
{
    protected bool $isSingleFile = false;

    protected ?int $maxFiles = null;

    protected array $acceptedMimeTypes = [];

    protected ?string $fallbackUrl = null;

    public function __construct(
        public readonly string $name,
    ) {}

    /**
     * Only allow a single file in this collection.
     * Adding a new file will auto-detach the previous one.
     */
    public function singleFile(): static
    {
        $this->isSingleFile = true;
        $this->maxFiles = 1;

        return $this;
    }

    /**
     * Limit the collection to N files, auto-detaching the oldest beyond this count.
     */
    public function onlyKeepLatest(int $count): static
    {
        $this->maxFiles = $count;

        return $this;
    }

    /**
     * Restrict accepted MIME types for this collection.
     *
     * @param  array<string>  $types  e.g. ['image/*'], ['application/pdf', 'image/png']
     */
    public function acceptsMimeTypes(array $types): static
    {
        $this->acceptedMimeTypes = $types;

        return $this;
    }

    /**
     * URL to return when the collection is empty (e.g. default avatar).
     */
    public function useFallbackUrl(string $url): static
    {
        $this->fallbackUrl = $url;

        return $this;
    }

    public function isSingleFile(): bool
    {
        return $this->isSingleFile;
    }

    public function getMaxFiles(): ?int
    {
        return $this->maxFiles;
    }

    public function getAcceptedMimeTypes(): array
    {
        return $this->acceptedMimeTypes;
    }

    public function getFallbackUrl(): ?string
    {
        return $this->fallbackUrl;
    }
}
