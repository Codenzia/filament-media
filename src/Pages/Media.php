<?php

namespace Codenzia\FilamentMedia\Pages;

use Filament\Pages\Page;
use Codenzia\FilamentMedia\Facades\FilamentMedia;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use Livewire\WithFileUploads;
use Codenzia\FilamentMedia\Repositories\Interfaces\MediaFileInterface;
use Codenzia\FilamentMedia\Repositories\Interfaces\MediaFolderInterface;
use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaFolder;
use Codenzia\FilamentMedia\Models\MediaSetting;
use Codenzia\FilamentMedia\Http\Resources\FileResource;
use Codenzia\FilamentMedia\Http\Resources\FolderResource;
use Codenzia\FilamentMedia\Helpers\BaseHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;

class Media extends Page
{
    use WithFileUploads;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return FilamentMedia::getConfig('navigation.icon', static::$navigationIcon);
    }

    public static function getNavigationLabel(): string
    {
        return FilamentMedia::getConfig('navigation.label') ?: trans('filament-media::media.menu_name');
    }

    public static function getNavigationGroup(): ?string
    {
        return FilamentMedia::getConfig('navigation.group', null);
    }

    protected string $view = 'filament-media::pages.media';

    // Livewire state properties
    #[Url(as: 'folder')]
    public int $folderId = 0;

    #[Url(as: 'view')]
    public string $viewIn = 'all_media';

    #[Url(as: 'filter')]
    public string $filter = 'everything';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at-desc';

    #[Url(as: 'q')]
    public string $search = '';

    public string $viewType = 'grid';
    public array $selectedItems = [];
    public ?array $previewItem = null;
    public bool $showDetailsPanel = true;
    public bool $isLoading = false;

    // File uploads
    public $uploadedFiles = [];

    // Pagination
    public int $perPage = 30;
    public int $currentPage = 1;

    // Refresh key - used to force Livewire computed property cache invalidation
    public int $refreshKey = 0;

    protected array $sorts = [];

    public function mount(): void
    {
        $this->sorts = FilamentMedia::getSorts();

        // Load user preferences
        $this->loadUserPreferences();

        // Note: Assets are registered in FilamentMediaServiceProvider::getAssets()
        // No need to register them here - it causes duplicate/conflicting registrations
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

    #[Computed]
    public function items(): Collection
    {
        // Touch refreshKey to ensure computed property cache invalidates when it changes
        $_ = $this->refreshKey;

        $fileRepository = app(MediaFileInterface::class);
        $folderRepository = app(MediaFolderInterface::class);

        $paramsFile = [
            'order_by' => ['is_folder' => 'DESC'],
            'paginate' => [
                'per_page' => $this->perPage,
                'current_paged' => $this->currentPage,
            ],
            'filter' => $this->filter !== 'everything' ? $this->filter : null,
        ];

        $paramsFolder = [];

        // Apply sorting
        $orderBy = $this->transformOrderBy($this->sortBy);
        if (count($orderBy) === 2) {
            $paramsFile['order_by'][$orderBy[0]] = $orderBy[1];
        }

        // Apply search
        if ($this->search) {
            $paramsFolder['condition'] = [
                ['media_folders.name', 'LIKE', '%' . $this->search . '%'],
            ];
            $paramsFile['condition'] = [
                ['media_files.name', 'LIKE', '%' . $this->search . '%'],
            ];
        }

        $items = collect();

        switch ($this->viewIn) {
            case 'trash':
                $queried = $fileRepository->getTrashed(
                    $this->folderId,
                    $paramsFile,
                    true,
                    $paramsFolder
                );
                break;

            case 'recent':
                $recentItems = MediaSetting::query()
                    ->where([
                        'key' => 'recent_items',
                        'user_id' => Auth::guard()->id(),
                    ])->first();

                if ($this->folderId > 0) {
                    $queried = $fileRepository->getFilesByFolderId(
                        $this->folderId,
                        $paramsFile,
                        true,
                        $paramsFolder
                    );
                } elseif (!empty($recentItems) && !empty($recentItems->value)) {
                    $fileIds = collect($recentItems->value)
                        ->filter(fn($item) => !($item['is_folder'] ?? false))
                        ->pluck('id')
                        ->all();
                    $folderIds = collect($recentItems->value)
                        ->filter(fn($item) => $item['is_folder'] ?? false)
                        ->pluck('id')
                        ->all();

                    if (count($fileIds) > 0) {
                        $paramsFile['condition'][] = ['media_files.id', 'IN', $fileIds];
                    }
                    if (count($folderIds) > 0) {
                        $paramsFolder['condition'][] = ['media_folders.id', 'IN', $folderIds];
                    }

                    $queried = $fileRepository->getFilesByFolderId(0, $paramsFile, true, $paramsFolder);
                } else {
                    $queried = collect();
                }
                break;

            case 'favorites':
                $favoriteItems = MediaSetting::query()
                    ->where([
                        'key' => 'favorites',
                        'user_id' => Auth::guard()->id(),
                    ])->first();

                if (!empty($favoriteItems) && !empty($favoriteItems->value)) {
                    $favoriteCollection = collect($favoriteItems->value);

                    $fileIds = $favoriteCollection
                        ->filter(fn($item) => !($item['is_folder'] ?? false))
                        ->pluck('id')
                        ->all();
                    $folderIds = $favoriteCollection
                        ->filter(fn($item) => $item['is_folder'] ?? false)
                        ->pluck('id')
                        ->all();

                    if ($this->folderId > 0) {
                        $queried = $fileRepository->getFilesByFolderId(
                            $this->folderId,
                            $paramsFile,
                            true,
                            $paramsFolder
                        );
                    } else {
                        if (count($fileIds) > 0) {
                            $paramsFile['condition'][] = ['media_files.id', 'IN', $fileIds];
                        }
                        if (count($folderIds) > 0) {
                            $paramsFolder['condition'][] = ['media_folders.id', 'IN', $folderIds];
                        }
                        $paramsFile['is_favorite'] = true;

                        $queried = $fileRepository->getFilesByFolderId(0, $paramsFile, true, $paramsFolder);
                    }
                } else {
                    $queried = collect();
                }
                break;

            default: // all_media
                $queried = $fileRepository->getFilesByFolderId(
                    $this->folderId,
                    $paramsFile,
                    true,
                    $paramsFolder
                );
                break;
        }

        if ($queried instanceof Collection && $queried->isNotEmpty()) {
            $folders = FolderResource::collection($queried->where('is_folder', 1))->resolve();
            $files = FileResource::collection($queried->where('is_folder', 0))->resolve();
            $items = collect(array_merge($folders, $files));
        }

        return $items;
    }

    #[Computed]
    public function breadcrumbs(): array
    {
        $folderRepository = app(MediaFolderInterface::class);

        $breadcrumbs = [
            [
                'id' => 0,
                'name' => $this->getViewInLabel(),
                'icon' => $this->getViewInIcon(),
            ],
        ];

        if ($this->folderId > 0) {
            $folder = $this->viewIn === 'trash'
                ? MediaFolder::query()->withTrashed()->find($this->folderId)
                : MediaFolder::query()->find($this->folderId);

            if ($folder) {
                $parentBreadcrumbs = $folderRepository->getBreadcrumbs($folder->parent_id);
                if (!empty($parentBreadcrumbs)) {
                    $breadcrumbs = array_merge($breadcrumbs, $parentBreadcrumbs);
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
            $folder = MediaFolder::query()->find($item['id']);
            if ($folder) {
                $fileCount = MediaFile::query()->where('folder_id', $folder->id)->count();
                $folderCount = MediaFolder::query()->where('parent_id', $folder->id)->count();

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
        } else {
            $file = MediaFile::query()->find($item['id']);
            if ($file) {
                // Check if file exists on disk
                $fileExists = $this->checkFileExists($file->url);

                return [
                    'type' => 'file',
                    'id' => $file->id,
                    'name' => $file->name,
                    'url' => $file->url,
                    'mime_type' => $file->mime_type,
                    'size' => $this->formatBytes($file->size),
                    'size_raw' => $file->size,
                    'created_at' => $file->created_at?->format('M d, Y H:i'),
                    'updated_at' => $file->updated_at?->format('M d, Y H:i'),
                    'alt' => $file->alt ?? '',
                    'thumbnail' => $fileExists && $file->canGenerateThumbnails() ? $file->url : null,
                    'file_type' => $file->type,
                    'file_exists' => $fileExists,
                ];
            }
        }

        return null;
    }

    protected function getViewInLabel(): string
    {
        return match ($this->viewIn) {
            'trash' => trans('filament-media::media.trash'),
            'recent' => trans('filament-media::media.recent'),
            'favorites' => trans('filament-media::media.favorites'),
            default => trans('filament-media::media.all_media'),
        };
    }

    protected function getViewInIcon(): string
    {
        return match ($this->viewIn) {
            'trash' => BaseHelper::renderIcon('heroicon-m-trash'),
            'recent' => BaseHelper::renderIcon('heroicon-m-clock'),
            'favorites' => BaseHelper::renderIcon('heroicon-m-star'),
            default => BaseHelper::renderIcon('heroicon-o-photo'),
        };
    }

    protected function transformOrderBy(?string $orderBy): array
    {
        if (!$orderBy) {
            return ['created_at', 'desc'];
        }

        $result = explode('-', $orderBy);
        return count($result) === 2 ? $result : ['created_at', 'desc'];
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Check if a file exists on disk.
     */
    protected function checkFileExists(?string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        try {
            // For cloud storage, trust it exists (checking would be slow)
            if (FilamentMedia::isUsingCloud()) {
                return true;
            }

            return Storage::disk(FilamentMedia::getConfig('driver', 'public'))->exists($url);
        } catch (\Throwable $e) {
            return false;
        }
    }

    // Navigation actions
    public function navigateToFolder(int $folderId): void
    {
        $this->folderId = $folderId;
        $this->currentPage = 1;
        $this->selectedItems = [];
        unset($this->items);
        unset($this->breadcrumbs);
    }

    public function setViewIn(string $viewIn): void
    {
        $this->viewIn = $viewIn;
        $this->folderId = 0;
        $this->currentPage = 1;
        $this->selectedItems = [];
        unset($this->items);
        unset($this->breadcrumbs);
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
        $this->showDetailsPanel = !$this->showDetailsPanel;
        $this->saveUserPreferences();
    }

    public function updatedSearch(): void
    {
        $this->currentPage = 1;
        unset($this->items);
    }

    // Selection actions
    public function selectItem(array $item, bool $addToSelection = false): void
    {
        if ($addToSelection) {
            $exists = collect($this->selectedItems)->contains(fn($i) => $i['id'] === $item['id'] && $i['is_folder'] === $item['is_folder']);

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
            // Get all file IDs in current view for gallery navigation
            $fileIds = $this->items
                ->filter(fn($i) => !($i['is_folder'] ?? false))
                ->pluck('id')
                ->values()
                ->all();

            $this->dispatch('open-preview-modal', fileId: (int) $item['id'], fileIds: $fileIds);
        }
    }

    // Preview
    public function closePreview(): void
    {
        $this->previewItem = null;
    }

    // Keyboard navigation methods
    public function selectByIndex(int $index, bool $addToSelection = false): void
    {
        $item = $this->items->get($index);
        if (!$item) {
            return;
        }

        $itemData = [
            'id' => $item['id'],
            'is_folder' => $item['is_folder'] ?? false,
        ];

        $this->selectItem($itemData, $addToSelection);
    }

    public function toggleSelectionByIndex(int $index): void
    {
        $item = $this->items->get($index);
        if (!$item) {
            return;
        }

        $itemData = [
            'id' => $item['id'],
            'is_folder' => $item['is_folder'] ?? false,
        ];

        // Toggle: if already selected, remove; otherwise add
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
        if (!$item) {
            return;
        }

        $this->openItem([
            'id' => $item['id'],
            'is_folder' => $item['is_folder'] ?? false,
        ]);
    }

    // Drag & Drop: Move items to a folder
    public function moveItemsToFolder(array $items, int $destinationFolderId): void
    {
        if (empty($items)) {
            return;
        }

        foreach ($items as $item) {
            $isFolder = $item['is_folder'] ?? false;
            $id = $item['id'];

            // Prevent moving a folder into itself
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
                    $folder->parent_id = $destinationFolderId;
                    $folder->save();
                }
            } else {
                $file = MediaFile::find($id);
                if ($file) {
                    $file->folder_id = $destinationFolderId;
                    $file->save();
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

    // Refresh data
    public function refresh(): void
    {
        $this->currentPage = 1;
        $this->refreshKey = now()->timestamp; // Force computed property cache invalidation
        unset($this->items);
        unset($this->breadcrumbs);
    }

    // Load more for infinite scroll
    public function loadMore(): void
    {
        $this->currentPage++;
        // Items will be merged via the computed property
    }

    // Livewire event listeners for backward compatibility
    #[On('open-download-url-modal')]
    public function openDownloadUrlModal(): void
    {
        $this->mountAction('download_url');
    }

    #[On('open-rename-modal')]
    public function openRenameModal(array $items): void
    {
        if (count($items) !== 1) {
            return;
        }

        $this->mountAction('rename', ['items' => $items]);
    }

    #[On('update-folder-id')]
    public function updateFolderId($id): void
    {
        $this->folderId = $id;
    }

    #[On('open-trash-modal')]
    public function openTrashModal(array $items): void
    {
        $this->mountAction('trash', ['items' => $items]);
    }

    #[On('open-delete-modal')]
    public function openDeleteModal(array $items): void
    {
        $this->mountAction('delete', ['items' => $items]);
    }

    #[On('open-empty-trash-modal')]
    public function openEmptyTrashModal(): void
    {
        $this->mountAction('empty_trash');
    }

    #[On('open-create-folder-modal')]
    public function openCreateFolderModal(): void
    {
        $this->mountAction('create_folder');
    }

    #[On('open-favorite-modal')]
    public function openFavoriteModal(array $items): void
    {
        $this->mountAction('favorite', ['items' => $items]);
    }

    #[On('open-remove-favorite-modal')]
    public function openRemoveFavoriteModal(array $items): void
    {
        $this->mountAction('remove_favorite', ['items' => $items]);
    }

    #[On('open-properties-modal')]
    public function openPropertiesModal(array $items): void
    {
        $this->mountAction('properties', ['items' => $items]);
    }

    #[On('open-alt-text-modal')]
    public function openAltTextModal(array $items): void
    {
        $this->mountAction('alt_text', ['items' => $items]);
    }

    #[On('open-move-modal')]
    public function openMoveModal(array $items): void
    {
        $this->mountAction('move', ['items' => $items]);
    }

    #[On('media-folder-created')]
    public function onMediaFolderCreated(): void
    {
        $this->refresh();
    }

    #[On('media-files-uploaded')]
    public function onMediaFilesUploaded(): void
    {
        $this->refresh();
    }

    /**
     * Header actions for the page - Upload and Create Folder buttons
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_folder_header')
                ->label(trans('filament-media::media.create_folder'))
                ->icon('heroicon-m-folder-plus')
                ->color('gray')
                ->action(fn() => $this->mountAction('create_folder')),

            ActionGroup::make([
                Action::make('upload_from_local')
                    ->label(trans('filament-media::media.upload_from_local'))
                    ->icon('heroicon-m-arrow-up-tray')
                    ->action(fn() => $this->dispatch('open-upload-modal', folderId: $this->folderId)),

                Action::make('upload_from_url')
                    ->label(trans('filament-media::media.upload_from_url'))
                    ->icon('heroicon-m-globe-alt')
                    ->action(fn() => $this->mountAction('uploadFromUrl')),
            ])
                ->label(trans('filament-media::media.upload'))
                ->icon('heroicon-m-arrow-up-tray')
                ->color('primary')
                ->button(),
        ];
    }

    // ========================================
    // Action Methods - mountable via mountAction()
    // ========================================

    public function uploadAction(): Action
    {
        return Action::make('upload')
            ->label(trans('filament-media::media.upload'))
            ->icon('heroicon-o-arrow-up-tray')
            ->color('primary')
            ->action(function () {
                $this->dispatch('open-upload-modal');
            });
    }

    public function create_folderAction(): Action
    {
        return Action::make('create_folder')
            ->label(trans('filament-media::media.create_folder'))
            ->icon('heroicon-o-folder-plus')
            ->color('gray')
            ->modalHeading(trans('filament-media::media.create_folder'))
            ->modalSubmitActionLabel(trans('filament-media::media.create'))
            ->schema([
                TextInput::make('name')
                    ->label(trans('filament-media::media.folder_name'))
                    ->required()
                    ->maxLength(120)
                    ->autofocus(),
            ])
            ->action(function (array $data) {
                FilamentMedia::createFolder($data['name'], $this->folderId);

                $this->refresh();

                Notification::make()
                    ->title(trans('filament-media::media.folder_created'))
                    ->success()
                    ->send();
            });
    }

    public function trashAction(): Action
    {
        return Action::make('trash')
            ->label(trans('filament-media::media.move_to_trash'))
            ->requiresConfirmation()
            ->modalHeading(trans('filament-media::media.move_to_trash'))
            ->modalDescription(trans('filament-media::media.confirm_trash'))
            ->schema([
                Checkbox::make('skip_trash')
                    ->label(trans('filament-media::media.skip_trash'))
                    ->helperText(trans('filament-media::media.skip_trash_description')),
            ])
            ->action(function (array $data, array $arguments) {
                $items = $arguments['items'] ?? [];
                $skipTrash = $data['skip_trash'] ?? false;

                $fileRepository = app(MediaFileInterface::class);
                $folderRepository = app(MediaFolderInterface::class);

                foreach ($items as $item) {
                    $id = $item['id'];
                    $isFolder = $item['is_folder'] ?? false;

                    if (!$isFolder) {
                        try {
                            if ($skipTrash) {
                                $fileRepository->forceDelete(['id' => $id]);
                            } else {
                                $fileRepository->deleteBy(['id' => $id]);
                            }
                        } catch (\Throwable $exception) {
                            report($exception);
                        }
                    } else {
                        if ($skipTrash) {
                            $folderRepository->forceDelete(['id' => $id]);
                        } else {
                            $folderRepository->deleteFolder($id);
                        }
                    }
                }

                $this->selectedItems = [];
                $this->refresh();

                Notification::make()
                    ->title(trans('filament-media::media.trash_success'))
                    ->success()
                    ->send();
            });
    }

    public function renameAction(): Action
    {
        return Action::make('rename')
            ->label(trans('filament-media::media.rename'))
            ->fillForm(function (array $arguments): array {
                $items = $arguments['items'] ?? [];
                foreach ($items as $item) {
                    $isFolder = $item['is_folder'] ?? false;
                    $name = $isFolder ? MediaFolder::find($item['id'])->name ?? '' : MediaFile::find($item['id'])->name ?? '';
                    return ['name' => $name];
                }
                return [];
            })
            ->schema([
                TextInput::make('name')
                    ->label(trans('filament-media::media.folder_name'))
                    ->required(),
                Checkbox::make('rename_physical_file')
                    ->label(trans('filament-media::media.rename_physical_file'))
                    ->helperText(trans('filament-media::media.rename_physical_file_warning')),
            ])
            ->action(function (array $data, array $arguments) {
                $items = $arguments['items'] ?? [];
                $newName = $data['name'];
                $renameOnDisk = $data['rename_physical_file'] ?? false;

                foreach ($items as $item) {
                    $id = $item['id'];
                    $isFolder = $item['is_folder'] ?? false;

                    if (!$isFolder) {
                        $file = MediaFile::find($id);
                        if ($file) {
                            FilamentMedia::renameFile($file, $newName, $renameOnDisk);
                        }
                    } else {
                        $folder = MediaFolder::find($id);
                        if ($folder) {
                            FilamentMedia::renameFolder($folder, $newName, $renameOnDisk);
                        }
                    }
                }

                $this->refresh();

                Notification::make()
                    ->title(trans('filament-media::media.rename_success'))
                    ->success()
                    ->send();
            });
    }

    public function moveAction(): Action
    {
        return Action::make('move')
            ->label(trans('filament-media::media.move'))
            ->schema([
                Select::make('destination')
                    ->label(trans('filament-media::media.destination_folder'))
                    ->options(function () {
                        $folders = MediaFolder::query()
                            ->whereNull('deleted_at')
                            ->orderBy('name')
                            ->get();

                        $options = [0 => trans('filament-media::media.root_folder')];

                        foreach ($folders as $folder) {
                            $options[$folder->id] = $folder->name;
                        }

                        return $options;
                    })
                    ->required()
                    ->live()
                    ->suffixAction(
                        Action::make('createFolder')
                            ->icon('heroicon-m-plus')
                            ->tooltip(trans('filament-media::media.create_new_folder'))
                            ->form([
                                TextInput::make('folder_name')
                                    ->label(trans('filament-media::media.folder_name'))
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->action(function (array $data, Select $component) {
                                $folder = MediaFolder::create([
                                    'name' => $data['folder_name'],
                                    'parent_id' => null,
                                ]);

                                // Update the select to show new folder and select it
                                $component->state($folder->id);

                                Notification::make()
                                    ->title(trans('filament-media::media.folder_created'))
                                    ->success()
                                    ->send();
                            })
                    ),
            ])
            ->action(function (array $data, array $arguments) {
                $items = $arguments['items'] ?? [];
                $destination = $data['destination'];

                foreach ($items as $item) {
                    if (!($item['is_folder'] ?? false)) {
                        $file = MediaFile::find($item['id']);
                        if ($file) {
                            $file->folder_id = $destination;
                            $file->save();
                        }
                    } else {
                        $folder = MediaFolder::find($item['id']);
                        if ($folder && $folder->id !== $destination) {
                            $folder->parent_id = $destination;
                            $folder->save();
                        }
                    }
                }

                $this->selectedItems = [];
                $this->refresh();

                Notification::make()
                    ->title(trans('filament-media::media.move_success'))
                    ->success()
                    ->send();
            });
    }

    public function deleteAction(): Action
    {
        return Action::make('delete')
            ->label(trans('filament-media::media.confirm_delete'))
            ->requiresConfirmation()
            ->modalHeading(trans('filament-media::media.confirm_delete'))
            ->modalDescription(trans('filament-media::media.confirm_delete_description'))
            ->action(function (array $arguments) {
                $items = $arguments['items'] ?? [];

                $fileRepository = app(MediaFileInterface::class);
                $folderRepository = app(MediaFolderInterface::class);

                foreach ($items as $item) {
                    $id = $item['id'];
                    $isFolder = $item['is_folder'] ?? false;

                    if (!$isFolder) {
                        try {
                            $fileRepository->forceDelete(['id' => $id]);
                        } catch (\Throwable $exception) {
                            report($exception);
                        }
                    } else {
                        $folderRepository->deleteFolder($id, true);
                    }
                }

                $this->selectedItems = [];
                $this->refresh();

                Notification::make()
                    ->title(trans('filament-media::media.delete_success'))
                    ->success()
                    ->send();
            });
    }

    public function empty_trashAction(): Action
    {
        return Action::make('empty_trash')
            ->label(trans('filament-media::media.empty_trash_title'))
            ->requiresConfirmation()
            ->modalHeading(trans('filament-media::media.empty_trash_title'))
            ->modalDescription(trans('filament-media::media.empty_trash_description'))
            ->action(function () {
                $fileRepository = app(MediaFileInterface::class);
                $folderRepository = app(MediaFolderInterface::class);

                $fileRepository->emptyTrash();
                $folderRepository->emptyTrash();

                $this->refresh();

                Notification::make()
                    ->title(trans('filament-media::media.empty_trash_success'))
                    ->success()
                    ->send();
            });
    }

    public function favoriteAction(): Action
    {
        return Action::make('favorite')
            ->label(trans('filament-media::media.javascript.actions_list.user.favorite'))
            ->action(function (array $arguments) {
                $items = $arguments['items'] ?? [];

                $meta = MediaSetting::query()->firstOrCreate([
                    'key' => 'favorites',
                    'user_id' => Auth::guard()->id(),
                ]);

                if (!empty($meta->value)) {
                    $meta->value = array_merge($meta->value, $items);
                } else {
                    $meta->value = $items;
                }

                $meta->save();

                $this->refresh();

                Notification::make()
                    ->title(trans('filament-media::media.favorite_success'))
                    ->success()
                    ->send();
            });
    }

    public function remove_favoriteAction(): Action
    {
        return Action::make('remove_favorite')
            ->label(trans('filament-media::media.javascript.actions_list.user.remove_favorite'))
            ->action(function (array $arguments) {
                $items = $arguments['items'] ?? [];

                $meta = MediaSetting::query()->firstOrCreate([
                    'key' => 'favorites',
                    'user_id' => Auth::guard()->id(),
                ]);

                if (!empty($meta)) {
                    $value = $meta->value;
                    if (!empty($value)) {
                        foreach ($value as $key => $item) {
                            foreach ($items as $selectedItem) {
                                if (($item['is_folder'] ?? false) == ($selectedItem['is_folder'] ?? false) && $item['id'] == $selectedItem['id']) {
                                    unset($value[$key]);
                                }
                            }
                        }

                        $meta->value = $value;
                        $meta->save();
                    }
                }

                $this->refresh();

                Notification::make()
                    ->title(trans('filament-media::media.remove_favorite_success'))
                    ->success()
                    ->send();
            });
    }

    public function propertiesAction(): Action
    {
        return Action::make('properties')
            ->label(trans('filament-media::media.properties.name'))
            ->fillForm(function (array $arguments): array {
                $items = $arguments['items'] ?? [];
                if (count($items) === 1) {
                    $folder = MediaFolder::find($items[0]['id']);
                    if ($folder) {
                        return ['color' => $folder->color];
                    }
                }
                return [];
            })
            ->schema([
                \Filament\Forms\Components\ColorPicker::make('color')
                    ->label(trans('filament-media::media.properties.color_label'))
                    ->required(),
            ])
            ->action(function (array $data, array $arguments) {
                $items = $arguments['items'] ?? [];
                foreach ($items as $item) {
                    if ($item['is_folder'] ?? false) {
                        MediaFolder::where('id', $item['id'])->update(['color' => $data['color']]);
                    }
                }
                $this->refresh();
                Notification::make()->title(trans('filament-media::media.update_properties_success'))->success()->send();
            });
    }

    public function alt_textAction(): Action
    {
        return Action::make('alt_text')
            ->label(trans('filament-media::media.alt_text'))
            ->fillForm(function (array $arguments): array {
                $items = $arguments['items'] ?? [];
                if (count($items) === 1) {
                    $file = MediaFile::find($items[0]['id']);
                    if ($file) {
                        return ['alt' => $file->alt];
                    }
                }
                return [];
            })
            ->schema([
                TextInput::make('alt')
                    ->label(trans('filament-media::media.alt_text'))
                    ->maxLength(255),
            ])
            ->action(function (array $data, array $arguments) {
                $items = $arguments['items'] ?? [];
                foreach ($items as $item) {
                    if (!($item['is_folder'] ?? false)) {
                        MediaFile::where('id', $item['id'])->update(['alt' => $data['alt']]);
                    }
                }
                $this->refresh();
                Notification::make()->title(trans('filament-media::media.update_alt_text_success'))->success()->send();
            });
    }

    public function uploadFromUrlAction(): Action
    {
        return Action::make('uploadFromUrl')
            ->label(trans('filament-media::media.download_link'))
            ->icon('heroicon-o-globe-alt')
            ->modalWidth('md')
            ->modalHeading(trans('filament-media::media.upload_from_url'))
            ->modalSubmitActionLabel(trans('filament-media::media.upload'))
            ->schema([
                Textarea::make('urls')
                    ->label(trans('filament-media::media.url'))
                    ->placeholder('https://example.com/image1.jpg' . "\n" . 'https://example.com/image2.png')
                    ->helperText(trans('filament-media::media.download_explain'))
                    ->required()
                    ->rows(5),
            ])
            ->action(function (array $data) {
                $urls = explode("\n", $data['urls']);
                $successCount = 0;

                foreach ($urls as $url) {
                    $url = trim($url);
                    if ($url && filter_var($url, FILTER_VALIDATE_URL)) {
                        try {
                            FilamentMedia::uploadFromUrl($url, $this->folderId);
                            $successCount++;
                        } catch (\Throwable $e) {
                            logger()->error('Failed to upload from URL', ['url' => $url, 'error' => $e->getMessage()]);
                        }
                    }
                }

                $this->refresh();

                if ($successCount > 0) {
                    Notification::make()
                        ->title(trans('filament-media::media.add_success'))
                        ->body(trans('filament-media::media.files_uploaded', ['count' => $successCount]))
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title(trans('filament-media::media.upload_error'))
                        ->danger()
                        ->send();
                }
            });
    }

    public function restoreAction(): Action
    {
        return Action::make('restore')
            ->label(trans('filament-media::media.restore'))
            ->action(function (array $arguments) {
                $items = $arguments['items'] ?? [];

                $fileRepository = app(MediaFileInterface::class);
                $folderRepository = app(MediaFolderInterface::class);

                foreach ($items as $item) {
                    if (!($item['is_folder'] ?? false)) {
                        $fileRepository->restoreBy(['id' => $item['id']]);
                    } else {
                        $folderRepository->restoreFolder($item['id']);
                    }
                }

                $this->selectedItems = [];
                $this->refresh();

                Notification::make()
                    ->title(trans('filament-media::media.restore_success'))
                    ->success()
                    ->send();
            });
    }

    public function getViewData(): array
    {
        return [
            'sorts' => $this->sorts,
            'mimeTypes' => FilamentMedia::getConfig('mime_types', []),
            'sidebarDisplay' => FilamentMedia::getConfig('sidebar_display', 'horizontal'),
        ];
    }
}
