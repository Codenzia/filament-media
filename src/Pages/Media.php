<?php

namespace Codenzia\FilamentMedia\Pages;

use Codenzia\FilamentMedia\Pages\Concerns\HasExtendedMediaActions;
use Codenzia\FilamentMedia\Pages\Concerns\HasFileManagementActions;
use Codenzia\FilamentMedia\Pages\Concerns\HasMediaHelpers;
use Codenzia\FilamentMedia\Pages\Concerns\InteractsWithMediaEvents;
use Codenzia\FilamentMedia\Pages\Concerns\InteractsWithMediaQueries;
use Codenzia\FilamentMedia\Pages\Concerns\InteractsWithMediaState;
use Codenzia\FilamentMedia\Pages\Concerns\HasConditionalPageShield;
use Filament\Pages\Page;
use Livewire\Attributes\Url;
use Livewire\WithFileUploads;

/**
 * Primary Filament page for the media manager interface.
 *
 * Composes traits for state management, data queries, event handling,
 * file management actions, extended media actions, and shared helpers.
 */
class Media extends Page
{
    use HasConditionalPageShield;
    use WithFileUploads;
    use HasMediaHelpers;
    use InteractsWithMediaQueries;
    use InteractsWithMediaState;
    use InteractsWithMediaEvents;
    use HasFileManagementActions;
    use HasExtendedMediaActions;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected string $view = 'filament-media::pages.media';

    // Livewire state
    #[Url(as: 'folder')]
    public int $folderId = 0;

    #[Url(as: 'view')]
    public string $viewIn = 'all_media';

    #[Url(as: 'filter')]
    public string $filter = 'everything';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at-desc';

    #[Url(as: 'q')]
    public string $search = '';

    public int $collectionId = 0;

    public string $viewType = 'grid';
    public array $selectedItems = [];
    public ?array $previewItem = null;
    public bool $showDetailsPanel = true;
    public bool $isLoading = false;
    public $uploadedFiles = [];

    // Pagination (overridden from config in mount)
    public int $perPage = 200;
    public int $currentPage = 1;
    public int $refreshKey = 0;
    public bool $hasMorePages = false;
    public int $totalFileCount = 0;
    public int $displayedFileCount = 0;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return config('media.navigation.media.icon', static::$navigationIcon);
    }

    public static function getNavigationLabel(): string
    {
        $label = config('media.navigation.media.label');

        return $label ?: trans('filament-media::media.menu_name');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('media.navigation.shared_group')
            ?? config('media.navigation.media.group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('media.navigation.media.sort', 1);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return config('media.navigation.media.visible', true);
    }
}
