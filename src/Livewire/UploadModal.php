<?php

namespace Codenzia\FilamentMedia\Livewire;

use Codenzia\FilamentMedia\Facades\FilamentMedia;
use Codenzia\FilamentMedia\Helpers\BaseHelper;
use Codenzia\FilamentMedia\Models\MediaFolder;
use Codenzia\FilamentMedia\Services\UploadService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Livewire component that manages the file upload modal with queue tracking,
 * progress updates, and batch upload completion notifications.
 */
class UploadModal extends Component
{
    public bool $isOpen = false;
    public int $folderId = 0;

    /** Per-field allowed extensions override (comma-separated), or null for global default. */
    public ?string $allowedExtensions = null;

    /** HMAC signature to verify allowedExtensions wasn't tampered client-side. */
    public ?string $allowedExtensionsSig = null;

    /** @var array<string, array{name: string, size: int, status: string, progress: int, error: ?string}> */
    public array $uploadQueue = [];

    public int $completedCount = 0;
    public int $failedCount = 0;

    protected int $maxFilesPerUpload = 50;

    public function mount(
        ?string $allowedExtensions = null,
        ?string $allowedExtensionsSig = null,
    ): void {
        $this->folderId = 0;
        $this->allowedExtensions = $allowedExtensions;
        $this->allowedExtensionsSig = $allowedExtensionsSig;
    }

    #[On('open-upload-modal')]
    public function open(int $folderId = 0): void
    {
        if (! FilamentMedia::hasPermission('files.create')) {
            Notification::make()
                ->title(trans('filament-media::media.permission_denied'))
                ->danger()
                ->send();

            return;
        }

        if ($folderId > 0) {
            $folder = MediaFolder::find($folderId);
            if (! $folder) {
                Notification::make()
                    ->title(trans('filament-media::media.folder_not_found'))
                    ->danger()
                    ->send();

                return;
            }
        }

        $this->folderId = $folderId;
        $this->isOpen = true;
        $this->reset(['uploadQueue', 'completedCount', 'failedCount']);
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->reset(['uploadQueue', 'completedCount', 'failedCount']);
    }

    public function addToQueue(string $key, string $name, int $size): void
    {
        if (count($this->uploadQueue) >= $this->maxFilesPerUpload) {
            Notification::make()
                ->title(trans('filament-media::media.too_many_files', ['max' => $this->maxFilesPerUpload]))
                ->danger()
                ->send();

            return;
        }

        $this->uploadQueue[$key] = [
            'name' => e($name),
            'size' => $size,
            'status' => 'uploading',
            'progress' => 0,
            'error' => null,
        ];
    }

    public function updateProgress(string $key, int $progress): void
    {
        if (isset($this->uploadQueue[$key])) {
            $this->uploadQueue[$key]['progress'] = min(100, max(0, $progress));
        }
    }

    public function markComplete(string $key): void
    {
        if (isset($this->uploadQueue[$key])) {
            $this->uploadQueue[$key]['status'] = 'completed';
            $this->uploadQueue[$key]['progress'] = 100;
            $this->completedCount++;
            $this->checkAllComplete();
        }
    }

    public function markFailed(string $key, string $error): void
    {
        if (isset($this->uploadQueue[$key])) {
            $this->uploadQueue[$key]['status'] = 'failed';
            $this->uploadQueue[$key]['error'] = e($error);
            $this->failedCount++;
            $this->checkAllComplete();
        }
    }

    public function removeFromQueue(string $key): void
    {
        if (isset($this->uploadQueue[$key])) {
            $status = $this->uploadQueue[$key]['status'];
            unset($this->uploadQueue[$key]);

            if ($status === 'completed') {
                $this->completedCount = max(0, $this->completedCount - 1);
            } elseif ($status === 'failed') {
                $this->failedCount = max(0, $this->failedCount - 1);
            }
        }
    }

    protected function checkAllComplete(): void
    {
        $allComplete = true;
        foreach ($this->uploadQueue as $item) {
            if ($item['status'] !== 'completed' && $item['status'] !== 'failed') {
                $allComplete = false;
                break;
            }
        }

        if ($allComplete && count($this->uploadQueue) > 0) {
            $this->dispatch('media-files-uploaded');
            $this->dispatch('media-folder-created');

            if ($this->completedCount > 0) {
                Notification::make()
                    ->title(trans('filament-media::media.upload_complete', ['count' => $this->completedCount]))
                    ->success()
                    ->send();
            }

            if ($this->failedCount === 0) {
                $this->close();
            }
        }
    }

    public function formatSize(int $bytes): string
    {
        return BaseHelper::humanFilesize($bytes);
    }

    public function getUploadUrl(): string
    {
        return route('media.files.upload');
    }

    public function getMaxSize(): int
    {
        return app(UploadService::class)->getMaxSize();
    }

    public function render(): View
    {
        return view('filament-media::livewire.upload-modal');
    }
}
