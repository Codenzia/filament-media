<?php

namespace Codenzia\FilamentMedia\Services;

use Codenzia\FilamentMedia\Models\MediaFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class SearchService
{
    public function search(string $query, array $filters = []): Collection
    {
        if ($this->isScoutEnabled()) {
            return $this->scoutSearch($query, $filters);
        }

        return $this->databaseSearch($query, $filters);
    }

    public function searchFiles(string $query, ?int $folderId = null): Collection
    {
        $builder = MediaFile::search($query);

        if ($folderId !== null) {
            $builder = $builder->inFolder($folderId);
        }

        return $builder->get();
    }

    public function searchByTag(string $tagName): Collection
    {
        return MediaFile::whereHas('tags', function ($q) use ($tagName) {
            $q->where('name', 'LIKE', "%{$tagName}%");
        })->get();
    }

    public function searchByMetadata(string $fieldSlug, string $value): Collection
    {
        return MediaFile::withMetadataValue($fieldSlug, $value)->get();
    }

    /**
     * Advanced search with multiple criteria.
     *
     * Supported criteria:
     * - name: string (LIKE search)
     * - type: string (image, video, document, audio)
     * - tags: array of tag IDs
     * - metadata: array of [field_slug => value]
     * - date_from: string (Y-m-d)
     * - date_to: string (Y-m-d)
     * - size_min: int (bytes)
     * - size_max: int (bytes)
     * - folder_id: int|null
     */
    public function advancedSearch(array $criteria): Collection
    {
        $query = MediaFile::query();

        if ($name = Arr::get($criteria, 'name')) {
            $query->search($name);
        }

        if ($type = Arr::get($criteria, 'type')) {
            $query->filterByType($type);
        }

        if ($tags = Arr::get($criteria, 'tags')) {
            $query->tagged($tags);
        }

        if ($metadata = Arr::get($criteria, 'metadata')) {
            foreach ($metadata as $fieldSlug => $value) {
                $query->withMetadataValue($fieldSlug, $value);
            }
        }

        if ($dateFrom = Arr::get($criteria, 'date_from')) {
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($dateTo = Arr::get($criteria, 'date_to')) {
            $query->where('created_at', '<=', $dateTo . ' 23:59:59');
        }

        if ($sizeMin = Arr::get($criteria, 'size_min')) {
            $query->where('size', '>=', $sizeMin);
        }

        if ($sizeMax = Arr::get($criteria, 'size_max')) {
            $query->where('size', '<=', $sizeMax);
        }

        if (Arr::has($criteria, 'folder_id')) {
            $query->inFolder(Arr::get($criteria, 'folder_id'));
        }

        return $query->orderBy('name')->get();
    }

    public function isScoutEnabled(): bool
    {
        $config = config('filament-media.media.search.driver')
            ?? config('media.search.driver', 'database');

        return $config === 'scout' && class_exists(\Laravel\Scout\Searchable::class);
    }

    public function reindexAll(): void
    {
        if (! $this->isScoutEnabled()) {
            return;
        }

        MediaFile::all()->searchable();
    }

    protected function scoutSearch(string $query, array $filters): Collection
    {
        $builder = MediaFile::search($query);

        return $builder->get();
    }

    protected function databaseSearch(string $query, array $filters): Collection
    {
        $builder = MediaFile::search($query);

        if ($type = Arr::get($filters, 'type')) {
            $builder->filterByType($type);
        }

        if ($folderId = Arr::get($filters, 'folder_id')) {
            $builder->inFolder($folderId);
        }

        if ($tags = Arr::get($filters, 'tags')) {
            $builder->tagged($tags);
        }

        return $builder->get();
    }
}
