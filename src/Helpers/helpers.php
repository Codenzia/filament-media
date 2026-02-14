<?php

use Codenzia\FilamentMedia\Models\MediaSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

if (!function_exists('setting')) {
    /**
     * Get a setting value from database, then config, with caching.
     *
     * This helper provides a unified way to access media settings.
     * Priority order:
     * 1. Database (MediaSetting with system scope) - for runtime configuration
     * 2. Config file (config/media.php) - for default/fallback values
     * 3. Provided default value
     *
     * @param string $key The setting key (e.g., 'media_driver', 'media_max_file_size')
     * @param mixed $default Default value if setting is not found
     * @return mixed
     */
    function setting(string $key, mixed $default = null): mixed
    {
        // Cache key for this setting
        $cacheKey = 'filament-media.setting.' . $key;

        // Try to get from cache first (5 minute cache)
        return Cache::remember($cacheKey, 300, function () use ($key, $default) {
            // 1. First, check database for system settings
            try {
                $dbValue = MediaSetting::getSystemSetting($key);
                if ($dbValue !== null) {
                    return $dbValue;
                }
            } catch (\Throwable $e) {
                // Database might not be available (during migrations, etc.)
                // Continue to config fallback
            }

            // 2. Map common setting keys to their config equivalents
            $configMap = [
                'media_driver' => 'media.driver',
                'media_max_file_size' => 'media.max_file_size',
                'max_upload_filesize' => 'media.max_file_size',
                'media_chunk_enabled' => 'media.chunk.enabled',
                'media_chunk_size' => 'media.chunk.chunk_size',
                'media_chunk_max_file_size' => 'media.chunk.max_file_size',
                'media_watermark_enabled' => 'media.watermark.enabled',
                'media_watermark_source' => 'media.watermark.source',
                'media_watermark_position' => 'media.watermark.position',
                'media_watermark_size' => 'media.watermark.size',
                'media_watermark_opacity' => 'media.watermark.opacity',
                'media_convert_file_name_to_uuid' => 'media.convert_file_name_to_uuid',
                'media_use_original_name_for_file_path' => 'media.use_original_name_for_file_path',
                'media_default_placeholder_image' => 'media.default_placeholder_image',
                'media_sizes' => 'media.sizes',
                'media_folders_can_have_colors' => 'media.folders_can_have_colors',
                'media_turn_off_automatic_url_translation_into_latin' => 'media.turn_off_automatic_url_translation_into_latin',
                'media_allowed_mime_types' => 'media.allowed_mime_types',
            ];

            // Check if we have a direct mapping
            if (isset($configMap[$key])) {
                return config($configMap[$key], $default);
            }

            // Try to find in media config with the key directly
            if (Str::startsWith($key, 'media_')) {
                $configKey = 'media.' . Str::after($key, 'media_');
                $value = config($configKey);
                if ($value !== null) {
                    return $value;
                }
            }

            // Try the key as-is in media config
            $value = config('media.' . $key);
            if ($value !== null) {
                return $value;
            }

            return $default;
        });
    }
}

if (!function_exists('clear_media_settings_cache')) {
    /**
     * Clear all cached media settings.
     *
     * Call this after updating settings in the database.
     *
     * @param string|null $key Specific key to clear, or null to clear all
     * @return void
     */
    function clear_media_settings_cache(?string $key = null): void
    {
        if ($key) {
            Cache::forget('filament-media.setting.' . $key);
        } else {
            // Clear all known setting keys
            $keys = [
                'media_driver',
                'media_max_file_size',
                'max_upload_filesize',
                'media_chunk_enabled',
                'media_chunk_size',
                'media_chunk_max_file_size',
                'media_watermark_enabled',
                'media_watermark_source',
                'media_watermark_position',
                'media_watermark_size',
                'media_watermark_opacity',
                'media_sizes',
                'media_allowed_mime_types',
                'media_image_processing_library',
                'media_generate_thumbnails_enabled',
            ];

            foreach ($keys as $k) {
                Cache::forget('filament-media.setting.' . $k);
            }
        }
    }
}

if (!function_exists('clean')) {
    /**
     * Clean/sanitize content to prevent XSS attacks.
     *
     * @param mixed $content The content to clean
     * @return mixed
     */
    function clean(mixed $content): mixed
    {
        if (is_null($content)) {
            return null;
        }

        if (is_array($content)) {
            return array_map('clean', $content);
        }

        if (!is_string($content)) {
            return $content;
        }

        // Remove null bytes
        $content = str_replace(chr(0), '', $content);

        // Convert special characters to HTML entities
        $content = htmlspecialchars($content, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);

        return $content;
    }
}

if (!function_exists('do_action')) {
    /**
     * Execute callbacks attached to a hook.
     *
     * This is a simplified hook system for extensibility.
     *
     * @param string $hook The hook name
     * @param mixed ...$args Arguments to pass to callbacks
     * @return void
     */
    function do_action(string $hook, mixed ...$args): void
    {
        $hooks = app()->bound('filament-media.hooks')
            ? app('filament-media.hooks')
            : [];

        if (!isset($hooks[$hook])) {
            return;
        }

        // Sort by priority
        usort($hooks[$hook], fn($a, $b) => $a['priority'] <=> $b['priority']);

        foreach ($hooks[$hook] as $callback) {
            call_user_func_array($callback['callback'], $args);
        }
    }
}

if (!function_exists('apply_filters')) {
    /**
     * Apply filters to a value through registered callbacks.
     *
     * @param string $hook The filter hook name
     * @param mixed $value The value to filter
     * @param mixed ...$args Additional arguments to pass to callbacks
     * @return mixed The filtered value
     */
    function apply_filters(string $hook, mixed $value, mixed ...$args): mixed
    {
        $filters = app()->bound('filament-media.filters')
            ? app('filament-media.filters')
            : [];

        if (!isset($filters[$hook])) {
            return $value;
        }

        // Sort by priority
        usort($filters[$hook], fn($a, $b) => $a['priority'] <=> $b['priority']);

        foreach ($filters[$hook] as $filter) {
            $value = call_user_func_array($filter['callback'], [$value, ...$args]);
        }

        return $value;
    }
}

if (!function_exists('add_action')) {
    /**
     * Register a callback for a hook.
     *
     * @param string $hook The hook name
     * @param callable $callback The callback function
     * @param int $priority Priority (lower = earlier execution)
     * @param int $arguments Number of arguments (unused, kept for compatibility)
     * @return void
     */
    function add_action(string $hook, callable $callback, int $priority = 10, int $arguments = 1): void
    {
        $hooks = app()->bound('filament-media.hooks')
            ? app('filament-media.hooks')
            : [];

        $hooks[$hook][] = [
            'callback' => $callback,
            'priority' => $priority,
        ];

        app()->instance('filament-media.hooks', $hooks);
    }
}

if (!function_exists('add_filter')) {
    /**
     * Register a filter callback.
     *
     * @param string $hook The filter hook name
     * @param callable $callback The callback function
     * @param int $priority Priority (lower = earlier execution)
     * @return void
     */
    function add_filter(string $hook, callable $callback, int $priority = 10): void
    {
        $filters = app()->bound('filament-media.filters')
            ? app('filament-media.filters')
            : [];

        $filters[$hook][] = [
            'callback' => $callback,
            'priority' => $priority,
        ];

        app()->instance('filament-media.filters', $filters);
    }
}
