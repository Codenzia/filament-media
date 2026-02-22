<?php

namespace Codenzia\FilamentMedia\Livewire;

use Illuminate\Contracts\View\View;

/**
 * Unified media file viewer with a toggle between grid and list layouts.
 *
 * Provides a full context menu with Filament Actions (rename, tags, metadata,
 * visibility, etc.) by reusing the same action traits as the main Media page.
 * The parent model must use the HasMediaFiles trait.
 */
class MediaFiles extends MediaFileBase
{
    public string $layout = 'grid';

    public bool $showLayoutToggle = true;

    public string $columns = 'grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4';

    public function setLayout(string $layout): void
    {
        if (in_array($layout, ['grid', 'list'], true)) {
            $this->layout = $layout;
        }
    }

    public function render(): View
    {
        return view('filament-media::livewire.media-files');
    }
}
