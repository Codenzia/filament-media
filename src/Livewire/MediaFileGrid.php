<?php

namespace Codenzia\FilamentMedia\Livewire;

use Codenzia\FilamentMedia\Pages\Concerns\HasExtendedMediaActions;
use Codenzia\FilamentMedia\Pages\Concerns\HasFileManagementActions;
use Codenzia\FilamentMedia\Pages\Concerns\HasMediaHelpers;
use Codenzia\FilamentMedia\Pages\Concerns\InteractsWithMediaEvents;
use Codenzia\FilamentMedia\Services\FavoriteService;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Livewire component that displays a grid of media files attached to a model.
 *
 * Provides a full context menu with Filament Actions (rename, tags, metadata,
 * visibility, etc.) by reusing the same action traits as the main Media page.
 * The parent model must use the HasMediaFiles trait.
 */
class MediaFileGrid extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;
    use WithFileUploads;
    use HasMediaHelpers;
    use HasFileManagementActions;
    use HasExtendedMediaActions;
    use InteractsWithMediaEvents;

    // ──────────────────────────────────────────────────
    // Component Props (mount parameters)
    // ──────────────────────────────────────────────────

    /** Single model to load files from via relationship */
    public ?Model $record = null;

    public string $relationship = 'files';

    /** Multi-model mode: query files across many records of the same morph type */
    public ?string $fileableType = null;

    public array $fileableIds = [];

    public bool $deletable = false;

    public string $columns = 'grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4';

    public string $emptyMessage = '';

    public bool $contextMenu = true;

    public array $contextMenuExclude = [];

    // ──────────────────────────────────────────────────
    // Stub properties required by the action traits
    // ──────────────────────────────────────────────────

    public int $folderId = 0;

    public int $collectionId = 0;

    public array $selectedItems = [];

    // ──────────────────────────────────────────────────
    // Computed Properties
    // ──────────────────────────────────────────────────

    #[Computed]
    public function files()
    {
        // Multi-model mode: query files across many records of the same morph type
        if ($this->fileableType && ! empty($this->fileableIds)) {
            return \Codenzia\FilamentMedia\Models\MediaFile::where('fileable_type', $this->fileableType)
                ->whereIn('fileable_id', $this->fileableIds)
                ->latest()
                ->get();
        }

        // Single-model mode: load files via relationship
        if (! $this->record) {
            return collect();
        }

        return $this->record->{$this->relationship}()->get();
    }

    /**
     * Get the IDs of files favorited by the current user.
     */
    #[Computed]
    public function favoritedFileIds(): array
    {
        $userId = Auth::guard()->id();

        if (! $userId) {
            return [];
        }

        $favorites = app(FavoriteService::class)->getFavorites($userId);

        return collect($favorites)
            ->filter(fn ($item) => ! ($item['is_folder'] ?? false))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }

    // ──────────────────────────────────────────────────
    // Overrides & Stubs
    // ──────────────────────────────────────────────────

    /**
     * Refresh the file list after any action completes (including file uploads).
     */
    #[On('files-uploaded')]
    public function refresh(): void
    {
        unset($this->files, $this->favoritedFileIds);
    }

    /**
     * Stub required by move_to_folder action in HasFileManagementActions.
     * Not applicable in the file grid context.
     */
    public function moveItemsToFolder(array $items, int $folderId): void
    {
        // Not applicable in file grid context
    }

    // ──────────────────────────────────────────────────
    // Render
    // ──────────────────────────────────────────────────

    public function render(): View
    {
        return view('filament-media::livewire.media-file-grid');
    }
}
