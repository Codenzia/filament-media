<?php

namespace Codenzia\FilamentMedia\Livewire;

use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Pages\Concerns\HasExtendedMediaActions;
use Codenzia\FilamentMedia\Pages\Concerns\HasFileManagementActions;
use Codenzia\FilamentMedia\Pages\Concerns\HasMediaHelpers;
use Codenzia\FilamentMedia\Pages\Concerns\InteractsWithMediaEvents;
use Codenzia\FilamentMedia\Services\FavoriteService;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Abstract base class for media file display components (grid, list, unified viewer).
 *
 * Contains all shared Filament Actions, computed properties, and stub properties
 * required by the action traits. Subclasses only need to provide render().
 */
abstract class MediaFileBase extends Component implements HasActions, HasForms
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
            return MediaFile::where('fileable_type', $this->fileableType)
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
    // Helpers
    // ──────────────────────────────────────────────────

    /**
     * Build the item data array used by the context menu and Alpine.js.
     */
    public function buildItemData(MediaFile $file): array
    {
        return [
            'id' => $file->id,
            'url' => $file->indirect_url,
            'full_url' => $file->indirect_url,
            'name' => $file->name,
            'type' => $file->type,
            'is_folder' => false,
            'linked_model_label' => $file->fileable_type ? class_basename($file->fileable_type) : null,
            'is_favorited' => in_array($file->id, $this->favoritedFileIds),
        ];
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
     * Not applicable in the file display context.
     */
    public function moveItemsToFolder(array $items, int $folderId): void
    {
        // Not applicable in file display context
    }

    // ──────────────────────────────────────────────────
    // Render
    // ──────────────────────────────────────────────────

    abstract public function render(): View;
}
