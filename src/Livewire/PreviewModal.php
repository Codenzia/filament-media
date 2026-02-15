<?php

namespace Codenzia\FilamentMedia\Livewire;

use Codenzia\FilamentMedia\Facades\FilamentMedia;
use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Services\MediaUrlService;
use Codenzia\FilamentMedia\Services\VersionService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Livewire component that displays a file preview modal with navigation
 * between files, version history, and thumbnail strip support.
 */
class PreviewModal extends Component
{
    public bool $isOpen = false;
    public ?int $currentFileId = null;

    /** @var array<int> */
    public array $fileIds = [];
    public int $currentIndex = 0;

    public string $name = '';
    public string $url = '';
    public string $fullUrl = '';
    public string $mimeType = '';
    public string $size = '';
    public string $alt = '';
    public string $createdAt = '';
    public string $fileType = 'document';
    public bool $fileExists = true;
    public array $versions = [];

    #[On('open-preview-modal')]
    public function open(int $fileId, array $fileIds = []): void
    {
        if (! FilamentMedia::hasPermission('files.read')) {
            Notification::make()
                ->title(trans('filament-media::media.permission_denied'))
                ->danger()
                ->send();

            return;
        }

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
        $this->reset([
            'currentFileId', 'fileIds', 'currentIndex',
            'name', 'url', 'fullUrl', 'mimeType', 'size',
            'alt', 'createdAt', 'fileType', 'fileExists', 'versions',
        ]);
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
        $file = MediaFile::withTrashed()->find($fileId);

        if (! $file) {
            $this->close();

            return;
        }

        $urlService = app(MediaUrlService::class);

        $this->currentFileId = $file->id;
        $this->name = e($file->name ?? '');
        $this->url = $file->url ?? '';
        $this->fullUrl = $urlService->visibilityAwareUrl($file);
        $this->mimeType = $file->mime_type ?? '';
        $this->size = $file->human_size ?? '';
        $this->alt = e($file->alt ?? '');
        $this->createdAt = $file->created_at?->format('M j, Y') ?? '';
        $this->fileType = $this->determineFileType($this->mimeType);
        $this->fileExists = $urlService->fileExists($file->url);

        // Load version history if versioning is enabled
        $this->versions = [];
        if (config('media.features.versioning', true)) {
            $this->versions = app(VersionService::class)
                ->getVersions($file)
                ->map(fn ($v) => [
                    'id' => $v->id,
                    'version_number' => $v->version_number,
                    'size' => $v->size,
                    'created_at' => $v->created_at?->format('M j, Y H:i'),
                    'changelog' => $v->changelog,
                    'user' => $v->user?->name ?? 'System',
                ])
                ->toArray();
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

        $urlService = app(MediaUrlService::class);
        $thumbnails = [];
        $files = MediaFile::withTrashed()->whereIn('id', $this->fileIds)->get()->keyBy('id');

        foreach ($this->fileIds as $index => $fileId) {
            $file = $files->get($fileId);
            if ($file) {
                $thumbnails[] = [
                    'id' => $file->id,
                    'name' => e($file->name ?? ''),
                    'thumbnail' => $this->resolveThumbUrl($file, $urlService),
                    'mime_type' => $file->mime_type,
                    'is_current' => $index === $this->currentIndex,
                ];
            }
        }

        return $thumbnails;
    }

    protected function resolveThumbUrl(MediaFile $file, MediaUrlService $urlService): ?string
    {
        if ($file->canGenerateThumbnails()) {
            return $urlService->url($file->url);
        }

        if ($file->visibility === 'private' && str_starts_with($file->mime_type ?? '', 'image/')) {
            return $urlService->visibilityAwareUrl($file);
        }

        return null;
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
