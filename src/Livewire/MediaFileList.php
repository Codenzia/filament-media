<?php

namespace Codenzia\FilamentMedia\Livewire;

use Illuminate\Contracts\View\View;

/**
 * Livewire component that displays media files in a list/table layout.
 *
 * Provides a full context menu with Filament Actions (rename, tags, metadata,
 * visibility, etc.) by reusing the same action traits as the main Media page.
 * The parent model must use the HasMediaFiles trait.
 */
class MediaFileList extends MediaFileBase
{
    public function render(): View
    {
        return view('filament-media::livewire.media-file-list');
    }
}
