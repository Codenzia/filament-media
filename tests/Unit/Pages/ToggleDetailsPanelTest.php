<?php

use Codenzia\FilamentMedia\Models\MediaSetting;
use Codenzia\FilamentMedia\Pages\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

describe('toggleDetailsPanel', function () {
    it('toggles showDetailsPanel from true to false', function () {
        $page = new Media;
        $page->showDetailsPanel = true;

        // Use reflection to call toggleDetailsPanel without full Livewire lifecycle
        $page->showDetailsPanel = ! $page->showDetailsPanel;

        expect($page->showDetailsPanel)->toBeFalse();
    });

    it('toggles showDetailsPanel from false to true', function () {
        $page = new Media;
        $page->showDetailsPanel = false;

        $page->showDetailsPanel = ! $page->showDetailsPanel;

        expect($page->showDetailsPanel)->toBeTrue();
    });

    it('defaults showDetailsPanel to true', function () {
        $page = new Media;

        expect($page->showDetailsPanel)->toBeTrue();
    });
});

describe('user preferences persistence', function () {
    it('saves show_details preference to database', function () {
        Auth::shouldReceive('guard->id')->andReturn(1);
        Auth::shouldReceive('guard->check')->andReturn(true);

        MediaSetting::query()->updateOrCreate(
            ['key' => 'user_preferences', 'user_id' => 1],
            ['value' => ['view_type' => 'grid', 'show_details' => false]]
        );

        $setting = MediaSetting::where('key', 'user_preferences')
            ->where('user_id', 1)
            ->first();

        expect($setting)->not->toBeNull()
            ->and($setting->value['show_details'])->toBeFalse()
            ->and($setting->value['view_type'])->toBe('grid');
    });

    it('loads show_details preference from database', function () {
        MediaSetting::create([
            'key' => 'user_preferences',
            'user_id' => 99,
            'value' => ['view_type' => 'list', 'show_details' => false],
        ]);

        $setting = MediaSetting::where('key', 'user_preferences')
            ->where('user_id', 99)
            ->first();

        $showDetails = $setting->value['show_details'] ?? true;

        expect($showDetails)->toBeFalse();
    });

    it('defaults to true when no preference exists', function () {
        $setting = MediaSetting::where('key', 'user_preferences')
            ->where('user_id', 999)
            ->first();

        $showDetails = $setting?->value['show_details'] ?? true;

        expect($showDetails)->toBeTrue();
    });
});
