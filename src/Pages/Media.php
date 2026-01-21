<?php

namespace Codenzia\FilamentMedia\Pages;

use Filament\Pages\Page;
use Codenzia\FilamentMedia\Facades\FilamentMedia;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use Codenzia\FilamentMedia\Repositories\Interfaces\MediaFileInterface;
use Codenzia\FilamentMedia\Repositories\Interfaces\MediaFolderInterface;
use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaFolder;

class Media extends Page
{

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected string $view = 'filament-media::pages.media';
    protected array $sorts = [];

    public $folderId = 0;

    public function mount(): void
    {
        $this->sorts = FilamentMedia::getSorts();
    }

    #[On('open-rename-modal')]
    public function openRenameModal(array $items)
    {
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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_folder')
                ->label(trans('core/media::media.create_folder'))
                ->icon('heroicon-o-folder-plus')
                ->extraAttributes(['class' => 'hidden'])
                ->form([
                    TextInput::make('name')
                        ->label(trans('core/media::media.folder_name'))
                        ->required(),
                ])
                ->action(function (array $data) {
                    FilamentMedia::createFolder($data['name'], $this->folderId);

                    $this->dispatch('media-folder-created');

                    Notification::make()
                        ->title(trans('core/media::media.folder_created'))
                        ->success()
                        ->send();
                }),

            Action::make('trash')
                ->label(trans('core/media::media.move_to_trash'))
                ->extraAttributes(['class' => 'hidden'])
                ->requiresConfirmation()
                ->modalHeading(trans('core/media::media.move_to_trash'))
                ->modalDescription(trans('core/media::media.confirm_trash'))
                ->form([
                    Checkbox::make('skip_trash')
                        ->label(trans('core/media::media.skip_trash'))
                        ->helperText(trans('core/media::media.skip_trash_description')),
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
                        } else {
                            if ($skipTrash) {
                                $folderRepository->forceDelete(['id' => $id]);
                            } else {
                                $folderRepository->deleteFolder($id);
                            }
                        }
                    }

                    $this->dispatch('media-folder-created');

                    Notification::make()
                        ->title(trans('core/media::media.trash_success'))
                        ->success()
                        ->send();
                }),

            Action::make('rename')
                ->label(trans('core/media::media.rename'))
                ->extraAttributes(['class' => 'hidden'])
                ->mountUsing(function ($form, array $arguments) {
                    $items = $arguments['items'] ?? [];
                    if (count($items) === 1) {
                        $form->fill([
                            'name' => $items[0]['name'] ?? '',
                        ]);
                    }
                })
                ->form([
                    TextInput::make('name')
                        ->label(trans('core/media::media.folder_name'))
                        ->required(),
                ])
                ->action(function (array $data, array $arguments) {
                    $items = $arguments['items'] ?? [];
                    $newName = $data['name'];

                    foreach ($items as $item) {
                        $id = $item['id'];
                        $isFolder = $item['is_folder'] ?? false;

                        if (! $isFolder) {
                            $file = MediaFile::find($id);
                            if ($file) {
                                FilamentMedia::renameFile($file, $newName , false);
                            }
                        } else {
                            $folder = MediaFolder::find($id);
                            if ($folder) {
                                FilamentMedia::renameFolder($folder, $newName);
                            }
                        }
                    }

                    $this->dispatch('media-folder-created');

                    Notification::make()
                        ->title(trans('core/media::media.rename_success'))
                        ->success()
                        ->send();
                }),

            Action::make('delete')
                ->label(trans('core/media::media.confirm_delete'))
                ->extraAttributes(['class' => 'hidden'])
                ->requiresConfirmation()
                ->modalHeading(trans('core/media::media.confirm_delete'))
                ->modalDescription(trans('core/media::media.confirm_delete_description'))
                ->action(function (array $arguments) {
                    $items = $arguments['items'] ?? [];

                    $fileRepository = app(MediaFileInterface::class);
                    $folderRepository = app(MediaFolderInterface::class);

                    foreach ($items as $item) {
                        $id = $item['id'];
                        $isFolder = $item['is_folder'] ?? false;
                        if (! $isFolder) {
                            try {
                                $fileRepository->forceDelete(['id' => $id]);
                            } catch (\Throwable $exception) {
                                report($exception);
                            }
                        } else {
                            $folderRepository->deleteFolder($id, true);
                        }
                    }

                    $this->dispatch('media-folder-created');

                    Notification::make()
                        ->title(trans('core/media::media.delete_success'))
                        ->success()
                        ->send();
                }),

            Action::make('empty_trash')
                ->label(trans('core/media::media.empty_trash_title'))
                ->extraAttributes(['class' => 'hidden'])
                ->requiresConfirmation()
                ->modalHeading(trans('core/media::media.empty_trash_title'))
                ->modalDescription(trans('core/media::media.empty_trash_description'))
                ->action(function () {
                    $fileRepository = app(MediaFileInterface::class);
                    $folderRepository = app(MediaFolderInterface::class);

                    $fileRepository->emptyTrash();
                    $folderRepository->emptyTrash();

                    $this->dispatch('media-folder-created');

                    Notification::make()
                        ->title(trans('core/media::media.empty_trash_success'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
