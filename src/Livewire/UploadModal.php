<?php

namespace Codenzia\FilamentMedia\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\Attributes\On;
use Filament\Notifications\Notification;
use Codenzia\FilamentMedia\Facades\FilamentMedia;
use Codenzia\FilamentMedia\Models\MediaFolder;

class UploadModal extends Component
{
    public bool $isOpen = false;
    public int $folderId = 0;

    /** @var array<string, array{name: string, size: int, status: string, progress: int, error: ?string}> */
    public array $uploadQueue = [];

    public int $completedCount = 0;
    public int $failedCount = 0;

    /** Maximum files allowed per upload session */
    protected int $maxFilesPerUpload = 50;

    public function mount(): void
    {
        $this->folderId = 0;
    }

    #[On('open-upload-modal')]
    public function open(int $folderId = 0): void
    {
        // Verify user has permission to upload
        if (!FilamentMedia::hasPermission('files.create')) {
            Notification::make()
                ->title(trans('filament-media::media.permission_denied'))
                ->danger()
                ->send();
            return;
        }

        // Verify folder exists and user can access it
        if ($folderId > 0) {
            $folder = MediaFolder::find($folderId);
            if (!$folder) {
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

    /**
     * Called from JS when a file is selected for upload.
     */
    public function addToQueue(string $key, string $name, int $size): void
    {
        // Limit number of files to prevent DOS
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

    /**
     * Called from JS to update upload progress.
     */
    public function updateProgress(string $key, int $progress): void
    {
        if (isset($this->uploadQueue[$key])) {
            $this->uploadQueue[$key]['progress'] = min(100, max(0, $progress));
        }
    }

    /**
     * Called from JS when upload completes successfully.
     */
    public function markComplete(string $key): void
    {
        if (isset($this->uploadQueue[$key])) {
            $this->uploadQueue[$key]['status'] = 'completed';
            $this->uploadQueue[$key]['progress'] = 100;
            $this->completedCount++;
            $this->checkAllComplete();
        }
    }

    /**
     * Called from JS when upload fails.
     */
    public function markFailed(string $key, string $error): void
    {
        if (isset($this->uploadQueue[$key])) {
            $this->uploadQueue[$key]['status'] = 'failed';
            $this->uploadQueue[$key]['error'] = e($error);
            $this->failedCount++;
            $this->checkAllComplete();
        }
    }

    /**
     * Remove a file from the queue (for completed/failed items).
     */
    public function removeFromQueue(string $key): void
    {
        if (isset($this->uploadQueue[$key])) {
            $status = $this->uploadQueue[$key]['status'];
            unset($this->uploadQueue[$key]);

            // Adjust counts
            if ($status === 'completed') {
                $this->completedCount = max(0, $this->completedCount - 1);
            } elseif ($status === 'failed') {
                $this->failedCount = max(0, $this->failedCount - 1);
            }
        }
    }

    /**
     * Check if all uploads are complete and handle notifications.
     */
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
            // Notify parent to refresh - dispatch as browser event for cross-component communication
            $this->dispatch('media-files-uploaded');
            $this->dispatch('media-folder-created');

            if ($this->completedCount > 0) {
                Notification::make()
                    ->title(trans('filament-media::media.upload_complete', ['count' => $this->completedCount]))
                    ->success()
                    ->send();
            }

            if ($this->failedCount === 0) {
                // Auto-close if all succeeded
                $this->close();
            }
        }
    }

    /**
     * Format file size for display.
     */
    public function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Get the upload URL for JS.
     */
    public function getUploadUrl(): string
    {
        return route('media.files.upload');
    }

    /**
     * Get max file size in bytes for JS validation.
     */
    public function getMaxSize(): int
    {
        return FilamentMedia::getMaxSize();
    }

    public function render(): View
    {
        return view('filament-media::livewire.upload-modal');
    }
}
