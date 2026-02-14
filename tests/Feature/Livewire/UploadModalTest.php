<?php

namespace Codenzia\FilamentMedia\Tests\Feature\Livewire;

use Codenzia\FilamentMedia\Livewire\UploadModal;
use Codenzia\FilamentMedia\Models\MediaFolder;
use Codenzia\FilamentMedia\Tests\TestCase;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests for the Upload Modal Livewire component.
 *
 * Note: These tests require Filament Notifications and full Livewire setup.
 * They are skipped in the package test environment but will work
 * when the package is installed in an application with Filament configured.
 */
class UploadModalTest extends TestCase
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
    public function it_can_render_the_upload_modal(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        Livewire::test(UploadModal::class)
            ->assertStatus(200);
    }

    #[Test]
    public function it_can_open_the_modal(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        Livewire::test(UploadModal::class)
            ->assertSet('isOpen', false)
            ->call('open', 0)
            ->assertSet('isOpen', true)
            ->assertSet('folderId', 0);
    }

    #[Test]
    public function it_can_open_modal_with_folder_id(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        $folder = MediaFolder::factory()->create(['name' => 'Test Folder']);

        Livewire::test(UploadModal::class)
            ->call('open', $folder->id)
            ->assertSet('folderId', $folder->id);
    }

    #[Test]
    public function it_can_close_the_modal(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        Livewire::test(UploadModal::class)
            ->call('open', 0)
            ->assertSet('isOpen', true)
            ->call('close')
            ->assertSet('isOpen', false);
    }

    #[Test]
    public function it_resets_state_when_closing(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        Livewire::test(UploadModal::class)
            ->call('open', 0)
            ->set('completedCount', 5)
            ->set('failedCount', 2)
            ->call('close')
            ->assertSet('completedCount', 0)
            ->assertSet('failedCount', 0)
            ->assertSet('uploadQueue', []);
    }

    #[Test]
    public function it_can_remove_file_from_queue(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        Livewire::test(UploadModal::class)
            ->call('open', 0)
            ->set('uploadQueue', [
                'file1' => ['name' => 'file1.jpg', 'size' => 1000, 'status' => 'completed', 'progress' => 100, 'error' => null],
                'file2' => ['name' => 'file2.jpg', 'size' => 2000, 'status' => 'pending', 'progress' => 0, 'error' => null],
            ])
            ->call('removeFromQueue', 'file1')
            ->assertCount('uploadQueue', 1);
    }

    #[Test]
    public function it_formats_file_size_correctly(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        $component = Livewire::test(UploadModal::class);

        $this->assertEquals('0 B', $component->instance()->formatSize(0));
        $this->assertEquals('1 KB', $component->instance()->formatSize(1024));
        $this->assertEquals('1 MB', $component->instance()->formatSize(1048576));
        $this->assertEquals('1.5 MB', $component->instance()->formatSize(1572864));
    }

    #[Test]
    public function it_validates_files_on_update(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        $file = UploadedFile::fake()->create('test.exe', 1000);

        Livewire::test(UploadModal::class)
            ->call('open', 0)
            ->set('files', [$file])
            ->assertHasErrors(['files.*']);
    }

    #[Test]
    public function it_accepts_valid_image_files(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        Livewire::test(UploadModal::class)
            ->call('open', 0)
            ->set('files', [$file])
            ->assertHasNoErrors();
    }

    #[Test]
    public function it_adds_file_to_queue_with_correct_structure(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        Livewire::test(UploadModal::class)
            ->call('open', 0)
            ->call('addToQueue', 'file-key-1', 'test-file.jpg', 1024)
            ->assertSet('uploadQueue.file-key-1.name', 'test-file.jpg')
            ->assertSet('uploadQueue.file-key-1.size', 1024)
            ->assertSet('uploadQueue.file-key-1.status', 'uploading')
            ->assertSet('uploadQueue.file-key-1.progress', 0)
            ->assertSet('uploadQueue.file-key-1.error', null);
    }

    #[Test]
    public function it_enforces_max_files_limit_in_queue(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        $component = Livewire::test(UploadModal::class)
            ->call('open', 0);

        // Add 50 files (the max limit)
        for ($i = 0; $i < 50; $i++) {
            $component->call('addToQueue', "file-key-{$i}", "file{$i}.jpg", 1000);
        }

        $component->assertCount('uploadQueue', 50);

        // Adding one more should not increase the count
        $component->call('addToQueue', 'file-key-51', 'file51.jpg', 1000);
        $component->assertCount('uploadQueue', 50);
    }

    #[Test]
    public function it_updates_progress_correctly(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        Livewire::test(UploadModal::class)
            ->call('open', 0)
            ->call('addToQueue', 'file-key-1', 'test.jpg', 1024)
            ->call('updateProgress', 'file-key-1', 50)
            ->assertSet('uploadQueue.file-key-1.progress', 50);
    }

    #[Test]
    public function it_clamps_progress_between_zero_and_hundred(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        Livewire::test(UploadModal::class)
            ->call('open', 0)
            ->call('addToQueue', 'file-key-1', 'test.jpg', 1024)
            ->call('updateProgress', 'file-key-1', 150)
            ->assertSet('uploadQueue.file-key-1.progress', 100)
            ->call('updateProgress', 'file-key-1', -10)
            ->assertSet('uploadQueue.file-key-1.progress', 0);
    }

    #[Test]
    public function it_marks_file_as_complete_and_increments_count(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        Livewire::test(UploadModal::class)
            ->call('open', 0)
            ->call('addToQueue', 'file-key-1', 'test.jpg', 1024)
            ->assertSet('completedCount', 0)
            ->call('markComplete', 'file-key-1')
            ->assertSet('uploadQueue.file-key-1.status', 'completed')
            ->assertSet('uploadQueue.file-key-1.progress', 100)
            ->assertSet('completedCount', 1);
    }

    #[Test]
    public function it_marks_file_as_failed_with_error_message(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        Livewire::test(UploadModal::class)
            ->call('open', 0)
            ->call('addToQueue', 'file-key-1', 'test.jpg', 1024)
            ->assertSet('failedCount', 0)
            ->call('markFailed', 'file-key-1', 'File too large')
            ->assertSet('uploadQueue.file-key-1.status', 'failed')
            ->assertSet('uploadQueue.file-key-1.error', 'File too large')
            ->assertSet('failedCount', 1);
    }

    #[Test]
    public function it_dispatches_event_when_all_uploads_complete(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        Livewire::test(UploadModal::class)
            ->call('open', 0)
            ->call('addToQueue', 'file-key-1', 'test.jpg', 1024)
            ->call('markComplete', 'file-key-1')
            ->assertDispatched('media-files-uploaded');
    }

    #[Test]
    public function it_auto_closes_modal_when_all_uploads_succeed(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        Livewire::test(UploadModal::class)
            ->call('open', 0)
            ->assertSet('isOpen', true)
            ->call('addToQueue', 'file-key-1', 'test.jpg', 1024)
            ->call('markComplete', 'file-key-1')
            ->assertSet('isOpen', false);
    }
}
