<?php

use Codenzia\FilamentMedia\Models\MediaSetting;
use Illuminate\Support\Facades\Auth;

if (! function_exists('setting')) {
    function setting($key, $default = null)
    {
        // Try to find in DB (global setting or user setting?)
        // The original CMS probably had a robust settings manager.
        // For this package, we can try to look into config or just return default.
        
        // Check config first if mapped?
        // But keys like 'media_driver' are not directly in config root usually.
        
        return $default;
    }
}

if (! function_exists('clean')) {
    function clean($content)
    {
        return $content;
    }
}

if (! function_exists('do_action')) {
    function do_action($hook, ...$args)
    {
        return;
    }
}

if (! function_exists('apply_filters')) {
    function apply_filters($hook, $value, ...$args)
    {
        return $value;
    }
}

if (! function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $arguments = 1)
    {
        return;
    }
}
