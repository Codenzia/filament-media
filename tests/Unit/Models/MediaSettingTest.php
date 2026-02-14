<?php

use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('MediaSetting Model', function () {
    it('can be created with factory', function () {
        $setting = MediaSetting::factory()->create();

        expect($setting)->toBeInstanceOf(MediaSetting::class)
            ->and($setting->exists)->toBeTrue();
    });

    it('has fillable attributes', function () {
        $setting = MediaSetting::factory()->create([
            'key' => 'test-key',
            'value' => 'test-value',
        ]);

        expect($setting->key)->toBe('test-key')
            ->and($setting->value)->toBe('test-value');
    });

    it('casts value to json', function () {
        $value = ['option1' => true, 'option2' => 'value'];
        $setting = MediaSetting::factory()->create(['value' => $value]);

        $setting->refresh();

        expect($setting->value)->toBeArray()
            ->and($setting->value)->toBe($value);
    });

    it('belongs to media file', function () {
        $file = MediaFile::factory()->create();
        $setting = MediaSetting::factory()->create(['media_id' => $file->id]);

        expect($setting->media)->toBeInstanceOf(MediaFile::class)
            ->and($setting->media->id)->toBe($file->id);
    });
});

describe('MediaSetting Static Methods', function () {
    it('can get value by key', function () {
        MediaSetting::factory()->create([
            'key' => 'my-setting',
            'value' => 'my-value',
        ]);

        $value = MediaSetting::getValue('my-setting');

        expect($value)->toBe('my-value');
    });

    it('returns default when key not found', function () {
        $value = MediaSetting::getValue('nonexistent', null, 'default-value');

        expect($value)->toBe('default-value');
    });

    it('can get value by key and user id', function () {
        MediaSetting::factory()->create([
            'key' => 'user-setting',
            'value' => 'user-specific-value',
            'user_id' => 1,
        ]);

        MediaSetting::factory()->create([
            'key' => 'user-setting',
            'value' => 'other-user-value',
            'user_id' => 2,
        ]);

        $value = MediaSetting::getValue('user-setting', 1);

        expect($value)->toBe('user-specific-value');
    });

    it('can set value for key', function () {
        MediaSetting::setValue('new-setting', 'new-value');

        $setting = MediaSetting::where('key', 'new-setting')->first();

        expect($setting)->not->toBeNull()
            ->and($setting->value)->toBe('new-value');
    });

    it('updates existing setting when key exists', function () {
        MediaSetting::factory()->create([
            'key' => 'existing-setting',
            'value' => 'old-value',
        ]);

        MediaSetting::setValue('existing-setting', 'updated-value');

        expect(MediaSetting::where('key', 'existing-setting')->count())->toBe(1)
            ->and(MediaSetting::getValue('existing-setting'))->toBe('updated-value');
    });

    it('can set value for specific user', function () {
        MediaSetting::setValue('user-pref', 'user1-value', 1);
        MediaSetting::setValue('user-pref', 'user2-value', 2);

        expect(MediaSetting::getValue('user-pref', 1))->toBe('user1-value')
            ->and(MediaSetting::getValue('user-pref', 2))->toBe('user2-value');
    });

    it('can store array values', function () {
        $arrayValue = ['items' => [1, 2, 3], 'enabled' => true];

        MediaSetting::setValue('array-setting', $arrayValue);

        $retrieved = MediaSetting::getValue('array-setting');

        expect($retrieved)->toBeArray()
            ->and($retrieved)->toBe($arrayValue);
    });
});

describe('MediaSetting Factory States', function () {
    it('can create setting with specific key', function () {
        $setting = MediaSetting::factory()->withKey('custom-key')->create();

        expect($setting->key)->toBe('custom-key');
    });

    it('can create setting with user', function () {
        $setting = MediaSetting::factory()->withUser(5)->create();

        expect($setting->user_id)->toBe(5);
    });

    it('can create setting with media', function () {
        $file = MediaFile::factory()->create();
        $setting = MediaSetting::factory()->withMedia($file->id)->create();

        expect($setting->media_id)->toBe($file->id);
    });
});

describe('MediaSetting System Settings', function () {
    it('filters to system settings with scopeSystem', function () {
        // Create a system setting (no user_id, no media_id)
        MediaSetting::factory()->create([
            'key' => 'system-setting',
            'value' => 'system-value',
            'user_id' => null,
            'media_id' => null,
        ]);

        // Create a user setting
        MediaSetting::factory()->create([
            'key' => 'user-setting',
            'value' => 'user-value',
            'user_id' => 1,
        ]);

        // Create a media setting
        $file = MediaFile::factory()->create();
        MediaSetting::factory()->create([
            'key' => 'media-setting',
            'value' => 'media-value',
            'media_id' => $file->id,
        ]);

        $systemSettings = MediaSetting::system()->get();

        expect($systemSettings)->toHaveCount(1)
            ->and($systemSettings->first()->key)->toBe('system-setting');
    });

    it('can get system setting with getSystemSetting', function () {
        MediaSetting::factory()->create([
            'key' => 'media_driver',
            'value' => 's3',
            'user_id' => null,
            'media_id' => null,
        ]);

        $value = MediaSetting::getSystemSetting('media_driver');

        expect($value)->toBe('s3');
    });

    it('returns default when system setting not found', function () {
        $value = MediaSetting::getSystemSetting('nonexistent_setting', 'default-value');

        expect($value)->toBe('default-value');
    });

    it('can create new system setting with setSystemSetting', function () {
        MediaSetting::setSystemSetting('media_max_file_size', 10485760);

        $setting = MediaSetting::system()->where('key', 'media_max_file_size')->first();

        expect($setting)->not->toBeNull()
            ->and($setting->value)->toBe(10485760)
            ->and($setting->user_id)->toBeNull()
            ->and($setting->media_id)->toBeNull();
    });

    it('updates existing system setting with setSystemSetting', function () {
        MediaSetting::factory()->create([
            'key' => 'media_driver',
            'value' => 'public',
            'user_id' => null,
            'media_id' => null,
        ]);

        MediaSetting::setSystemSetting('media_driver', 's3');

        expect(MediaSetting::system()->where('key', 'media_driver')->count())->toBe(1)
            ->and(MediaSetting::getSystemSetting('media_driver'))->toBe('s3');
    });

    it('returns all system settings as key-value array with getAllSystemSettings', function () {
        MediaSetting::factory()->create([
            'key' => 'media_driver',
            'value' => 's3',
            'user_id' => null,
            'media_id' => null,
        ]);

        MediaSetting::factory()->create([
            'key' => 'media_max_file_size',
            'value' => 10485760,
            'user_id' => null,
            'media_id' => null,
        ]);

        // This should not be included
        MediaSetting::factory()->create([
            'key' => 'user_preference',
            'value' => 'grid',
            'user_id' => 1,
        ]);

        $settings = MediaSetting::getAllSystemSettings();

        expect($settings)->toBeArray()
            ->and($settings)->toHaveKey('media_driver')
            ->and($settings)->toHaveKey('media_max_file_size')
            ->and($settings)->not->toHaveKey('user_preference')
            ->and($settings['media_driver'])->toBe('s3')
            ->and($settings['media_max_file_size'])->toBe(10485760);
    });

    it('can batch set multiple system settings with setSystemSettings', function () {
        $settings = [
            'media_driver' => 'r2',
            'media_max_file_size' => 52428800,
            'media_generate_thumbnails_enabled' => true,
        ];

        MediaSetting::setSystemSettings($settings);

        expect(MediaSetting::getSystemSetting('media_driver'))->toBe('r2')
            ->and(MediaSetting::getSystemSetting('media_max_file_size'))->toBe(52428800)
            ->and(MediaSetting::getSystemSetting('media_generate_thumbnails_enabled'))->toBe(true);
    });
});
