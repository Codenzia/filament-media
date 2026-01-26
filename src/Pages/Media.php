<?php

namespace Codenzia\FilamentMedia\Pages;

use Filament\Pages\Page;
use Codenzia\FilamentMedia\Facades\FilamentMedia;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use Codenzia\FilamentMedia\Repositories\Interfaces\MediaFileInterface;
use Codenzia\FilamentMedia\Repositories\Interfaces\MediaFolderInterface;
use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaFolder;
use Codenzia\FilamentMedia\Models\MediaSetting;
use Illuminate\Support\Facades\Auth;

class Media extends Page
{
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
    protected array $sorts = [];

    public $folderId = 0;

    public function mount(): void
    {
        $this->sorts = FilamentMedia::getSorts();
    }

    #[On('open-download-url-modal')]
    public function openDownloadUrlModal()
    {
        $this->mountAction('download_url');
    }

    #[On('open-rename-modal')]
    public function openRenameModal(array $items)
    {
        if (count($items) !== 1) {
            return;
        }

        $this->mountAction('rename', ['items' => $items]);
    }

    #[On('update-folder-id')]
    public function updateFolderId($id)
    {
        $this->folderId = $id;
    }

    #[On('open-trash-modal')]
    public function openTrashModal(array $items)
    {
        $this->mountAction('trash', ['items' => $items]);
    }

    #[On('open-delete-modal')]
    public function openDeleteModal(array $items)
    {
        $this->mountAction('delete', ['items' => $items]);
    }

    #[On('open-empty-trash-modal')]
    public function openEmptyTrashModal()
    {
        $this->mountAction('empty_trash');
    }

    #[On('open-create-folder-modal')]
    public function openCreateFolderModal()
    {
        $this->mountAction('create_folder');
    }

    #[On('open-favorite-modal')]
    public function openFavoriteModal(array $items)
    {
        $this->mountAction('favorite', ['items' => $items]);
    }

    #[On('open-remove-favorite-modal')]
    public function openRemoveFavoriteModal(array $items)
    {
        $this->mountAction('remove_favorite', ['items' => $items]);
    }

    #[On('open-properties-modal')]
    public function openPropertiesModal(array $items)
    {
        $this->mountAction('properties', ['items' => $items]);
    }

    #[On('open-alt-text-modal')]
    public function openAltTextModal(array $items)
    {
        $this->mountAction('alt_text', ['items' => $items]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_folder')
                ->label(trans('filament-media::media.create_folder'))
                ->icon('heroicon-o-folder-plus')
                ->extraAttributes(['class' => 'hidden'])
                ->form([
                    TextInput::make('name')
                        ->label(trans('filament-media::media.folder_name'))
                        ->required(),
                ])
                ->action(function (array $data) {
                    FilamentMedia::createFolder($data['name'], $this->folderId);

                    $this->dispatch('media-folder-created');

                    Notification::make()
                        ->title(trans('filament-media::media.folder_created'))
                        ->success()
                        ->send();
                }),

            Action::make('trash')
                ->label(trans('filament-media::media.move_to_trash'))
                ->extraAttributes(['class' => 'hidden'])
                ->requiresConfirmation()
                ->modalHeading(trans('filament-media::media.move_to_trash'))
                ->modalDescription(trans('filament-media::media.confirm_trash'))
                ->form([
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
                        if (! $isFolder) {
                            try {
                                if ($skipTrash) {
                                    $fileRepository->forceDelete(['id' => $id]);
                                } else {
                                    $fileRepository->deleteBy(['id' => $id]);
                                }
                            } catch (\Throwable $exception) {
                                report($exception);
                            }
                        }
                        else {
                            if ($skipTrash) {
                                $folderRepository->forceDelete(['id' => $id]);
                            } else {
                                $folderRepository->deleteFolder($id);
                            }
                        }
                    }

                    $this->dispatch('media-folder-created');

                    Notification::make()
                        ->title(trans('filament-media::media.trash_success'))
                        ->success()
                        ->send();
                }),

            Action::make('rename')
                ->label(trans('filament-media::media.rename'))
                ->extraAttributes(['class' => 'hidden'])
                ->mountUsing(function ($form, array $arguments) {
                    $items = $arguments['items'] ?? [];
                    foreach ($items as $item) {
                        $name = $item['is_folder'] ? MediaFolder::find($item['id'])->name ?? '' : MediaFile::find($item['id'])->name ?? '';
                        $form->fill(['name' => $name]);
                    }
                })
                ->form([
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
                        if (! $isFolder) {
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

                    $this->dispatch('media-folder-created');

                    Notification::make()
                        ->title(trans('filament-media::media.rename_success'))
                        ->success()
                        ->send();
                }),

            Action::make('delete')
                ->label(trans('filament-media::media.confirm_delete'))
                ->extraAttributes(['class' => 'hidden'])
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
                        if (! $isFolder) {
                            try {
                                if ($fileRepository instanceof \Codenzia\FilamentMedia\Repositories\Interfaces\MediaFileInterface) {
                                    $fileRepository->forceDelete(['id' => $id]);
                                }
                            } catch (\Throwable $exception) {
                                report($exception);
                            }
                        } else {
                            $folderRepository->deleteFolder($id, true);
                        }
                    }

                    $this->dispatch('media-folder-created');

                    Notification::make()
                        ->title(trans('filament-media::media.delete_success'))
                        ->success()
                        ->send();
                }),

            Action::make('empty_trash')
                ->label(trans('filament-media::media.empty_trash_title'))
                ->extraAttributes(['class' => 'hidden'])
                ->requiresConfirmation()
                ->modalHeading(trans('filament-media::media.empty_trash_title'))
                ->modalDescription(trans('filament-media::media.empty_trash_description'))
                ->action(function () {
                    $fileRepository = app(MediaFileInterface::class);
                    $folderRepository = app(MediaFolderInterface::class);

                    $fileRepository->emptyTrash();
                    $folderRepository->emptyTrash();

                    $this->dispatch('media-folder-created');

                    Notification::make()
                        ->title(trans('filament-media::media.empty_trash_success'))
                        ->success()
                        ->send();
                }),

            Action::make('favorite')
                ->label(trans('filament-media::media.javascript.actions_list.user.favorite'))
                ->extraAttributes(['class' => 'hidden'])
                ->action(function (array $arguments) {
                    $items = $arguments['items'] ?? [];

                    $meta = MediaSetting::query()->firstOrCreate([
                        'key' => 'favorites',
                        'user_id' => Auth::guard()->id(),
                    ]);

                    if (! empty($meta->value)) {
                        $meta->value = array_merge($meta->value, $items);
                    } else {
                        $meta->value = $items;
                    }

                    $meta->save();

                    $this->dispatch('media-folder-created');

                    Notification::make()
                        ->title(trans('filament-media::media.favorite_success'))
                        ->success()
                        ->send();
                }),

            Action::make('remove_favorite')
                ->label(trans('filament-media::media.javascript.actions_list.user.remove_favorite'))
                ->extraAttributes(['class' => 'hidden'])
                ->action(function (array $arguments) {
                    $items = $arguments['items'] ?? [];

                    $meta = MediaSetting::query()->firstOrCreate([
                        'key' => 'favorites',
                        'user_id' => Auth::guard()->id(),
                    ]);

                    if (! empty($meta)) {
                        $value = $meta->value;
                        if (! empty($value)) {
                            foreach ($value as $key => $item) {
                                foreach ($items as $selectedItem) {
                                    if ($item['is_folder'] == $selectedItem['is_folder'] && $item['id'] == $selectedItem['id']) {
                                        unset($value[$key]);
                                    }
                                }
                            }

                            $meta->value = $value;
                            $meta->save();
                        }
                    }

                    $this->dispatch('media-folder-created');

                    Notification::make()
                        ->title(trans('filament-media::media.remove_favorite_success'))
                        ->success()
                        ->send();
                }),

            Action::make('properties')
                ->label(trans('filament-media::media.properties.name'))
                ->extraAttributes(['class' => 'hidden'])
                ->mountUsing(function ($form, array $arguments) {
                    $items = $arguments['items'] ?? [];
                    if (count($items) === 1) {
                        $folder = MediaFolder::find($items[0]['id']);
                        if ($folder) {
                            $form->fill([
                                'color' => $folder->color,
                            ]);
                        }
                    }
                })
                ->form([
                    \Filament\Forms\Components\ColorPicker::make('color')
                        ->label(trans('filament-media::media.properties.color_label'))
                        ->required(),
                ])
                ->action(function (array $data, array $arguments) {
                    $items = $arguments['items'] ?? [];
                    foreach ($items as $item) {
                        if ($item['is_folder']) {
                            MediaFolder::where('id', $item['id'])->update(['color' => $data['color']]);
                        }
                    }
                    $this->dispatch('media-folder-created');
                    Notification::make()->title(trans('filament-media::media.update_properties_success'))->success()->send();
                }),

            Action::make('alt_text')
                ->label(trans('filament-media::media.alt_text'))
                ->extraAttributes(['class' => 'hidden'])
                ->mountUsing(function ($form, array $arguments) {
                    $items = $arguments['items'] ?? [];
                    if (count($items) === 1) {
                        $file = MediaFile::find($items[0]['id']);
                        if ($file) {
                            $form->fill([
                                'alt' => $file->alt,
                            ]);
                        }
                    }
                })
                ->form([
                    TextInput::make('alt')
                        ->label(trans('filament-media::media.alt_text'))
                        ->maxLength(255),
                ])
                ->action(function (array $data, array $arguments) {
                    $items = $arguments['items'] ?? [];
                    foreach ($items as $item) {
                        if (!$item['is_folder']) {
                            MediaFile::where('id', $item['id'])->update(['alt' => $data['alt']]);
                        }
                    }
                    $this->dispatch('media-folder-created');
                    Notification::make()->title(trans('filament-media::media.update_alt_text_success'))->success()->send();
                }),

            Action::make('download_url')
                ->label(trans('filament-media::media.download_link'))
                ->icon('heroicon-o-arrow-down-tray')
                ->extraAttributes(['class' => 'hidden'])
                ->form([
                    Textarea::make('urls')
                        ->label(trans('filament-media::media.url'))
                        ->helperText(trans('filament-media::media.download_explain'))
                        ->required()
                        ->rows(5),
                ])
                ->action(function (array $data) {
                    $urls = explode("\n", $data['urls']);
                    foreach ($urls as $url) {
                        $url = trim($url);
                        if ($url) {
                            FilamentMedia::uploadFromUrl($url, $this->folderId);
                        }
                    }

                    $this->dispatch('media-folder-created');

                    Notification::make()
                        ->title(trans('filament-media::media.add_success'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
