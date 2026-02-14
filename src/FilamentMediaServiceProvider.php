<?php

namespace Codenzia\FilamentMedia;

use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Codenzia\FilamentMedia\Commands\FilamentMediaCommand;
use Livewire\Livewire;

class FilamentMediaServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-media';

    public static string $viewNamespace = 'filament-media';

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
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

        // Migrations are loaded in packageBooted() via loadMigrationsFrom()

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageRegistered(): void
    {
        if (!class_exists('BaseHelper')) {
            class_alias(\Codenzia\FilamentMedia\Helpers\BaseHelper::class, 'BaseHelper');
        }
        if (!class_exists('AdminHelper')) {
            class_alias(\Codenzia\FilamentMedia\Helpers\AdminHelper::class, 'AdminHelper');
        }

        $this->app->bind(\Codenzia\FilamentMedia\Repositories\Interfaces\MediaFileInterface::class, function () {
            return new \Codenzia\FilamentMedia\Repositories\Eloquent\MediaFileRepository(new \Codenzia\FilamentMedia\Models\MediaFile());
        });

        $this->app->bind(\Codenzia\FilamentMedia\Repositories\Interfaces\MediaFolderInterface::class, function () {
            return new \Codenzia\FilamentMedia\Repositories\Eloquent\MediaFolderRepository(new \Codenzia\FilamentMedia\Models\MediaFolder());
        });
    }

    public function packageBooted(): void
    {
        $this->loadHelpers();
        $this->loadViewsFrom(__DIR__ . '/../resources/views', static::$viewNamespace);
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'filament-media');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        // Load migrations directly so they run with php artisan migrate
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Register Livewire components
        Livewire::component('filament-media::upload-modal', \Codenzia\FilamentMedia\Livewire\UploadModal::class);
        Livewire::component('filament-media::preview-modal', \Codenzia\FilamentMedia\Livewire\PreviewModal::class);
        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        // Icon Registration
        FilamentIcon::register($this->getIcons());

        // Handle Stubs
        if (app()->runningInConsole()) {
            foreach (app(Filesystem::class)->files(__DIR__ . '/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/filament-media/{$file->getFilename()}"),
                ], 'filament-media-stubs');
            }
        }

    }

    protected function loadHelpers(): void
    {
        $helperPath = __DIR__ . '/Helpers/helpers.php';

        if (file_exists($helperPath)) {
            require_once $helperPath;
        }
    }

    protected function getAssetPackageName(): ?string
    {
        return 'codenzia/filament-media';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [
            Css::make('filament-media', __DIR__ . '/../resources/dist/filament-media.css'),
            Js::make('filament-media', __DIR__ . '/../resources/dist/filament-media.js')->module(),
        ];
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            FilamentMediaCommand::class,
            \Codenzia\FilamentMedia\Commands\SyncMediaCommand::class,
            \Codenzia\FilamentMedia\Console\Commands\CleanupOrphanedMedia::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected function getIcons(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getRoutes(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScriptData(): array
    {
        return [];
    }

}
