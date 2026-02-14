<?php

namespace Codenzia\FilamentMedia\Tests\Feature\Livewire;

use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaFolder;
use Codenzia\FilamentMedia\Pages\Media;
use Codenzia\FilamentMedia\Tests\TestCase;
use Illuminate\Contracts\Auth\Authenticatable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests for the Media Page Livewire component.
 *
 * Note: These tests require a full Filament Panel setup to run.
 * They are skipped in the package test environment but will work
 * when the package is installed in an application with Filament configured.
 */
class MediaPageTest extends TestCase
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
    public function it_can_render_the_media_page(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        Livewire::test(Media::class)
            ->assertStatus(200);
    }

    #[Test]
    public function it_shows_empty_state_when_no_files(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        Livewire::test(Media::class)
            ->assertSee(trans('filament-media::media.no_files'));
    }

    #[Test]
    public function it_can_navigate_to_a_folder(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        $folder = MediaFolder::factory()->create([
            'name' => 'Test Folder',
            'parent_id' => 0,
        ]);

        Livewire::test(Media::class)
            ->call('navigateToFolder', $folder->id)
            ->assertSet('folderId', $folder->id);
    }

    #[Test]
    public function it_can_select_an_item(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        $file = MediaFile::factory()->create([
            'name' => 'test-file.jpg',
            'folder_id' => 0,
        ]);

        Livewire::test(Media::class)
            ->call('selectItem', ['id' => $file->id, 'is_folder' => false])
            ->assertSet('selectedItems', [['id' => $file->id, 'is_folder' => false]]);
    }

    #[Test]
    public function it_can_multi_select_items(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        $file1 = MediaFile::factory()->create(['name' => 'file1.jpg', 'folder_id' => 0]);
        $file2 = MediaFile::factory()->create(['name' => 'file2.jpg', 'folder_id' => 0]);

        Livewire::test(Media::class)
            ->call('selectItem', ['id' => $file1->id, 'is_folder' => false])
            ->call('selectItem', ['id' => $file2->id, 'is_folder' => false], true)
            ->assertCount('selectedItems', 2);
    }

    #[Test]
    public function it_can_clear_selection(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        $file = MediaFile::factory()->create(['name' => 'test-file.jpg', 'folder_id' => 0]);

        Livewire::test(Media::class)
            ->call('selectItem', ['id' => $file->id, 'is_folder' => false])
            ->call('clearSelection')
            ->assertSet('selectedItems', []);
    }

    #[Test]
    public function it_can_select_all_items(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        MediaFile::factory()->count(3)->create(['folder_id' => 0]);

        Livewire::test(Media::class)
            ->call('selectAll')
            ->assertCount('selectedItems', 3);
    }

    #[Test]
    public function it_can_change_view_type(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        Livewire::test(Media::class)
            ->assertSet('viewType', 'grid')
            ->call('setViewType', 'list')
            ->assertSet('viewType', 'list');
    }

    #[Test]
    public function it_can_change_filter(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        Livewire::test(Media::class)
            ->assertSet('filter', 'everything')
            ->call('setFilter', 'image')
            ->assertSet('filter', 'image');
    }

    #[Test]
    public function it_can_search_files(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        MediaFile::factory()->create(['name' => 'searchable-file.jpg', 'folder_id' => 0]);
        MediaFile::factory()->create(['name' => 'other-file.jpg', 'folder_id' => 0]);

        $component = Livewire::test(Media::class)
            ->set('search', 'searchable');

        $this->assertStringContainsString('searchable', $component->viewData('items')[0]['name'] ?? '');
    }

    #[Test]
    public function it_can_change_sort_order(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        Livewire::test(Media::class)
            ->assertSet('sortBy', 'created_at-desc')
            ->call('setSortBy', 'name-asc')
            ->assertSet('sortBy', 'name-asc');
    }

    #[Test]
    public function it_can_toggle_details_panel(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        Livewire::test(Media::class)
            ->assertSet('showDetailsPanel', true)
            ->call('toggleDetailsPanel')
            ->assertSet('showDetailsPanel', false);
    }

    #[Test]
    public function it_can_move_items_to_folder(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        $folder = MediaFolder::factory()->create(['name' => 'Target Folder', 'parent_id' => 0]);
        $file = MediaFile::factory()->create(['name' => 'test-file.jpg', 'folder_id' => 0]);

        Livewire::test(Media::class)
            ->call('moveItemsToFolder', [['id' => $file->id, 'is_folder' => false]], $folder->id);

        $this->assertEquals($folder->id, $file->fresh()->folder_id);
    }

    #[Test]
    public function it_shows_breadcrumbs_for_nested_folders(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        $parent = MediaFolder::factory()->create(['name' => 'Parent', 'parent_id' => 0]);
        $child = MediaFolder::factory()->create(['name' => 'Child', 'parent_id' => $parent->id]);

        $component = Livewire::test(Media::class)
            ->call('navigateToFolder', $child->id);

        $breadcrumbs = $component->viewData('breadcrumbs');
        $this->assertCount(3, $breadcrumbs);
    }

    #[Test]
    public function it_can_select_item_by_index(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        MediaFile::factory()->count(3)->create(['folder_id' => 0]);

        Livewire::test(Media::class)
            ->call('selectByIndex', 1)
            ->assertCount('selectedItems', 1);
    }

    #[Test]
    public function it_can_toggle_selection_by_index(): void
    {
        $this->markTestSkipped('Requires Filament Panel setup');

        MediaFile::factory()->count(3)->create(['folder_id' => 0]);

        Livewire::test(Media::class)
            ->call('toggleSelectionByIndex', 0)
            ->assertCount('selectedItems', 1)
            ->call('toggleSelectionByIndex', 0)
            ->assertCount('selectedItems', 0);
    }
}
