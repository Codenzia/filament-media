<?php

namespace Codenzia\FilamentMedia\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Codenzia\FilamentMedia\FilamentMediaServiceProvider;
use Filament\Support\SupportServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Codenzia\\FilamentMedia\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        Storage::fake('public');
    }

    /**
     * Only this package's own service provider is listed explicitly.
     * Filament's providers + the Livewire / Blade / Icons providers it
     * depends on are auto-discovered via Composer's
     * extra.laravel.providers metadata. Keeps the TestCase compatible
     * across Filament v4 and v5 without hand-curating the import list.
     */
    protected function getPackageProviders($app): array
    {
        return [
            // Livewire must be explicitly listed (its binding "livewire.finder"
            // doesn't survive Testbench's package:discover alone). Same for
            // Blade Icons + Heroicons + Filament Support so tests that render
            // <x-filament::icon> in views can resolve the view component and
            // the underlying heroicon SVG set.
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            LivewireServiceProvider::class,
            SupportServiceProvider::class,
            FilamentMediaServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        config()->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));

        config()->set('media', require __DIR__.'/../config/media.php');
        config()->set('filesystems.default', 'public');
        config()->set('filesystems.disks.public', [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
