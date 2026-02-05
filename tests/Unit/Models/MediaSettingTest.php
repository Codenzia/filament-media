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
