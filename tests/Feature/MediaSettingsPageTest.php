<?php

namespace Codenzia\FilamentMedia\Tests\Feature;

use Codenzia\FilamentMedia\Models\MediaSetting;
use Codenzia\FilamentMedia\Pages\MediaSettings;
use Codenzia\FilamentMedia\Tests\TestCase;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests for the Media Settings page.
 *
 * Note: These tests require a full Filament Panel setup to run.
 * They are skipped in the package test environment but will work
 * when the package is installed in an application with Filament configured.
 */
class MediaSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->createUser());
    }

    protected function createUser(): Authenticatable
    {
        return new class implements Authenticatable {
            public int $id = 1;
            public string $name = 'Test User';
            public string $email = 'test@example.com';
            public string $password = 'password';
            public ?string $remember_token = null;

            public function getAuthIdentifierName(): string
            {
                return 'id';
            }

            public function getAuthIdentifier(): mixed
            {
                return $this->id;
            }

            public function getAuthPasswordName(): string
            {
                return 'password';
            }

            public function getAuthPassword(): string
            {
                return $this->password;
            }

            public function getRememberToken(): ?string
            {
                return $this->remember_token;
            }

            public function setRememberToken($value): void
            {
                $this->remember_token = $value;
            }

            public function getRememberTokenName(): string
            {
                return 'remember_token';
            }

            public function hasRole(string $role): bool
            {
                return $role === 'super_admin';
            }
        };
    }

    #[Test]
    public function it_can_render_settings_page(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        Livewire::test(MediaSettings::class)
            ->assertStatus(200);
    }

    #[Test]
    public function it_loads_existing_settings_from_database(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        // Set up system settings in database
        MediaSetting::setSystemSetting('media_driver', 's3');
        MediaSetting::setSystemSetting('media_max_file_size', 52428800); // 50MB

        Livewire::test(MediaSettings::class)
            ->assertSet('data.storage_driver', 's3')
            ->assertSet('data.max_file_size', 50); // Converted to MB
    }

    #[Test]
    public function it_saves_storage_settings_to_database(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        Livewire::test(MediaSettings::class)
            ->set('data.storage_driver', 'r2')
            ->set('data.custom_upload_path', true)
            ->set('data.upload_path', 'custom/media')
            ->call('save');

        expect(MediaSetting::getSystemSetting('media_driver'))->toBe('r2')
            ->and(MediaSetting::getSystemSetting('media_customize_upload_path'))->toBeTrue()
            ->and(MediaSetting::getSystemSetting('media_upload_path'))->toBe('custom/media');
    }

    #[Test]
    public function it_saves_file_type_settings_to_database(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        Livewire::test(MediaSettings::class)
            ->set('data.allowed_extensions', ['jpg', 'png', 'gif', 'pdf'])
            ->set('data.max_file_size', 25)
            ->call('save');

        expect(MediaSetting::getSystemSetting('media_allowed_mime_types'))->toBe('jpg,png,gif,pdf')
            ->and(MediaSetting::getSystemSetting('media_max_file_size'))->toBe(26214400); // 25MB in bytes
    }

    #[Test]
    public function it_saves_thumbnail_sizes_in_correct_format(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        Livewire::test(MediaSettings::class)
            ->set('data.generate_thumbnails', true)
            ->set('data.thumbnail_sizes', [
                ['name' => 'thumb', 'width' => 150, 'height' => 150],
                ['name' => 'medium', 'width' => 300, 'height' => 300],
            ])
            ->call('save');

        $sizes = MediaSetting::getSystemSetting('media_sizes');

        expect($sizes)->toBeArray()
            ->and($sizes)->toHaveKey('thumb')
            ->and($sizes)->toHaveKey('medium')
            ->and($sizes['thumb'])->toBe('150x150')
            ->and($sizes['medium'])->toBe('300x300');
    }

    #[Test]
    public function it_saves_watermark_settings(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        Livewire::test(MediaSettings::class)
            ->set('data.watermark_enabled', true)
            ->set('data.watermark_position', 'center')
            ->set('data.watermark_opacity', 50)
            ->set('data.watermark_size', 15)
            ->call('save');

        expect(MediaSetting::getSystemSetting('media_watermark_enabled'))->toBeTrue()
            ->and(MediaSetting::getSystemSetting('media_watermark_position'))->toBe('center')
            ->and(MediaSetting::getSystemSetting('media_watermark_opacity'))->toBe(50)
            ->and(MediaSetting::getSystemSetting('media_watermark_size'))->toBe(15);
    }

    #[Test]
    public function it_clears_settings_cache_after_save(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        // Pre-populate cache by reading a setting
        $initialValue = setting('media_driver', 'public');

        // Save new value via settings page
        Livewire::test(MediaSettings::class)
            ->set('data.storage_driver', 'wasabi')
            ->call('save');

        // The setting() helper should return the new value (cache was cleared)
        $newValue = setting('media_driver', 'public');

        expect($newValue)->toBe('wasabi');
    }
}
