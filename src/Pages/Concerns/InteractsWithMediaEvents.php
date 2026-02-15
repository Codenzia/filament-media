<?php

namespace Codenzia\FilamentMedia\Pages\Concerns;

use Livewire\Attributes\On;

/**
 * Handles Livewire event listeners for the media manager page.
 *
 * Bridges JavaScript-dispatched context menu events to their corresponding
 * Filament modal actions, enabling seamless interaction between the frontend
 * and server-side action handlers.
 */
trait InteractsWithMediaEvents
{
    #[On('open-download-url-modal')]
    public function openDownloadUrlModal(): void
    {
        $this->mountAction('download_url');
    }

    #[On('open-rename-modal')]
    public function openRenameModal(array $items): void
    {
        if (count($items) !== 1) {
            return;
        }

        $this->mountAction('rename', ['items' => $items]);
    }

    #[On('update-folder-id')]
    public function updateFolderId($id): void
    {
        $this->folderId = $id;
    }

    #[On('open-trash-modal')]
    public function openTrashModal(array $items): void
    {
        $this->mountAction('trash', ['items' => $items]);
    }

    #[On('open-delete-modal')]
    public function openDeleteModal(array $items): void
    {
        $this->mountAction('delete', ['items' => $items]);
    }

    #[On('open-empty-trash-modal')]
    public function openEmptyTrashModal(): void
    {
        $this->mountAction('empty_trash');
    }

    #[On('open-create-folder-modal')]
    public function openCreateFolderModal(): void
    {
        $this->mountAction('create_folder');
    }

    #[On('open-favorite-modal')]
    public function openFavoriteModal(array $items): void
    {
        $this->mountAction('favorite', ['items' => $items]);
    }

    #[On('open-remove-favorite-modal')]
    public function openRemoveFavoriteModal(array $items): void
    {
        $this->mountAction('remove_favorite', ['items' => $items]);
    }

    #[On('open-properties-modal')]
    public function openPropertiesModal(array $items): void
    {
        $this->mountAction('properties', ['items' => $items]);
    }

    #[On('open-alt-text-modal')]
    public function openAltTextModal(array $items): void
    {
        $this->mountAction('alt_text', ['items' => $items]);
    }

    #[On('open-move-modal')]
    public function openMoveModal(array $items): void
    {
        $this->mountAction('move', ['items' => $items]);
    }

    #[On('open-tag-modal')]
    public function openTagModal(array $items): void
    {
        $this->mountAction('tag', ['items' => $items]);
    }

    #[On('open-collection-modal')]
    public function openCollectionModal(array $items): void
    {
        $this->mountAction('add_to_collection', ['items' => $items]);
    }

    #[On('open-version-modal')]
    public function openVersionModal(array $items): void
    {
        $this->mountAction('upload_new_version', ['items' => $items]);
    }

    #[On('open-metadata-modal')]
    public function openMetadataModal(array $items): void
    {
        $this->mountAction('edit_metadata', ['items' => $items]);
    }

    #[On('open-export-modal')]
    public function openExportModal(array $items): void
    {
        $this->mountAction('export_files', ['items' => $items]);
    }

    #[On('open-copy-modal')]
    public function openCopyModal(array $items): void
    {
        $this->mountAction('copy', ['items' => $items]);
    }

    #[On('open-remove-from-collection-modal')]
    public function openRemoveFromCollectionModal(array $items): void
    {
        $this->mountAction('remove_from_collection', ['items' => $items]);
    }

    #[On('open-parent-details-modal')]
    public function openParentDetailsModal(array $items): void
    {
        $this->mountAction('view_parent_details', ['items' => $items]);
    }

    #[On('media-folder-created')]
    public function onMediaFolderCreated(): void
    {
        $this->refresh();
    }

    #[On('media-files-uploaded')]
    public function onMediaFilesUploaded(): void
    {
        $this->refresh();
    }
}
