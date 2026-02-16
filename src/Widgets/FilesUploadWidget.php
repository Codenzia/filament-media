<?php

namespace Codenzia\FilamentMedia\Widgets;

use Codenzia\FilamentMedia\FilamentMedia;
use Codenzia\FilamentMedia\Forms\MediaFileUpload;
use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Services\StorageDriverService;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;

/**
 * Generic file upload widget that creates MediaFile records
 * linked to any Eloquent model via morphable relationship.
 */
class FilesUploadWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    public Model $record;

    public ?string $directory = null;

    public string $visibility = 'private';

    public string $submitLabel = '';

    public string $submitColor = 'primary';

    public string $submitAlignment = 'start';

    public array $data = [];

    protected string $view = 'filament-media::widgets.files-upload-widget';

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                MediaFileUpload::make($this->directory)
                    ->multiple()
                    ->reorderable()
                    ->appendFiles()
                    ->hiddenLabel()
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    #[Computed]
    public function hasUploadedFiles(): bool
    {
        return ! empty(array_filter($this->data['url'] ?? []));
    }

    public function create(): void
    {
        $data = $this->form->getState();
        $storageService = app(StorageDriverService::class);
        $mediaDisk = $storageService->getMediaDisk();
        $mediaDriverName = $storageService->getMediaDriver();

        // For private files on local storage, move from media disk to private disk
        // (matches UploadsManager behaviour)
        $needsMove = $this->visibility === 'private' && ! $storageService->isUsingCloud();
        $privateDiskName = FilamentMedia::getConfig('private_files.private_disk') ?? 'local';
        $privateDisk = Storage::disk($privateDiskName);

        foreach ($data['url'] as $filePath) {
            $mimeType = $mediaDisk->mimeType($filePath) ?: 'application/octet-stream';
            $size = $mediaDisk->size($filePath) ?: 0;

            if ($needsMove && $mediaDriverName !== $privateDiskName) {
                $privateDisk->put($filePath, $mediaDisk->get($filePath));
                $mediaDisk->delete($filePath);
            }

            MediaFile::create([
                'name' => basename($filePath),
                'mime_type' => $mimeType,
                'size' => $size,
                'url' => $filePath,
                'visibility' => $this->visibility,
                'fileable_type' => $this->record->getMorphClass(),
                'fileable_id' => $this->record->getKey(),
                'user_id' => Auth::id(),
                'created_by_user_id' => Auth::id(),
                'updated_by_user_id' => Auth::id(),
            ]);
        }

        Notification::make()
            ->title(__('Files uploaded successfully'))
            ->success()
            ->send();

        $this->record->refresh();
        $this->form->fill();
        $this->dispatch('files-uploaded');
    }
}
