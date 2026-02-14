<?php

namespace Codenzia\FilamentMedia\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Attributes\On;
use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Facades\FilamentMedia;
use Filament\Notifications\Notification;

class PreviewModal extends Component
{
    public bool $isOpen = false;
    public ?int $currentFileId = null;

    /** @var array<int> */
    public array $fileIds = [];
    public int $currentIndex = 0;

    // Current file details (escaped for XSS prevention)
    public string $name = '';
    public string $url = '';
    public string $fullUrl = '';
    public string $mimeType = '';
    public string $size = '';
    public string $alt = '';
    public string $createdAt = '';
    public string $fileType = 'document'; // image, video, audio, document
    public bool $fileExists = true;

    #[On('open-preview-modal')]
    public function open(int $fileId, array $fileIds = []): void
    {
        // Verify user has permission to view files
        if (!FilamentMedia::hasPermission('files.read')) {
            Notification::make()
                ->title(trans('filament-media::media.permission_denied'))
                ->danger()
                ->send();
            return;
        }

        // Validate and sanitize file IDs (ensure all are integers)
        $this->fileIds = array_map('intval', array_filter($fileIds, 'is_numeric'));
        $this->currentIndex = array_search($fileId, $this->fileIds);

        if ($this->currentIndex === false) {
            $this->currentIndex = 0;
            $this->fileIds = [$fileId];
        }

        $this->loadFile($fileId);
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->reset(['currentFileId', 'fileIds', 'currentIndex', 'name', 'url', 'fullUrl', 'mimeType', 'size', 'alt', 'createdAt', 'fileType', 'fileExists']);
    }

    public function next(): void
    {
        if ($this->currentIndex < count($this->fileIds) - 1) {
            $this->currentIndex++;
            $this->loadFile($this->fileIds[$this->currentIndex]);
        }
    }

    public function previous(): void
    {
        if ($this->currentIndex > 0) {
            $this->currentIndex--;
            $this->loadFile($this->fileIds[$this->currentIndex]);
        }
    }

    protected function loadFile(int $fileId): void
    {
        $file = MediaFile::find($fileId);

        if (!$file) {
            $this->close();
            return;
        }

        $this->currentFileId = $file->id;
        // Escape user-provided content to prevent XSS
        $this->name = e($file->name ?? '');
        $this->url = $file->url ?? '';
        $this->fullUrl = FilamentMedia::url($file->url);
        $this->mimeType = $file->mime_type ?? '';
        $this->size = $file->human_size ?? '';
        $this->alt = e($file->alt ?? '');
        $this->createdAt = $file->created_at?->format('M j, Y') ?? '';
        $this->fileType = $this->determineFileType($this->mimeType);
        $this->fileExists = $this->checkFileExists($file->url);
    }

    /**
     * Check if the file exists on disk.
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

    protected function determineFileType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        if (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }

        return 'document';
    }

    public function hasNext(): bool
    {
        return $this->currentIndex < count($this->fileIds) - 1;
    }

    public function hasPrevious(): bool
    {
        return $this->currentIndex > 0;
    }

    public function getThumbnails(): array
    {
        if (count($this->fileIds) <= 1) {
            return [];
        }

        $thumbnails = [];
        $files = MediaFile::whereIn('id', $this->fileIds)->get()->keyBy('id');

        foreach ($this->fileIds as $index => $fileId) {
            $file = $files->get($fileId);
            if ($file) {
                $thumbnails[] = [
                    'id' => $file->id,
                    'name' => e($file->name ?? ''), // Escape for XSS prevention
                    'thumbnail' => $file->canGenerateThumbnails()
                        ? FilamentMedia::url($file->url)
                        : null,
                    'mime_type' => $file->mime_type,
                    'is_current' => $index === $this->currentIndex,
                ];
            }
        }

        return $thumbnails;
    }

    public function goToIndex(int $index): void
    {
        if (isset($this->fileIds[$index])) {
            $this->currentIndex = $index;
            $this->loadFile($this->fileIds[$index]);
        }
    }

    public function render(): View
    {
        return view('filament-media::livewire.preview-modal', [
            'thumbnails' => $this->getThumbnails(),
        ]);
    }
}
