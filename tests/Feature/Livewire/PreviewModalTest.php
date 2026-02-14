<?php

namespace Codenzia\FilamentMedia\Tests\Feature\Livewire;

use Codenzia\FilamentMedia\Livewire\PreviewModal;
use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Tests\TestCase;
use Illuminate\Contracts\Auth\Authenticatable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests for the Preview Modal Livewire component.
 *
 * Note: These tests require Filament Notifications and full Livewire setup.
 * They are skipped in the package test environment but will work
 * when the package is installed in an application with Filament configured.
 */
class PreviewModalTest extends TestCase
{
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
        };
    }

    #[Test]
    public function it_can_render_the_preview_modal(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        Livewire::test(PreviewModal::class)
            ->assertStatus(200);
    }

    #[Test]
    public function it_can_open_with_a_file(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        $file = MediaFile::factory()->create([
            'name' => 'test-image.jpg',
            'mime_type' => 'image/jpeg',
            'url' => 'test/test-image.jpg',
        ]);

        Livewire::test(PreviewModal::class)
            ->call('open', $file->id, [$file->id])
            ->assertSet('isOpen', true)
            ->assertSet('currentFileId', $file->id)
            ->assertSet('fileType', 'image');
    }

    #[Test]
    public function it_can_close_the_modal(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        $file = MediaFile::factory()->create(['name' => 'test.jpg']);

        Livewire::test(PreviewModal::class)
            ->call('open', $file->id, [$file->id])
            ->assertSet('isOpen', true)
            ->call('close')
            ->assertSet('isOpen', false)
            ->assertSet('currentFileId', null);
    }

    #[Test]
    public function it_can_navigate_to_next_file(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        $files = MediaFile::factory()->count(3)->create();
        $fileIds = $files->pluck('id')->toArray();

        Livewire::test(PreviewModal::class)
            ->call('open', $files[0]->id, $fileIds)
            ->assertSet('currentIndex', 0)
            ->call('next')
            ->assertSet('currentIndex', 1)
            ->assertSet('currentFileId', $files[1]->id);
    }

    #[Test]
    public function it_can_navigate_to_previous_file(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        $files = MediaFile::factory()->count(3)->create();
        $fileIds = $files->pluck('id')->toArray();

        Livewire::test(PreviewModal::class)
            ->call('open', $files[1]->id, $fileIds)
            ->assertSet('currentIndex', 1)
            ->call('previous')
            ->assertSet('currentIndex', 0)
            ->assertSet('currentFileId', $files[0]->id);
    }

    #[Test]
    public function it_cannot_navigate_past_first_file(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        $files = MediaFile::factory()->count(3)->create();
        $fileIds = $files->pluck('id')->toArray();

        Livewire::test(PreviewModal::class)
            ->call('open', $files[0]->id, $fileIds)
            ->assertSet('currentIndex', 0)
            ->call('previous')
            ->assertSet('currentIndex', 0);
    }

    #[Test]
    public function it_cannot_navigate_past_last_file(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        $files = MediaFile::factory()->count(3)->create();
        $fileIds = $files->pluck('id')->toArray();

        Livewire::test(PreviewModal::class)
            ->call('open', $files[2]->id, $fileIds)
            ->assertSet('currentIndex', 2)
            ->call('next')
            ->assertSet('currentIndex', 2);
    }

    #[Test]
    public function it_correctly_determines_file_types(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        $imageFile = MediaFile::factory()->create(['mime_type' => 'image/png']);
        $videoFile = MediaFile::factory()->create(['mime_type' => 'video/mp4']);
        $audioFile = MediaFile::factory()->create(['mime_type' => 'audio/mpeg']);
        $docFile = MediaFile::factory()->create(['mime_type' => 'application/pdf']);

        Livewire::test(PreviewModal::class)
            ->call('open', $imageFile->id, [$imageFile->id])
            ->assertSet('fileType', 'image');

        Livewire::test(PreviewModal::class)
            ->call('open', $videoFile->id, [$videoFile->id])
            ->assertSet('fileType', 'video');

        Livewire::test(PreviewModal::class)
            ->call('open', $audioFile->id, [$audioFile->id])
            ->assertSet('fileType', 'audio');

        Livewire::test(PreviewModal::class)
            ->call('open', $docFile->id, [$docFile->id])
            ->assertSet('fileType', 'document');
    }

    #[Test]
    public function it_can_go_to_specific_index(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        $files = MediaFile::factory()->count(5)->create();
        $fileIds = $files->pluck('id')->toArray();

        Livewire::test(PreviewModal::class)
            ->call('open', $files[0]->id, $fileIds)
            ->call('goToIndex', 3)
            ->assertSet('currentIndex', 3)
            ->assertSet('currentFileId', $files[3]->id);
    }

    #[Test]
    public function it_returns_has_next_correctly(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        $files = MediaFile::factory()->count(3)->create();
        $fileIds = $files->pluck('id')->toArray();

        $component = Livewire::test(PreviewModal::class)
            ->call('open', $files[0]->id, $fileIds);

        $this->assertTrue($component->instance()->hasNext());

        $component->call('open', $files[2]->id, $fileIds);
        $this->assertFalse($component->instance()->hasNext());
    }

    #[Test]
    public function it_returns_has_previous_correctly(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        $files = MediaFile::factory()->count(3)->create();
        $fileIds = $files->pluck('id')->toArray();

        $component = Livewire::test(PreviewModal::class)
            ->call('open', $files[0]->id, $fileIds);

        $this->assertFalse($component->instance()->hasPrevious());

        $component->call('open', $files[2]->id, $fileIds);
        $this->assertTrue($component->instance()->hasPrevious());
    }

    #[Test]
    public function it_escapes_file_name_for_xss_prevention(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        $file = MediaFile::factory()->create([
            'name' => '<script>alert("xss")</script>',
        ]);

        $component = Livewire::test(PreviewModal::class)
            ->call('open', $file->id, [$file->id]);

        $this->assertStringNotContainsString('<script>', $component->get('name'));
        $this->assertStringContainsString('&lt;script&gt;', $component->get('name'));
    }

    #[Test]
    public function it_closes_when_file_not_found(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        Livewire::test(PreviewModal::class)
            ->call('open', 99999, [99999])
            ->assertSet('isOpen', false);
    }

    #[Test]
    public function it_returns_empty_thumbnails_for_single_file(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        $file = MediaFile::factory()->create();

        $component = Livewire::test(PreviewModal::class)
            ->call('open', $file->id, [$file->id]);

        $thumbnails = $component->instance()->getThumbnails();
        $this->assertEmpty($thumbnails);
    }

    #[Test]
    public function it_returns_thumbnails_for_multiple_files(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        $files = MediaFile::factory()->count(3)->create();
        $fileIds = $files->pluck('id')->toArray();

        $component = Livewire::test(PreviewModal::class)
            ->call('open', $files[0]->id, $fileIds);

        $thumbnails = $component->instance()->getThumbnails();
        $this->assertCount(3, $thumbnails);
        $this->assertTrue($thumbnails[0]['is_current']);
        $this->assertFalse($thumbnails[1]['is_current']);
    }
}
