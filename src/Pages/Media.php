<?php

namespace Codenzia\FilamentMedia\Pages;

use Codenzia\FilamentMedia\Pages\Concerns\HasExtendedMediaActions;
use Codenzia\FilamentMedia\Pages\Concerns\HasFileManagementActions;
use Codenzia\FilamentMedia\Pages\Concerns\InteractsWithMediaEvents;
use Codenzia\FilamentMedia\Pages\Concerns\InteractsWithMediaQueries;
use Codenzia\FilamentMedia\Pages\Concerns\InteractsWithMediaState;
use Filament\Pages\Page;
use Livewire\Attributes\Url;
use Livewire\WithFileUploads;

class Media extends Page
{
    use WithFileUploads;
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

    // Pagination
    public int $perPage = 30;
    public int $currentPage = 1;
    public int $refreshKey = 0;

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
