<?php

namespace Codenzia\FilamentMedia;

use Codenzia\FilamentMedia\Livewire\MediaFileGrid;
use Codenzia\FilamentMedia\Livewire\MediaFileList;
use Codenzia\FilamentMedia\Livewire\MediaFiles;
use Codenzia\FilamentMedia\Livewire\MediaPicker;
use Codenzia\FilamentMedia\Livewire\PreviewModal;
use Codenzia\FilamentMedia\Livewire\UploadModal;
use Codenzia\FilamentMedia\Services\ExportImportService;
use Codenzia\FilamentMedia\Services\FavoriteService;
use Codenzia\FilamentMedia\Services\FileOperationService;
use Codenzia\FilamentMedia\Services\ImageService;
use Codenzia\FilamentMedia\Services\MediaUrlService;
use Codenzia\FilamentMedia\Services\MetadataService;
use Codenzia\FilamentMedia\Services\OrphanScanService;
use Codenzia\FilamentMedia\Services\SearchService;
use Codenzia\FilamentMedia\Services\StorageDriverService;
use Codenzia\FilamentMedia\Services\TagService;
use Codenzia\FilamentMedia\Services\ThumbnailService;
use Codenzia\FilamentMedia\Services\UploadService;
use Codenzia\FilamentMedia\Services\UploadsManager;
use Codenzia\FilamentMedia\Services\VersionService;
use Codenzia\FilamentMedia\Widgets\FilesUploadWidget;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Filesystem\Filesystem;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Laravel service provider for the Filament Media package.
 *
 * Registers all singleton services, Livewire components, Filament assets,
 * config/views/translations, routes, migrations, and Artisan commands.
 */
class FilamentMediaServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-media';

    public static string $viewNamespace = 'filament-media';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasCommands($this->getCommands())
            ->hasRoutes()
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('codenzia/filament-media');
            });

        $configFileName = 'media';

        if (file_exists($package->basePath("/../config/{$configFileName}.php"))) {
            $package->hasConfigFile($configFileName);
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageRegistered(): void
    {
        $this->loadHelpers();

        // Core services
        $this->app->singleton(StorageDriverService::class);
        $this->app->singleton(ThumbnailService::class);
        $this->app->singleton(UploadsManager::class);
        $this->app->singleton(MediaUrlService::class);
        $this->app->singleton(ImageService::class);
        $this->app->singleton(UploadService::class);
        $this->app->singleton(FileOperationService::class);
        $this->app->singleton(FavoriteService::class);

        // Feature services
        $this->app->singleton(TagService::class);
        $this->app->singleton(MetadataService::class);
        $this->app->singleton(SearchService::class);
        $this->app->singleton(VersionService::class);
        $this->app->singleton(ExportImportService::class);
        $this->app->singleton(OrphanScanService::class);

        // FilamentMedia facade target
        $this->app->singleton(FilamentMedia::class);
    }

    public function packageBooted(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', static::$viewNamespace);
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'filament-media');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Register Livewire components
        $components = [
            'filament-media::upload-modal' => UploadModal::class,
            'filament-media::preview-modal' => PreviewModal::class,
            'filament-media::media-picker' => MediaPicker::class,
            'filament-media::files-upload-widget' => FilesUploadWidget::class,
            'filament-media::media-file-grid' => MediaFileGrid::class,
            'filament-media::media-file-list' => MediaFileList::class,
            'filament-media::media-files' => MediaFiles::class,
        ];

        foreach ($components as $alias => $class) {
            Livewire::component($alias, $class);
        }

        // Livewire v4's Finder only checks registered namespaces for
        // `ns::component` lookups, never classComponents entries.
        if (method_exists(Livewire::getFacadeRoot(), 'resolveMissingComponent')) {
            Livewire::resolveMissingComponent(fn (string $name): ?string => $components[$name] ?? null);
        }

        // Assets
        FilamentAsset::register($this->getAssets(), $this->getAssetPackageName());
        FilamentAsset::registerScriptData($this->getScriptData(), $this->getAssetPackageName());
        FilamentIcon::register($this->getIcons());

        // Stubs
        if (app()->runningInConsole()) {
            foreach (app(Filesystem::class)->files(__DIR__.'/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/filament-media/{$file->getFilename()}"),
                ], 'filament-media-stubs');
            }
        }
    }

    protected function loadHelpers(): void
    {
        $helperPath = __DIR__.'/Helpers/helpers.php';

        if (file_exists($helperPath)) {
            require_once $helperPath;
        }
    }

    protected function getAssetPackageName(): ?string
    {
        return 'codenzia/filament-media';
    }

    protected function getAssets(): array
    {
        return [
            Css::make('filament-media', __DIR__.'/../resources/dist/filament-media.css'),
            Js::make('filament-media', __DIR__.'/../resources/dist/filament-media.js')->module(),
        ];
    }

    protected function getCommands(): array
    {
        return [
            Commands\FilamentMediaCommand::class,
            Commands\SyncMediaCommand::class,
            Console\Commands\CleanupOrphanedMedia::class,
        ];
    }

    protected function getIcons(): array
    {
        return [];
    }

    protected function getRoutes(): array
    {
        return [];
    }

    protected function getScriptData(): array
    {
        return [];
    }
}
