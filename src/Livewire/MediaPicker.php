<?php

namespace Codenzia\FilamentMedia\Livewire;

use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaFolder;
use Codenzia\FilamentMedia\Services\MediaUrlService;
use Illuminate\Support\Arr;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Livewire component for browsing and selecting media files with folder navigation,
 * search, filtering, sorting, and single/multiple selection modes.
 */
class MediaPicker extends Component
{
    use WithPagination;

    public array $selected = [];

    public bool $multiple = false;

    public array $acceptedFileTypes = [];

    public int $maxFiles = 0;

    public ?string $collection = null;

    public ?string $directory = null;

    public int $folderId = 0;

    public string $search = '';

    public string $filter = 'everything';

    public string $sortBy = 'name-asc';

    public string $viewMode = 'grid';

    public string $fieldId = '';

    public function mount(
        bool $multiple = false,
        array $acceptedFileTypes = [],
        int $maxFiles = 0,
        ?string $collection = null,
        ?string $directory = null,
        string $fieldId = ''
    ): void {
        $this->multiple = $multiple;
        $this->acceptedFileTypes = $acceptedFileTypes;
        $this->maxFiles = $maxFiles;
        $this->collection = $collection;
        $this->directory = $directory;
        $this->fieldId = $fieldId;
        $this->viewMode = config('media.picker.default_view', 'grid');
    }

    public function getFilesProperty(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = MediaFile::inFolder($this->folderId)
            ->filterByType($this->filter)
            ->sorted($this->sortBy)
            ->search($this->search);

        if (! empty($this->acceptedFileTypes)) {
            $query->where(function ($q) {
                foreach ($this->acceptedFileTypes as $type) {
                    if (str_ends_with($type, '/*')) {
                        $prefix = str_replace('/*', '/', $type);
                        $q->orWhere('mime_type', 'LIKE', $prefix . '%');
                    } else {
                        $q->orWhere('mime_type', $type);
                    }
                }
            });
        }

        return $query->paginate(config('media.pagination.per_page', 30));
    }

    public function getFoldersProperty(): \Illuminate\Database\Eloquent\Collection
    {
        if (config('media.picker.show_folders', true) === false) {
            return collect();
        }

        return MediaFolder::where('parent_id', $this->folderId)
            ->orderBy('name')
            ->get();
    }

    public function getBreadcrumbsProperty(): array
    {
        $breadcrumbs = [['id' => 0, 'name' => trans('filament-media::media.all_media')]];

        if ($this->folderId) {
            $folder = MediaFolder::find($this->folderId);
            if ($folder) {
                foreach ($folder->parents->reverse() as $parent) {
                    $breadcrumbs[] = ['id' => $parent->id, 'name' => $parent->name];
                }
                $breadcrumbs[] = ['id' => $folder->id, 'name' => $folder->name];
            }
        }

        return $breadcrumbs;
    }

    public function openFolder(int $folderId): void
    {
        $this->folderId = $folderId;
        $this->resetPage();
    }

    public function selectFile(int $fileId): void
    {
        if ($this->multiple) {
            if (in_array($fileId, $this->selected)) {
                $this->selected = array_values(array_diff($this->selected, [$fileId]));
            } else {
                if ($this->maxFiles > 0 && count($this->selected) >= $this->maxFiles) {
                    return;
                }
                $this->selected[] = $fileId;
            }
        } else {
            $this->selected = [$fileId];
        }
    }

    /**
     * Refresh the file and folder listings by clearing cached computed properties.
     */
    public function refresh(): void
    {
        unset($this->files, $this->folders);
    }

    /**
     * Re-query files after new uploads are completed via the upload modal.
     */
    #[On('media-files-uploaded')]
    public function onFilesUploaded(): void
    {
        $this->refresh();
    }

    public function confirm(): void
    {
        $value = $this->multiple ? $this->selected : ($this->selected[0] ?? null);

        $this->dispatch('media-picker-selected', fieldId: $this->fieldId, value: $value);
    }

    public function cancel(): void
    {
        $this->dispatch('media-picker-cancelled', fieldId: $this->fieldId);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('filament-media::livewire.media-picker', [
            'files' => $this->files,
            'folders' => $this->folders,
            'breadcrumbs' => $this->breadcrumbs,
        ]);
    }
}
