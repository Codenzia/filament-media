<?php

namespace Codenzia\FilamentMedia;

use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Livewire\Features\SupportTesting\Testable;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Codenzia\FilamentMedia\Commands\FilamentMediaCommand;
use Codenzia\FilamentMedia\Testing\TestsFilamentMedia;

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

        if (file_exists($package->basePath('/../database/migrations'))) {
            $package->hasMigrations($this->getMigrations());
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageRegistered(): void {}

    public function packageBooted(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'core/media');
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'core/media');
        Blade::anonymousComponentPath(__DIR__ . '/../resources/views/components', 'core');

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
            Js::make('filament-media-jquery', 'https://code.jquery.com/jquery-3.7.1.min.js'),
            Js::make('filament-media', __DIR__ . '/../resources/dist/filament-media.js')->module(),
            Css::make('filament-media', __DIR__ . '/../resources/css/filament-media.css'),
        ];
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            FilamentMediaCommand::class,
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

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            'create_media_tables',
            'add_index_to_media_table',
            'add_alt_to_media_table',
            'add_color_column_to_media_folders_table',
            'make_sure_column_color_in_media_folders_nullable',
            'add_column_visibility_to_table_media_files',
            'change_random_hash_for_media',
        ];
    }
}
