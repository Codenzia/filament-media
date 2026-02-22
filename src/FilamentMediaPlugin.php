<?php

namespace Codenzia\FilamentMedia;

use Filament\Contracts\Plugin;
use Filament\Panel;

/**
 * Filament panel plugin that registers the media manager and settings pages.
 */
class FilamentMediaPlugin implements Plugin
{
    protected bool $mediaManagerPage = true;

    protected bool $settingsPage = true;

    public function getId(): string
    {
        return 'filament-media';
    }

    /**
     * Show or hide the standalone Media Manager page for this panel.
     * The MediaPickerField still works regardless of this setting.
     */
    public function showMediaManager(bool $show = true): static
    {
        $this->mediaManagerPage = $show;

        return $this;
    }

    /**
     * Show or hide the Media Settings page for this panel.
     */
    public function showSettings(bool $show = true): static
    {
        $this->settingsPage = $show;

        return $this;
    }

    public function register(Panel $panel): void
    {
        $pages = [];
        $nav = config('media.navigation', []);

        if ($this->mediaManagerPage && ($nav['media']['visible'] ?? true)) {
            $pages[] = Pages\Media::class;
        }

        if ($this->settingsPage && ($nav['settings']['visible'] ?? true) && config('media.settings.enabled', true)) {
            $pages[] = Pages\MediaSettings::class;
        }

        $panel->pages($pages);
    }

    public function boot(Panel $panel): void {}

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
