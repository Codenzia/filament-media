<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

describe('setting() function', function () {
    beforeEach(function () {
        Cache::flush();
    });

    it('returns config value for a mapped key', function () {
        Config::set('media.driver', 'local');

        $value = setting('media_driver');

        expect($value)->toBe('local');
    });

    it('returns default value when key not found', function () {
        $value = setting('totally_nonexistent_key', 'fallback');

        expect($value)->toBe('fallback');
    });

    it('reads from media config using media_ prefix stripping', function () {
        Config::set('media.watermark_custom', 'overlay.png');

        $value = setting('media_watermark_custom');

        expect($value)->toBe('overlay.png');
    });
});

describe('clear_media_settings_cache() function', function () {
    beforeEach(function () {
        Cache::flush();
    });

    it('clears cache for a specific key', function () {
        Cache::put('filament-media.setting.media_driver', 'cached_value', 300);

        clear_media_settings_cache('media_driver');

        expect(Cache::get('filament-media.setting.media_driver'))->toBeNull();
    });

    it('clears cache for all known keys when called without args', function () {
        Cache::put('filament-media.setting.media_driver', 'v1', 300);
        Cache::put('filament-media.setting.media_max_file_size', 'v2', 300);
        Cache::put('filament-media.setting.media_chunk_enabled', 'v3', 300);

        clear_media_settings_cache();

        expect(Cache::get('filament-media.setting.media_driver'))->toBeNull()
            ->and(Cache::get('filament-media.setting.media_max_file_size'))->toBeNull()
            ->and(Cache::get('filament-media.setting.media_chunk_enabled'))->toBeNull();
    });
});

describe('clean() function', function () {
    it('returns null for null input', function () {
        expect(clean(null))->toBeNull();
    });

    it('sanitizes HTML special characters', function () {
        $result = clean('<script>alert("xss")</script>');

        expect($result)->not->toContain('<script>')
            ->and($result)->toContain('&lt;script&gt;');
    });

    it('handles arrays recursively', function () {
        $input = ['<b>bold</b>', '<i>italic</i>'];
        $result = clean($input);

        expect($result)->toBeArray()
            ->and($result[0])->not->toContain('<b>')
            ->and($result[1])->not->toContain('<i>');
    });

    it('returns non-string values unchanged', function () {
        expect(clean(42))->toBe(42)
            ->and(clean(true))->toBe(true)
            ->and(clean(false))->toBe(false);
    });

    it('removes null bytes from strings', function () {
        $input = "hello\0world";
        $result = clean($input);

        expect($result)->not->toContain("\0")
            ->and($result)->toContain('hello')
            ->and($result)->toContain('world');
    });
});
