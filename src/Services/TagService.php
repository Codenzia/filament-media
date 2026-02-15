<?php

namespace Codenzia\FilamentMedia\Services;

use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaFolder;
use Codenzia\FilamentMedia\Models\MediaTag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Manages tags and collections for media files and folders,
 * including attaching, syncing, merging, and collection CRUD operations.
 */
class TagService
{
    public function attachTags(MediaFile|MediaFolder $item, array $tagNames): void
    {
        $tagIds = collect($tagNames)->map(fn (string $name) => $this->findOrCreate($name)->id);

        $item->tags()->syncWithoutDetaching($tagIds);
    }

    public function detachTags(MediaFile|MediaFolder $item, array $tagIds): void
    {
        $item->tags()->detach($tagIds);
    }

    public function syncTags(MediaFile|MediaFolder $item, array $tagNames): void
    {
        $tagIds = collect($tagNames)->map(fn (string $name) => $this->findOrCreate($name)->id);

        $item->tags()->sync($tagIds);
    }

    public function getPopularTags(int $limit = 20): Collection
    {
        return MediaTag::tags()
            ->withCount('files')
            ->orderByDesc('files_count')
            ->limit($limit)
            ->get();
    }

    public function findOrCreate(string $name, string $type = 'tag'): MediaTag
    {
        return MediaTag::findOrCreateByName($name, $type);
    }

    public function mergeTags(array $sourceTagIds, int $targetTagId): void
    {
        $target = MediaTag::findOrFail($targetTagId);

        foreach ($sourceTagIds as $sourceId) {
            if ($sourceId === $targetTagId) {
                continue;
            }

            $source = MediaTag::findOrFail($sourceId);

            // Move file associations
            $existingFileIds = $target->files()->pluck('media_files.id')->toArray();
            $newFileIds = $source->files()->pluck('media_files.id')
                ->diff($existingFileIds)
                ->toArray();
            $target->files()->syncWithoutDetaching($newFileIds);

            // Move folder associations
            $existingFolderIds = $target->folders()->pluck('media_folders.id')->toArray();
            $newFolderIds = $source->folders()->pluck('media_folders.id')
                ->diff($existingFolderIds)
                ->toArray();
            $target->folders()->syncWithoutDetaching($newFolderIds);

            $source->delete();
        }
    }

    // ──────────────────────────────────────────────────
    // Collections
    // ──────────────────────────────────────────────────

    public function createCollection(string $name, ?string $description = null): MediaTag
    {
        return MediaTag::create([
            'name' => $name,
            'slug' => MediaTag::createSlug($name),
            'type' => 'collection',
            'description' => $description,
        ]);
    }

    public function addToCollection(int $collectionId, array $fileIds): void
    {
        $collection = MediaTag::collections()->findOrFail($collectionId);

        $collection->files()->syncWithoutDetaching($fileIds);
    }

    public function removeFromCollection(int $collectionId, array $fileIds): void
    {
        $collection = MediaTag::collections()->findOrFail($collectionId);

        $collection->files()->detach($fileIds);
    }

    public function getCollections(): Collection
    {
        return MediaTag::collections()
            ->withCount('files')
            ->orderBy('name')
            ->get();
    }

    public function getCollectionContents(int $collectionId): Collection
    {
        $collection = MediaTag::collections()->findOrFail($collectionId);

        return $collection->files()->orderBy('name')->get();
    }
}
