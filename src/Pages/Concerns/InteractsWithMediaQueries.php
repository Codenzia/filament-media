<?php

namespace Codenzia\FilamentMedia\Pages\Concerns;

use Codenzia\FilamentMedia\Facades\FilamentMedia;
use Codenzia\FilamentMedia\Helpers\BaseHelper;
use Codenzia\FilamentMedia\Http\Resources\FileResource;
use Codenzia\FilamentMedia\Http\Resources\FolderResource;
use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaFolder;
use Codenzia\FilamentMedia\Models\MediaTag;
use Codenzia\FilamentMedia\Services\FavoriteService;
use Codenzia\FilamentMedia\Services\MediaUrlService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

/**
 * Provides computed Livewire properties for querying media data.
 *
 * Exposes items, breadcrumbs, selected item details, and sidebar data as
 * computed properties, with query strategies for all media, trash, recent,
 * favorites, and collection views.
 */
trait InteractsWithMediaQueries
{
    #[Computed]
    public function items(): Collection
    {
        $_ = $this->refreshKey;

        // Reset pagination counters (paginated views set them in paginatedFiles)
        $this->totalFileCount = 0;
        $this->displayedFileCount = 0;
        $this->hasMorePages = false;

        $items = collect();

        switch ($this->viewIn) {
            case 'trash':
                $items = $this->queryTrashedItems();
                break;

            case 'recent':
                $items = $this->queryRecentItems();
                break;

            case 'favorites':
                $items = $this->queryFavoriteItems();
                break;

            case 'collections':
                $items = $this->queryCollectionItems();
                break;

            default:
                $items = $this->queryAllMedia();
                break;
        }

        return $items;
    }

    #[Computed]
    public function breadcrumbs(): array
    {
        $breadcrumbs = [
            [
                'id' => 0,
                'name' => $this->getViewInLabel(),
                'icon' => $this->getViewInIcon(),
            ],
        ];

        if ($this->folderId > 0) {
            $folder = $this->viewIn === 'trash'
                ? MediaFolder::withTrashed()->find($this->folderId)
                : MediaFolder::find($this->folderId);

            if ($folder) {
                // Build breadcrumbs from the parents accessor
                $parents = $folder->parents->reverse();

                foreach ($parents as $parent) {
                    $breadcrumbs[] = [
                        'id' => $parent->id,
                        'name' => $parent->name,
                    ];
                }

                $breadcrumbs[] = [
                    'id' => $folder->id,
                    'name' => $folder->name,
                ];
            }
        }

        return $breadcrumbs;
    }

    #[Computed]
    public function selectedItemDetails(): ?array
    {
        if (count($this->selectedItems) !== 1) {
            return null;
        }

        $item = $this->selectedItems[0];
        $isFolder = $item['is_folder'] ?? false;

        if ($isFolder) {
            return $this->getFolderDetails($item['id']);
        }

        return $this->getFileDetails($item['id']);
    }

    protected function getFolderDetails(int $id): ?array
    {
        $folder = MediaFolder::withTrashed()->find($id);
        if (! $folder) {
            return null;
        }

        $fileCount = MediaFile::withTrashed()->where('folder_id', $folder->id)->count();
        $folderCount = MediaFolder::withTrashed()->where('parent_id', $folder->id)->count();

        return [
            'type' => 'folder',
            'id' => $folder->id,
            'name' => $folder->name,
            'created_at' => $folder->created_at?->format('M d, Y H:i'),
            'updated_at' => $folder->updated_at?->format('M d, Y H:i'),
            'items_count' => $fileCount + $folderCount,
            'files_count' => $fileCount,
            'folders_count' => $folderCount,
            'color' => $folder->color,
        ];
    }

    protected function getFileDetails(int $id): ?array
    {
        $file = MediaFile::withTrashed()->find($id);
        if (! $file) {
            return null;
        }

        $urlService = app(MediaUrlService::class);
        $fileExists = $urlService->fileExists($file->url);

        $fullUrl = $urlService->visibilityAwareUrl($file);

        return [
            'type' => 'file',
            'id' => $file->id,
            'name' => $file->name,
            'url' => $fullUrl,
            'visibility' => $file->visibility,
            'mime_type' => $file->mime_type,
            'size' => BaseHelper::humanFilesize($file->size),
            'size_raw' => $file->size,
            'created_at' => $file->created_at?->format('M d, Y H:i'),
            'updated_at' => $file->updated_at?->format('M d, Y H:i'),
            'alt' => $file->alt ?? '',
            'thumbnail' => $this->resolveDetailsThumbnail($file, $fileExists, $fullUrl, $urlService),
            'file_type' => $file->type,
            'file_exists' => $fileExists,
            'linked_model' => $file->getLinkedModelInfo(),
            'tags' => $file->tags->pluck('name')->toArray(),
            'collections' => config('media.features.collections', true)
                ? $file->collections->pluck('name')->toArray()
                : [],
            'version_count' => config('media.features.versioning', true)
                ? $file->versions()->count()
                : 0,
            'latest_version' => config('media.features.versioning', true)
                ? optional($file->versions()->first(), fn ($v) => [
                    'version_number' => $v->version_number,
                    'created_at' => $v->created_at?->format('M d, Y'),
                ])
                : null,
        ];
    }

    protected function resolveDetailsThumbnail(MediaFile $file, bool $fileExists, string $fullUrl, MediaUrlService $urlService): ?string
    {
        if (! $fileExists) {
            return null;
        }

        if ($file->canGenerateThumbnails()) {
            return $urlService->url($file->url);
        }

        if ($file->visibility === 'private' && str_starts_with($file->mime_type ?? '', 'image/')) {
            return $fullUrl;
        }

        return null;
    }

    protected function queryAllMedia(): Collection
    {
        $folders = MediaFolder::inParent($this->folderId)
            ->search($this->search)
            ->sorted($this->sortBy)
            ->get();

        $files = $this->paginatedFiles(
            MediaFile::inFolder($this->folderId)
                ->filterByType($this->filter)
                ->search($this->search)
                ->sorted($this->sortBy)
        );

        return $this->mergeResults($folders, $files);
    }

    protected function queryTrashedItems(): Collection
    {
        if ($this->folderId > 0) {
            $folders = MediaFolder::onlyTrashed()
                ->inParent($this->folderId)
                ->search($this->search)
                ->sorted($this->sortBy)
                ->get();

            $files = $this->paginatedFiles(
                MediaFile::onlyTrashed()
                    ->inFolder($this->folderId)
                    ->filterByType($this->filter)
                    ->search($this->search)
                    ->sorted($this->sortBy)
            );

            return $this->mergeResults($folders, $files);
        }

        $folders = MediaFolder::onlyTrashed()
            ->search($this->search)
            ->sorted($this->sortBy)
            ->get();

        $files = $this->paginatedFiles(
            MediaFile::onlyTrashed()
                ->filterByType($this->filter)
                ->search($this->search)
                ->sorted($this->sortBy)
        );

        return $this->mergeResults($folders, $files);
    }

    protected function queryRecentItems(): Collection
    {
        $favoriteService = app(FavoriteService::class);

        if ($this->folderId > 0) {
            return $this->queryAllMedia();
        }

        $recentItems = $favoriteService->getRecentItems(Auth::guard()->id());
        if (empty($recentItems)) {
            return collect();
        }

        $fileIds = collect($recentItems)
            ->filter(fn($item) => ! ($item['is_folder'] ?? false))
            ->pluck('id')
            ->all();

        $folderIds = collect($recentItems)
            ->filter(fn($item) => $item['is_folder'] ?? false)
            ->pluck('id')
            ->all();

        $folders = ! empty($folderIds)
            ? MediaFolder::whereIn('id', $folderIds)->search($this->search)->get()
            : collect();

        $files = ! empty($fileIds)
            ? MediaFile::whereIn('id', $fileIds)->filterByType($this->filter)->search($this->search)->get()
            : collect();

        $this->totalFileCount = $files->count();
        $this->displayedFileCount = $files->count();

        return $this->mergeResults($folders, $files);
    }

    protected function queryFavoriteItems(): Collection
    {
        $favoriteService = app(FavoriteService::class);

        if ($this->folderId > 0) {
            return $this->queryAllMedia();
        }

        $favoriteItems = $favoriteService->getFavorites(Auth::guard()->id());
        if (empty($favoriteItems)) {
            return collect();
        }

        $fileIds = collect($favoriteItems)
            ->filter(fn($item) => ! ($item['is_folder'] ?? false))
            ->pluck('id')
            ->all();

        $folderIds = collect($favoriteItems)
            ->filter(fn($item) => $item['is_folder'] ?? false)
            ->pluck('id')
            ->all();

        $folders = ! empty($folderIds)
            ? MediaFolder::whereIn('id', $folderIds)->search($this->search)->get()
            : collect();

        $files = ! empty($fileIds)
            ? MediaFile::whereIn('id', $fileIds)->filterByType($this->filter)->search($this->search)->get()
            : collect();

        $this->totalFileCount = $files->count();
        $this->displayedFileCount = $files->count();

        return $this->mergeResults($folders, $files);
    }

    protected function queryCollectionItems(): Collection
    {
        if ($this->collectionId === 0) {
            return collect();
        }

        $files = $this->paginatedFiles(
            MediaFile::inCollection($this->collectionId)
                ->filterByType($this->filter)
                ->search($this->search)
                ->sorted($this->sortBy)
        );

        return $this->mergeResults(collect(), $files);
    }

    /**
     * Run a cumulative "load more" query: fetches perPage * currentPage items
     * and sets $this->hasMorePages so the view can show a Load More button.
     */
    protected function paginatedFiles(\Illuminate\Database\Eloquent\Builder $query): Collection
    {
        $limit = $this->perPage * $this->currentPage;
        $total = (clone $query)->count();
        $this->hasMorePages = $total > $limit;
        $this->totalFileCount = $total;

        $files = $query->take($limit)->get();
        $this->displayedFileCount = $files->count();

        return $files;
    }

    protected function mergeResults(Collection $folders, Collection $files): Collection
    {
        if ($folders->isNotEmpty()) {
            $folderIds = $folders->pluck('id')->toArray();
            $recursiveSizes = MediaFolder::getRecursiveSizeMap($folderIds);
            $recursiveCounts = MediaFolder::getRecursiveFileCountMap($folderIds, $this->filter);

            foreach ($folders as $folder) {
                $folder->files_sum_size = $recursiveSizes[$folder->id] ?? 0;
                $folder->total_file_count = $recursiveCounts['total'][$folder->id] ?? 0;
                $folder->filtered_file_count = $recursiveCounts['filtered'][$folder->id] ?? 0;
            }
        }

        $folderResources = $folders->isNotEmpty()
            ? FolderResource::collection($folders)->resolve()
            : [];

        $fileResources = $files->isNotEmpty()
            ? FileResource::collection($files)->resolve()
            : [];

        $merged = collect(array_merge($folderResources, $fileResources));

        $userId = Auth::guard()->id();
        if ($userId) {
            $favorites = collect(app(FavoriteService::class)->getFavorites($userId));
            $merged = $merged->map(function ($item) use ($favorites) {
                $item['is_favorited'] = $favorites->contains(
                    fn ($fav) => $fav['id'] === $item['id']
                        && ($fav['is_folder'] ?? false) === ($item['is_folder'] ?? false)
                );

                return $item;
            });
        }

        return $merged;
    }

    protected function getViewInLabel(): string
    {
        return match ($this->viewIn) {
            'trash' => trans('filament-media::media.trash'),
            'recent' => trans('filament-media::media.recent'),
            'favorites' => trans('filament-media::media.favorites'),
            'collections' => trans('filament-media::media.collections'),
            default => trans('filament-media::media.all_media'),
        };
    }

    protected function getViewInIcon(): string
    {
        return match ($this->viewIn) {
            'trash' => BaseHelper::renderIcon('heroicon-m-trash'),
            'recent' => BaseHelper::renderIcon('heroicon-m-clock'),
            'favorites' => BaseHelper::renderIcon('heroicon-m-star'),
            'collections' => BaseHelper::renderIcon('heroicon-m-rectangle-stack'),
            default => BaseHelper::renderIcon('heroicon-o-photo'),
        };
    }

    public function getViewData(): array
    {
        return [
            'sorts' => FilamentMedia::getSorts(),
            'mimeTypes' => config('media.mime_types', []),
            'sidebarDisplay' => config('media.sidebar_display', 'horizontal'),
            'allCollections' => config('media.features.collections', true)
                ? MediaTag::collections()->withCount('files')->orderBy('name')->get()
                : collect(),
        ];
    }
}
