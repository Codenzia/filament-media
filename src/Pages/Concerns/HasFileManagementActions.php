<?php

namespace Codenzia\FilamentMedia\Pages\Concerns;

use Codenzia\FilamentMedia\Facades\FilamentMedia;
use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaFolder;
use Codenzia\FilamentMedia\Services\FileOperationService;
use Codenzia\FilamentMedia\Services\UploadService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

/**
 * Provides core file management Filament actions for the media manager.
 *
 * Includes actions for uploading files (local and URL), creating folders,
 * renaming, moving, copying, trashing, permanent deletion, emptying trash, and restoring items.
 */
trait HasFileManagementActions
{
    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_folder_header')
                ->label(trans('filament-media::media.create_folder'))
                ->icon('heroicon-m-folder-plus')
                ->color('gray')
                ->alpineClickHandler('$wire.mountAction(\'create_folder\')'),

            ActionGroup::make([
                Action::make('upload_from_local')
                    ->label(trans('filament-media::media.upload_from_local'))
                    ->icon('heroicon-m-arrow-up-tray')
                    ->alpineClickHandler("\$dispatch('open-upload-modal', { folderId: {$this->folderId} })"),

                Action::make('upload_from_url')
                    ->label(trans('filament-media::media.upload_from_url'))
                    ->icon('heroicon-m-globe-alt')
                    ->alpineClickHandler('$wire.mountAction(\'uploadFromUrl\')'),
            ])
                ->label(trans('filament-media::media.upload'))
                ->icon('heroicon-m-arrow-up-tray')
                ->color('primary')
                ->button(),
        ];
    }

    public function uploadAction(): Action
    {
        return Action::make('upload')
            ->label(trans('filament-media::media.upload'))
            ->icon('heroicon-o-arrow-up-tray')
            ->color('primary')
            ->action(fn() => $this->dispatch('open-upload-modal'));
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
                    ->autofocus()
                    ->rules([
                        fn () => function (string $attribute, $value, \Closure $fail) {
                            $exists = MediaFolder::withoutGlobalScopes()
                                ->where('name', $value)
                                ->where('parent_id', $this->folderId ?? 0)
                                ->whereNull('deleted_at')
                                ->exists();

                            if ($exists) {
                                $fail(trans('filament-media::media.folder_name_exists'));
                            }
                        },
                    ]),
            ])
            ->action(function (array $data) {
                FilamentMedia::createFolder($data['name'], $this->folderId);

                $this->refresh();

                $this->notifySuccess('folder_created');
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
                $uploadService = app(UploadService::class);
                $successCount = 0;

                foreach ($urls as $url) {
                    $url = trim($url);
                    if ($url && filter_var($url, FILTER_VALIDATE_URL)) {
                        try {
                            $uploadService->uploadFromUrl($url, $this->folderId);
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
                    $this->notifyError('upload_error');
                }
            });
    }

    public function move_to_folderAction(): Action
    {
        return Action::make('move_to_folder')
            ->label(trans('filament-media::media.move'))
            ->requiresConfirmation()
            ->modalHeading(trans('filament-media::media.move_to'))
            ->modalDescription(fn (array $arguments) => trans(
                'filament-media::media.confirm_move_to_folder',
                ['folder' => $arguments['folderName'] ?? '']
            ))
            ->action(function (array $arguments) {
                $items = $arguments['items'] ?? [];
                $destinationFolderId = $arguments['destinationFolderId'] ?? null;

                if (empty($items) || $destinationFolderId === null) {
                    return;
                }

                $this->moveItemsToFolder($items, (int) $destinationFolderId);
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
                $fileOps = app(FileOperationService::class);

                foreach ($items as $item) {
                    $id = $item['id'];
                    $isFolder = $item['is_folder'] ?? false;

                    try {
                        if (! $isFolder) {
                            $file = MediaFile::withTrashed()->find($id);
                            if ($file) {
                                if ($skipTrash) {
                                    $fileOps->deleteFile($file);
                                    $file->forceDelete();
                                } else {
                                    $file->delete();
                                }
                            }
                        } else {
                            $folder = MediaFolder::withTrashed()->find($id);
                            if ($folder) {
                                $skipTrash ? $folder->forceDelete() : $folder->delete();
                            }
                        }
                    } catch (\Throwable $exception) {
                        report($exception);
                    }
                }

                $this->selectedItems = [];
                $this->refresh();

                $this->notifySuccess('trash_success');
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
                    $name = $isFolder
                        ? MediaFolder::find($item['id'])?->name ?? ''
                        : MediaFile::find($item['id'])?->name ?? '';

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
                $fileOps = app(FileOperationService::class);

                foreach ($items as $item) {
                    $id = $item['id'];
                    $isFolder = $item['is_folder'] ?? false;

                    if (! $isFolder) {
                        $file = MediaFile::find($id);
                        if ($file) {
                            $fileOps->renameFile($file, $newName, $renameOnDisk);
                        }
                    } else {
                        $folder = MediaFolder::find($id);
                        if ($folder) {
                            $fileOps->renameFolder($folder, $newName, $renameOnDisk);
                        }
                    }
                }

                $this->refresh();

                $this->notifySuccess('rename_success');
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
                            ->schema([
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
                $fileOps = app(FileOperationService::class);

                foreach ($items as $item) {
                    if (! ($item['is_folder'] ?? false)) {
                        $file = MediaFile::find($item['id']);
                        if ($file) {
                            $fileOps->moveFile($file, $destination);
                        }
                    } else {
                        $folder = MediaFolder::find($item['id']);
                        if ($folder && $folder->id !== $destination) {
                            $folder->update(['parent_id' => $destination]);
                        }
                    }
                }

                $this->selectedItems = [];
                $this->refresh();

                $this->notifySuccess('move_success');
            });
    }

    public function copyAction(): Action
    {
        return Action::make('copy')
            ->label(trans('filament-media::media.copy'))
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
                    ->default($this->folderId),
            ])
            ->action(function (array $data, array $arguments) {
                $items = $arguments['items'] ?? [];
                $destination = $data['destination'] ?? $this->folderId;
                $fileOps = app(FileOperationService::class);
                $count = 0;

                foreach ($items as $item) {
                    if (! ($item['is_folder'] ?? false)) {
                        $file = MediaFile::find($item['id']);
                        if ($file) {
                            $fileOps->copyFile($file, $destination ?: null);
                            $count++;
                        }
                    }
                }

                $this->refresh();

                $this->notifySuccess('copy_success', ['count' => $count]);
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
                $fileOps = app(FileOperationService::class);

                foreach ($items as $item) {
                    $id = $item['id'];
                    $isFolder = $item['is_folder'] ?? false;

                    try {
                        if (! $isFolder) {
                            $file = MediaFile::withTrashed()->find($id);
                            if ($file) {
                                $fileOps->deleteFile($file);
                                $file->forceDelete();
                            }
                        } else {
                            $folder = MediaFolder::withTrashed()->find($id);
                            $folder?->forceDelete();
                        }
                    } catch (\Throwable $exception) {
                        report($exception);
                    }
                }

                $this->selectedItems = [];
                $this->refresh();

                $this->notifySuccess('delete_success');
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
                $fileOps = app(FileOperationService::class);

                MediaFile::onlyTrashed()->each(function (MediaFile $file) use ($fileOps) {
                    $fileOps->deleteFile($file);
                    $file->forceDelete();
                });

                MediaFolder::onlyTrashed()->each(fn(MediaFolder $folder) => $folder->forceDelete());

                $this->refresh();

                $this->notifySuccess('empty_trash_success');
            });
    }

    public function restoreAction(): Action
    {
        return Action::make('restore')
            ->label(trans('filament-media::media.restore'))
            ->action(function (array $arguments) {
                $items = $arguments['items'] ?? [];

                foreach ($items as $item) {
                    if (! ($item['is_folder'] ?? false)) {
                        MediaFile::withTrashed()->where('id', $item['id'])->restore();
                    } else {
                        $folder = MediaFolder::withTrashed()->find($item['id']);
                        $folder?->restore();
                    }
                }

                $this->selectedItems = [];
                $this->refresh();

                $this->notifySuccess('restore_success');
            });
    }
}
