<?php

namespace Codenzia\FilamentMedia;

use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentMediaPlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-media';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([
            Pages\Media::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
