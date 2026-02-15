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

trait InteractsWithMediaQueries
{
    #[Computed]
    public function items(): Collection
    {
        $_ = $this->refreshKey;

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
        $folder = MediaFolder::find($id);
        if (! $folder) {
            return null;
        }

        $fileCount = MediaFile::where('folder_id', $folder->id)->count();
        $folderCount = MediaFolder::where('parent_id', $folder->id)->count();

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
        $file = MediaFile::find($id);
        if (! $file) {
            return null;
        }

        $urlService = app(MediaUrlService::class);
        $fileExists = $urlService->fileExists($file->url);

        $fullUrl = $urlService->url($file->url);

        return [
            'type' => 'file',
            'id' => $file->id,
            'name' => $file->name,
            'url' => $fullUrl,
            'mime_type' => $file->mime_type,
            'size' => BaseHelper::humanFilesize($file->size),
            'size_raw' => $file->size,
            'created_at' => $file->created_at?->format('M d, Y H:i'),
            'updated_at' => $file->updated_at?->format('M d, Y H:i'),
            'alt' => $file->alt ?? '',
            'thumbnail' => $fileExists && $file->canGenerateThumbnails() ? $fullUrl : null,
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

    protected function queryAllMedia(): Collection
    {
        $folders = MediaFolder::inParent($this->folderId)
            ->search($this->search)
            ->sorted($this->sortBy)
            ->get();

        $files = MediaFile::inFolder($this->folderId)
            ->filterByType($this->filter)
            ->search($this->search)
            ->sorted($this->sortBy)
            ->paginate($this->perPage, ['*'], 'page', $this->currentPage);

        return $this->mergeResults($folders, collect($files->items()));
    }

    protected function queryTrashedItems(): Collection
    {
        if ($this->folderId > 0) {
            $folders = MediaFolder::onlyTrashed()
                ->inParent($this->folderId)
                ->search($this->search)
                ->sorted($this->sortBy)
                ->get();

            $files = MediaFile::onlyTrashed()
                ->inFolder($this->folderId)
                ->filterByType($this->filter)
                ->search($this->search)
                ->sorted($this->sortBy)
                ->paginate($this->perPage, ['*'], 'page', $this->currentPage);

            return $this->mergeResults($folders, collect($files->items()));
        }

        $folders = MediaFolder::onlyTrashed()
            ->search($this->search)
            ->sorted($this->sortBy)
            ->get();

        $files = MediaFile::onlyTrashed()
            ->filterByType($this->filter)
            ->search($this->search)
            ->sorted($this->sortBy)
            ->paginate($this->perPage, ['*'], 'page', $this->currentPage);

        return $this->mergeResults($folders, collect($files->items()));
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

        return $this->mergeResults($folders, $files);
    }

    protected function queryCollectionItems(): Collection
    {
        if ($this->collectionId === 0) {
            return collect();
        }

        $files = MediaFile::inCollection($this->collectionId)
            ->filterByType($this->filter)
            ->search($this->search)
            ->sorted($this->sortBy)
            ->paginate($this->perPage, ['*'], 'page', $this->currentPage);

        return $this->mergeResults(collect(), collect($files->items()));
    }

    protected function mergeResults(Collection $folders, Collection $files): Collection
    {
        $folderResources = $folders->isNotEmpty()
            ? FolderResource::collection($folders)->resolve()
            : [];

        $fileResources = $files->isNotEmpty()
            ? FileResource::collection($files)->resolve()
            : [];

        return collect(array_merge($folderResources, $fileResources));
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
