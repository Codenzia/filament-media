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
        $pages = [
            Pages\Media::class,
        ];

        // Register settings page if enabled in config
        if (config('media.settings.enabled', true)) {
            $pages[] = Pages\MediaSettings::class;
        }

        $panel->pages($pages);
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
