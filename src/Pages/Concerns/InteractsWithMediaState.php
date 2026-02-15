<?php

namespace Codenzia\FilamentMedia\Pages\Concerns;

use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaFolder;
use Codenzia\FilamentMedia\Models\MediaSetting;
use Codenzia\FilamentMedia\Services\FileOperationService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

trait InteractsWithMediaState
{
    public function mount(): void
    {
        $this->loadUserPreferences();
    }

    protected function loadUserPreferences(): void
    {
        $preferences = MediaSetting::query()
            ->where('key', 'user_preferences')
            ->where('user_id', Auth::guard()->id())
            ->first();

        if ($preferences && is_array($preferences->value)) {
            $this->viewType = $preferences->value['view_type'] ?? 'grid';
            $this->showDetailsPanel = $preferences->value['show_details'] ?? true;
        }
    }

    protected function saveUserPreferences(): void
    {
        MediaSetting::query()->updateOrCreate(
            [
                'key' => 'user_preferences',
                'user_id' => Auth::guard()->id(),
            ],
            [
                'value' => [
                    'view_type' => $this->viewType,
                    'show_details' => $this->showDetailsPanel,
                ],
            ]
        );
    }

    public function navigateToFolder(int $folderId): void
    {
        $this->folderId = $folderId;
        $this->currentPage = 1;
        $this->selectedItems = [];
        unset($this->items, $this->breadcrumbs);
    }

    public function setViewIn(string $viewIn): void
    {
        $this->viewIn = $viewIn;
        $this->folderId = 0;
        $this->collectionId = 0;
        $this->currentPage = 1;
        $this->selectedItems = [];
        unset($this->items, $this->breadcrumbs);
    }

    public function setCollection(int $collectionId): void
    {
        $this->viewIn = 'collections';
        $this->collectionId = $collectionId;
        $this->folderId = 0;
        $this->currentPage = 1;
        $this->selectedItems = [];
        unset($this->items, $this->breadcrumbs);
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->currentPage = 1;
        unset($this->items);
    }

    public function setSortBy(string $sortBy): void
    {
        $this->sortBy = $sortBy;
        $this->currentPage = 1;
        unset($this->items);
    }

    public function setViewType(string $viewType): void
    {
        $this->viewType = $viewType;
        $this->saveUserPreferences();
    }

    public function toggleDetailsPanel(): void
    {
        $this->showDetailsPanel = ! $this->showDetailsPanel;
        $this->saveUserPreferences();
    }

    public function updatedSearch(): void
    {
        $this->currentPage = 1;
        unset($this->items);
    }

    public function selectItem(array $item, bool $addToSelection = false): void
    {
        if ($addToSelection) {
            $exists = collect($this->selectedItems)->contains(
                fn($i) => $i['id'] === $item['id'] && $i['is_folder'] === $item['is_folder']
            );

            if ($exists) {
                $this->selectedItems = collect($this->selectedItems)
                    ->reject(fn($i) => $i['id'] === $item['id'] && $i['is_folder'] === $item['is_folder'])
                    ->values()
                    ->all();
            } else {
                $this->selectedItems[] = $item;
            }
        } else {
            $this->selectedItems = [$item];
        }

        unset($this->selectedItemDetails);
    }

    public function selectAll(): void
    {
        $this->selectedItems = $this->items->map(fn($item) => [
            'id' => $item['id'],
            'is_folder' => $item['is_folder'] ?? false,
        ])->all();
    }

    public function clearSelection(): void
    {
        $this->selectedItems = [];
        unset($this->selectedItemDetails);
    }

    public function openItem(array $item): void
    {
        if ($item['is_folder'] ?? false) {
            $this->navigateToFolder($item['id']);
        } else {
            $fileIds = $this->items
                ->filter(fn($i) => ! ($i['is_folder'] ?? false))
                ->pluck('id')
                ->values()
                ->all();

            $this->dispatch('open-preview-modal', fileId: (int) $item['id'], fileIds: $fileIds);
        }
    }

    public function closePreview(): void
    {
        $this->previewItem = null;
    }

    public function selectByIndex(int $index, bool $addToSelection = false): void
    {
        $item = $this->items->get($index);
        if (! $item) {
            return;
        }

        $this->selectItem([
            'id' => $item['id'],
            'is_folder' => $item['is_folder'] ?? false,
        ], $addToSelection);
    }

    public function toggleSelectionByIndex(int $index): void
    {
        $item = $this->items->get($index);
        if (! $item) {
            return;
        }

        $itemData = [
            'id' => $item['id'],
            'is_folder' => $item['is_folder'] ?? false,
        ];

        $isSelected = collect($this->selectedItems)->contains(
            fn($i) => $i['id'] === $itemData['id'] && ($i['is_folder'] ?? false) === $itemData['is_folder']
        );

        if ($isSelected) {
            $this->selectedItems = collect($this->selectedItems)
                ->reject(fn($i) => $i['id'] === $itemData['id'] && ($i['is_folder'] ?? false) === $itemData['is_folder'])
                ->values()
                ->all();
        } else {
            $this->selectedItems[] = $itemData;
        }

        unset($this->selectedItemDetails);
    }

    public function openItemByIndex(int $index): void
    {
        $item = $this->items->get($index);
        if (! $item) {
            return;
        }

        $this->openItem([
            'id' => $item['id'],
            'is_folder' => $item['is_folder'] ?? false,
        ]);
    }

    public function moveItemsToFolder(array $items, int $destinationFolderId): void
    {
        if (empty($items)) {
            return;
        }

        $fileOps = app(FileOperationService::class);

        foreach ($items as $item) {
            $isFolder = $item['is_folder'] ?? false;
            $id = $item['id'];

            if ($isFolder && $id === $destinationFolderId) {
                Notification::make()
                    ->title(trans('filament-media::media.move_error'))
                    ->danger()
                    ->send();

                return;
            }

            if ($isFolder) {
                $folder = MediaFolder::find($id);
                if ($folder) {
                    $folder->update(['parent_id' => $destinationFolderId]);
                }
            } else {
                $file = MediaFile::find($id);
                if ($file) {
                    $fileOps->moveFile($file, $destinationFolderId);
                }
            }
        }

        $this->selectedItems = [];
        $this->refresh();

        Notification::make()
            ->title(trans('filament-media::media.move_success'))
            ->success()
            ->send();
    }

    public function refresh(): void
    {
        $this->currentPage = 1;
        $this->refreshKey = now()->timestamp;
        unset($this->items, $this->breadcrumbs);
    }

    public function loadMore(): void
    {
        $this->currentPage++;
    }
}
