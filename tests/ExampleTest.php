<?php

use Codenzia\FilamentMedia\FilamentMedia;
use Codenzia\FilamentMedia\FilamentMediaServiceProvider;
use Codenzia\FilamentMedia\Facades\FilamentMedia as FilamentMediaFacade;

it('can test', function () {
    expect(true)->toBeTrue();
});

it('loads the service provider', function () {
    $providers = app()->getLoadedProviders();

    expect($providers)->toHaveKey(FilamentMediaServiceProvider::class);
});

it('registers the facade', function () {
    $facade = FilamentMediaFacade::getFacadeRoot();

    expect($facade)->toBeInstanceOf(FilamentMedia::class);
});

it('loads the config file', function () {
    $config = config('media');

    expect($config)->toBeArray()
        ->and($config)->toHaveKey('pagination')
        ->and($config)->toHaveKey('sizes')
        ->and($config)->toHaveKey('permissions');
});

it('has correct default config values', function () {
    expect(config('media.pagination.per_page'))->toBe(30)
        ->and(config('media.driver'))->toBe('public')
        ->and(config('media.max_file_size'))->toBe(10 * 1024 * 1024);
});
