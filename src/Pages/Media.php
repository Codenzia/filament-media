<?php

namespace Codenzia\FilamentMedia\Pages;

use Filament\Pages\Page;
use Codenzia\FilamentMedia\Facades\FilamentMedia;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;

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

    #[On('update-folder-id')]
    public function updateFolderId($id)
    {
        $this->folderId = $id;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_folder')
                ->label(trans('core/media::media.create_folder'))
                ->icon('heroicon-o-folder-plus')
                ->extraAttributes([
                    'class' => 'hidden',
                    'x-data' => '{}',
                    'x-on:open-create-folder.window' => "\$dispatch('open-modal', { id: 'create_folder' })",
                ])
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
        ];
    }

}
