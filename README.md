# Filament Media Manager

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codenzia/filament-media.svg?style=flat-square)](https://packagist.org/packages/codenzia/filament-media)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/codenzia/filament-media/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/codenzia/filament-media/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/codenzia/filament-media.svg?style=flat-square)](https://packagist.org/packages/codenzia/filament-media)

A powerful Digital Asset Management plugin for Filament v4. Service-based architecture with tags, collections, custom metadata, full-text search, file versioning, and cloud storage support. Built with Livewire and Alpine.js for optimal performance.

## Features

### File Management
- Drag-and-drop file uploads with real-time progress tracking
- Chunked uploads for large files (configurable chunk size)
- Upload from URL with SSRF protection
- Multi-file selection and batch operations
- Download files (single file or multiple as ZIP)
- Copy, move, rename files with optional physical rename on disk
- Alt text support for images (accessibility/SEO)
- Automatic thumbnail generation with watermark support

### Folder Management
- Create, rename, and delete folders
- Nested folder structure (unlimited depth)
- Color-coded folders (10 customizable colors)
- Move folders between parent folders

### Tags & Collections
- Create and manage tags for files and folders
- Organize files into named collections
- Filter and search by tags
- Bulk tagging operations
- Popular tags with usage counts

### Custom Metadata
- Define custom metadata fields (text, number, date, select, boolean, URL)
- Attach metadata to files
- Search and filter by metadata values
- Auto-extraction from EXIF data

### Full-Text Search
- Database search (default, no extra dependencies)
- Optional Laravel Scout integration for advanced search
- Search by name, tags, metadata, file type, date range

### File Versioning
- Upload new versions of existing files
- View version history with changelogs
- Revert to any previous version
- Configurable version retention (auto-prune)

### Export & Import
- Export files as ZIP archives
- Export with metadata manifest (manifest.json preserves tags, collections, metadata)
- Import from ZIP with automatic metadata restoration
- Import from local folder

### Organization & Discovery
- Favorites system for files and folders
- Recent items tracking
- Filter by file type (images, videos, documents, audio, archives)
- Sort by name, date, or size
- Grid and list view layouts
- Breadcrumb navigation

### Trash & Recovery
- Soft delete with trash folder
- Restore items from trash
- Empty trash (permanent delete)

### Preview & Viewing
- Full-screen gallery preview modal with version history
- Image, video, and audio preview with native controls
- Document preview (PDF, Office documents via Google/Microsoft viewers)
- Keyboard navigation in gallery (arrow keys, escape)

### User Interface
- Modern, responsive design with configurable theme
- Dark mode support with separate color configuration
- Context menu (right-click actions)
- Details panel with file metadata
- Drag & drop to move items between folders
- Selection with Ctrl/Cmd and Shift keys

### File Visibility & Access Control
- Per-file visibility (public or private)
- Public files served directly via storage URL (fast, CDN-friendly)
- Private files served through authenticated controller with hash verification
- Custom authorization callback for fine-grained access control
- Automatic file movement between public and private storage disks
- Thumbnail support for both public and private files
- Change visibility from the context menu or details panel

### Storage & Cloud Support
- Local storage (public disk)
- Amazon S3
- Cloudflare R2
- DigitalOcean Spaces
- Wasabi
- Backblaze B2
- BunnyCDN

### Developer Features
- Service-based architecture with dependency injection
- 16 Laravel Events for all file lifecycle operations
- `MediaFileUpload` pre-configured FileUpload component for Filament forms
- `MediaPickerField` form component for Filament forms
- `MediaFileGrid` Livewire component for displaying file grids with full context menu
- `FilesUploadWidget` Filament widget for file uploads linked to any model
- `HasMediaFiles` trait for attaching media to any model
- Query scopes for filtering (tags, collections, metadata, type)
- Fully configurable sidebar navigation, icons, and groups
- Customizable theme colors via CSS custom properties

## Keyboard Shortcuts

### Media Browser
| Shortcut | Action |
|----------|--------|
| `Arrow Keys` | Navigate between items in grid/list |
| `Enter` | Open folder or preview file |
| `Space` | Toggle item selection |
| `Ctrl+A` / `Cmd+A` | Select all items |
| `Delete` / `Backspace` | Move selected items to trash |
| `F2` | Rename selected item |
| `Escape` | Clear selection |

### Preview Modal
| Shortcut | Action |
|----------|--------|
| `Arrow Left` | Previous file |
| `Arrow Right` | Next file |
| `Escape` | Close preview |

## Requirements

- PHP 8.2+
- Laravel 11+
- Filament 4.0+
- GD or Imagick PHP extension (for thumbnails)
- ZIP PHP extension (for export/import and multi-file download)

## Installation

Install the package via Composer:

```bash
composer require codenzia/filament-media
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="filament-media-migrations"
php artisan migrate
```

Publish the config file:

```bash
php artisan vendor:publish --tag="filament-media-config"
```

Optionally, publish the views for customization:

```bash
php artisan vendor:publish --tag="filament-media-views"
```

## Setup

### Register the Plugin

Add the plugin to your Filament panel provider:

```php
use Codenzia\FilamentMedia\FilamentMediaPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            FilamentMediaPlugin::make(),
        ]);
}
```

### Storage Link

If using local storage, ensure you have created the storage symlink:

```bash
php artisan storage:link
```

## Configuration

The published config file (`config/media.php`) contains all available options.

### Feature Flags

Enable or disable features individually:

```php
'features' => [
    'tags' => true,
    'collections' => true,
    'metadata' => true,
    'versioning' => true,
    'search' => true,
    'export_import' => true,
],
```

### Storage Driver

```php
'driver' => 'public', // 'public', 's3', 'r2', 'do_spaces', 'wasabi', 'bunnycdn', 'backblaze'
```

### Sidebar Navigation

Configure how media pages appear in the Filament sidebar:

```php
'navigation' => [
    'media' => [
        'label' => null,                // null = use translation key
        'icon' => 'heroicon-o-photo',
        'group' => null,                // null = no group, or string group name
        'sort' => 1,
        'visible' => true,
    ],
    'settings' => [
        'label' => null,
        'icon' => 'heroicon-o-cog-6-tooth',
        'group' => null,
        'sort' => 2,
        'visible' => true,
    ],
    'shared_group' => null, // e.g. 'Media' - groups all pages under one heading
],
```

### Theme Colors

Customize the UI for both light and dark mode. Colors are injected as CSS custom properties (`--fm-*`):

```php
'theme' => [
    'light' => [
        'primary' => '#6366f1',
        'primary_hover' => '#4f46e5',
        'primary_light' => '#eef2ff',
        'success' => '#22c55e',
        'danger' => '#ef4444',
        'warning' => '#f59e0b',
        'info' => '#3b82f6',
        'surface' => '#ffffff',
        'surface_alt' => '#f9fafb',
        'border' => '#e5e7eb',
        'text' => '#111827',
        'text_muted' => '#6b7280',
    ],
    'dark' => [
        'primary' => '#818cf8',
        'primary_hover' => '#6366f1',
        'primary_light' => '#1e1b4b',
        'success' => '#4ade80',
        'danger' => '#f87171',
        'warning' => '#fbbf24',
        'info' => '#60a5fa',
        'surface' => '#111827',
        'surface_alt' => '#1f2937',
        'border' => '#374151',
        'text' => '#f9fafb',
        'text_muted' => '#9ca3af',
    ],
],
```

### Upload Limits

```php
'max_file_size' => 10 * 1024 * 1024, // 10MB in bytes
'allowed_mime_types' => 'jpg,jpeg,png,gif,pdf,doc,docx,...',
'allowed_download_domains' => [], // empty = all domains allowed for URL uploads
```

### Search

```php
'search' => [
    'driver' => 'database', // 'database' or 'scout'
    'min_query_length' => 2,
],
```

### File Versioning

```php
'versioning' => [
    'max_versions' => 10,
    'auto_prune' => true,
],
```

### Thumbnails & Watermarks

```php
'sizes' => [
    'thumb' => '150x150',
],
'generate_thumbnails_enabled' => true,
'watermark' => [
    'enabled' => false,
    'source' => null,
    'size' => 10,
    'opacity' => 70,
    'position' => 'bottom-right',
],
```

### Private Files

Configure how private files are stored and served:

```php
'private_files' => [
    'enabled' => true,
    'signed_url_expiry' => 30, // minutes for cloud temporary URLs
    'private_disk' => 'local', // disk for private file storage
],
```

- `private_disk` — The Laravel filesystem disk used to store private files. Defaults to `local` (the `storage/app` directory, not publicly accessible).
- `signed_url_expiry` — When using cloud storage (S3, R2, etc.), private files are served via temporary signed URLs that expire after this many minutes.

## Usage

### Media Manager Page

Once installed, the media manager is available at `/admin/media` (or your Filament panel prefix + `/media`).

### Programmatic Access via Services

All operations use dedicated service classes, resolved via Laravel's container:

```php
use Codenzia\FilamentMedia\Services\UploadService;
use Codenzia\FilamentMedia\Services\FileOperationService;
use Codenzia\FilamentMedia\Services\MediaUrlService;
use Codenzia\FilamentMedia\Services\TagService;

// Upload a file
$result = app(UploadService::class)->handleUpload($uploadedFile, $folderId);

// Get full URL for a file
$url = app(MediaUrlService::class)->url($file->url);

// Copy a file
$copy = app(FileOperationService::class)->copyFile($file, $targetFolderId);

// Tag a file
app(TagService::class)->attachTags($file, ['nature', 'landscape']);
```

### File Visibility & Access Control

Files have a `visibility` attribute — either `public` (default) or `private`. Public files are served directly via storage URL. Private files are served through an authenticated controller that validates access before streaming the file.

#### Changing Visibility

From the UI, right-click any file and select **Change Visibility**, or use the details panel. Programmatically:

```php
use Codenzia\FilamentMedia\Services\FileOperationService;

$fileOps = app(FileOperationService::class);

// Make a file private (moves it from public to private disk)
$fileOps->changeVisibility($file, 'private');

// Make a file public again (moves it back to public disk)
$fileOps->changeVisibility($file, 'public');
```

When changing visibility on local storage, the file (and its thumbnails) are physically moved between the public and private disks.

#### How Private File URLs Work

Public files get direct storage URLs (e.g. `/storage/photos/image.jpg`). Private files get routed through an authenticated controller:

```
/media/private/{hash}/{id}
```

The hash is a SHA-1 of the file ID, providing a layer of URL obfuscation. The controller verifies authentication and authorization before streaming the file.

To force a download instead of inline display, append `?download=1` to the URL.

#### Custom Authorization

By default, any authenticated user can access private files. To customize this, register an authorization callback in a service provider:

```php
use Codenzia\FilamentMedia\FilamentMedia;
use Codenzia\FilamentMedia\Models\MediaFile;

// In a service provider's boot() method:
app(FilamentMedia::class)->authorizeFileAccessUsing(function (MediaFile $file, $user) {
    // Only the file owner can access it
    return $user && $file->user_id === $user->id;
});
```

The callback receives the `MediaFile` model and the authenticated user (or `null` for guests). Return `true` to allow access or `false` to deny. Public files always bypass the callback.

```php
// Role-based access example
app(FilamentMedia::class)->authorizeFileAccessUsing(function (MediaFile $file, $user) {
    if (! $user) {
        return false;
    }

    // Admins can access everything
    if ($user->hasRole('admin')) {
        return true;
    }

    // Regular users can only access files in their folder
    return $file->folder?->user_id === $user->id;
});
```

#### Checking Access Programmatically

```php
$media = app(FilamentMedia::class);

// Check if a user can access a file
$canAccess = $media->canAccessFile($file, $user);

// Check without a user (guest access)
$canAccess = $media->canAccessFile($file);
```

#### Query-Level Filtering (Per-User Media Scoping)

By default, the Media page shows all files to every user. To filter which files each user can see, register a query scope callback. This applies a global scope on `MediaFile` and `MediaFolder` queries, so the Media page (and all views: all media, trash, recent, favorites, collections) only returns files the user is authorized to see.

```php
use Codenzia\FilamentMedia\FilamentMedia;

// In a service provider's boot() method:
app(FilamentMedia::class)->scopeMediaQueryUsing(function ($query, $user) {
    // Only show files the user uploaded
    $query->where('media_files.created_by_user_id', $user->id);
});
```

The callback receives an Eloquent `Builder` instance and the authenticated user. Modify the query to constrain results. When no user is authenticated, the callback is not invoked. If no callback is registered, the default behavior applies (all files visible, or filtered by `user_id` if `canOnlyViewOwnMedia()` returns `true`).

The callback is also invoked for `MediaFolder` queries. You can differentiate between files and folders by checking the query's table:

```php
app(FilamentMedia::class)->scopeMediaQueryUsing(function ($query, $user) {
    $table = $query->getModel()->getTable();

    if ($table === 'media_folders') {
        // Folders: only show folders the user created
        $query->where('media_folders.user_id', $user->id);
        return;
    }

    // Files: complex relationship-based filtering
    $query->where(function ($q) use ($user) {
        $q->where('media_files.created_by_user_id', $user->id)
          ->orWhere(function ($sub) use ($user) {
              $sub->where('media_files.fileable_type', 'App\\Models\\Project')
                  ->whereIn('media_files.fileable_id', function ($projectQuery) use ($user) {
                      $projectQuery->select('id')
                          ->from('projects')
                          ->where('owner_id', $user->id);
                  });
          });
    });
});
```

> **Note:** `scopeMediaQueryUsing()` controls which files appear in the Media page (query-level filtering), while `authorizeFileAccessUsing()` controls who can download/view a specific private file (file-level authorization). For complete access control, use both together.

### Artisan Commands

```bash
# Remove database entries for files that no longer exist on disk
php artisan media:cleanup
php artisan media:cleanup --dry-run  # Preview what would be deleted
php artisan media:cleanup --force    # Skip confirmation prompt
```

To import untracked files from storage into the database, use the **Orphan File Scanner** in the Media Settings page (see [Orphan File Management](#orphan-file-management)).

## File Upload Form Field

Use `MediaFileUpload` in any Filament form to get a pre-configured `FileUpload` component that automatically reads allowed file types, max upload size, and storage disk from the plugin configuration:

```php
use Codenzia\FilamentMedia\Forms\MediaFileUpload;

// Basic usage — inherits all settings from config/media.php
MediaFileUpload::make(),

// Upload to a specific directory
MediaFileUpload::make('avatars'),
```

The component automatically:
- Resolves allowed file extensions from config into proper MIME types
- Respects admin-configured max file size and server limits (`upload_max_filesize`, `post_max_size`)
- Uses the configured storage disk (local, S3, R2, etc.)
- Enables file preview (openable) and download
- Preserves original filenames

## File Picker Form Field

Use `MediaPickerField` in any Filament form to let users select media files:

```php
use Codenzia\FilamentMedia\Forms\MediaPickerField;

// Single image selection
MediaPickerField::make('featured_image')
    ->label('Featured Image')
    ->imageOnly()
    ->required(),

// Multiple file selection
MediaPickerField::make('attachments')
    ->label('Attachments')
    ->multiple()
    ->maxFiles(10),

// Documents with directory and collection
MediaPickerField::make('contracts')
    ->label('Contract Documents')
    ->documentOnly()
    ->directory('contracts')
    ->collection('legal'),

// Videos only
MediaPickerField::make('video')
    ->label('Video File')
    ->videoOnly(),

// Custom MIME types
MediaPickerField::make('design_files')
    ->acceptedFileTypes(['image/svg+xml', 'image/webp', 'application/pdf']),
```

### Available Methods

| Method | Description |
|--------|-------------|
| `multiple(bool $multiple = true)` | Allow selecting multiple files |
| `imageOnly()` | Restrict to image files |
| `videoOnly()` | Restrict to video files |
| `documentOnly()` | Restrict to document files |
| `acceptedFileTypes(array $types)` | Set custom allowed MIME types |
| `maxFiles(int $max)` | Limit number of selected files |
| `directory(string $dir)` | Set default upload directory |
| `collection(string $name)` | Auto-assign collection to uploads |

## Media File Grid Component

A Livewire component that displays a grid of media files attached to a model, with a full-featured context menu and hover overlay. All actions (rename, tags, metadata, visibility, etc.) work out of the box — the component owns its own Filament Actions.

### Single-Model Mode

The parent model must use the `HasMediaFiles` trait. Pass the model as the `record` prop:

```blade
{{-- Basic usage --}}
<livewire:filament-media::media-file-grid :record="$record" />

{{-- With delete enabled --}}
<livewire:filament-media::media-file-grid :record="$record" :deletable="true" />

{{-- Custom relationship name (default: 'files') --}}
<livewire:filament-media::media-file-grid :record="$record" relationship="images" />

{{-- Custom grid columns --}}
<livewire:filament-media::media-file-grid :record="$record"
    columns="grid-cols-2 md:grid-cols-4 xl:grid-cols-6" />

{{-- Custom empty state message --}}
<livewire:filament-media::media-file-grid :record="$record"
    empty-message="No documents uploaded yet" />
```

### Multi-Model Mode

Query files across multiple records of the same morph type without needing a single parent model:

```blade
{{-- Show files attached to multiple projects --}}
<livewire:filament-media::media-file-grid
    fileable-type="App\Models\Project"
    :fileable-ids="[1, 2, 3]"
    :deletable="true" />
```

In multi-model mode, files are queried directly from the `media_files` table using `fileable_type` and `fileable_id`, sorted by latest first.

### Context Menu

Right-click any file to access all actions: **Preview**, **Download**, **Copy Link**, **Rename**, **Alt Text** (images), **Manage Tags**, **Add to Collection**, **Upload New Version**, **Edit Metadata**, **Export**, **Change Visibility**, **Favorites**, and **Move to Trash** (when `deletable` is true).

Feature-gated items (Tags, Collections, Versioning, Metadata, Export) respect the `config('media.features.*')` settings.

#### Excluding Items

Use `contextMenuExclude` to hide specific items:

```blade
<livewire:filament-media::media-file-grid :record="$record"
    :context-menu-exclude="['versions', 'metadata', 'export']" />
```

Available keys: `preview`, `download`, `copy_link`, `view_parent`, `rename`, `alt_text`, `tags`, `collections`, `versions`, `metadata`, `export`, `visibility`, `favorites`, `trash`.

#### Disabling the Context Menu

```blade
<livewire:filament-media::media-file-grid :record="$record" :context-menu="false" />
```

### Available Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `record` | Model\|null | `null` | Parent model (must use `HasMediaFiles` trait). Required for single-model mode |
| `relationship` | string | `'files'` | Relationship method name on the model |
| `fileableType` | string\|null | `null` | Morph class for multi-model mode (e.g. `App\Models\Project`) |
| `fileableIds` | array | `[]` | Array of model IDs for multi-model mode |
| `deletable` | bool | `false` | Show trash/delete in overlay and context menu |
| `columns` | string | `'grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4'` | Tailwind grid column classes |
| `emptyMessage` | string | `'No files attached'` | Message shown when no files are found |
| `contextMenu` | bool | `true` | Enable/disable the right-click context menu |
| `contextMenuExclude` | array | `[]` | List of menu item keys to hide |

## Files Upload Widget

A Filament widget that provides a file upload form linked to any Eloquent model via morphable relationship. Useful for adding file upload capability to resource pages, custom pages, or dashboards.

```php
use Codenzia\FilamentMedia\Widgets\FilesUploadWidget;

// In a Filament resource page or custom page
protected function getFooterWidgets(): array
{
    return [
        FilesUploadWidget::make([
            'record' => $this->record,
            'directory' => 'project-files',
        ]),
    ];
}
```

### Customizing the Submit Button

The widget supports customizable submit button label, color, and alignment:

```php
FilesUploadWidget::make([
    'record' => $this->record,
    'directory' => 'attachments',
    'submitLabel' => 'Upload Files',      // default: 'Save'
    'submitColor' => 'success',           // default: 'primary' (any Filament color)
    'submitAlignment' => 'center',        // 'start' (default), 'center', or 'end'
    'visibility' => 'public',             // default: 'private'
])
```

### Available Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `record` | Model | required | Parent model to attach uploaded files to |
| `directory` | string\|null | `null` | Upload directory name (also used as the form field name) |
| `visibility` | string | `'private'` | File visibility (`'public'` or `'private'`) |
| `submitLabel` | string | `'Save'` | Submit button label text |
| `submitColor` | string | `'primary'` | Submit button color (any Filament color: `primary`, `success`, `danger`, etc.) |
| `submitAlignment` | string | `'start'` | Submit button alignment (`'start'`, `'center'`, or `'end'`) |

## Attaching Media to Models

Use the `HasMediaFiles` trait to attach media files to any Eloquent model:

### Setup

```php
use Codenzia\FilamentMedia\Traits\HasMediaFiles;

class Product extends Model
{
    use HasMediaFiles;
}
```

### Uploading Files

```php
$product = Product::find(1);

// Upload a file and attach it
$file = $product->addMedia($uploadedFile);

// Upload to a specific collection
$file = $product->addMedia($uploadedFile, 'gallery');

// Upload from URL
$file = $product->addMediaFromUrl('https://example.com/photo.jpg', 'gallery');
```

### Attaching Existing Files

```php
// Attach a single file
$product->attachMediaFile($mediaFile);

// Attach with metadata
$product->attachMediaWithMeta($mediaFile, ['alt' => 'Product photo']);

// Attach multiple files
$product->attachMediaFiles($mediaFiles);

// Sync (replaces all existing attachments)
$product->syncMediaFiles($mediaFiles);

// Detach
$product->detachMediaFile($mediaFile);
$product->detachAllMediaFiles();

// Delete a file (verifies ownership, removes physical file + DB record)
$product->deleteMediaFile($fileId);
$product->deleteMediaFile($fileId, 'File deleted!', 'Delete failed');
```

### Querying Files

```php
// All files
$product->files;

// By type
$product->images;
$product->videos;
$product->documents;
$product->audio;

// By collection
$product->mediaByCollection('gallery')->get();

// By tag
$product->mediaByTag('featured')->get();

// First file / URL helpers
$url = $product->getFirstImageUrl();
$file = $product->getFirstMediaFile();
$urls = $product->getMediaUrls('gallery');

// Check existence
$product->hasMediaFiles();
$product->hasImages();

// Clear files
$product->clearMedia();              // all files
$product->clearMedia('gallery');     // specific collection
```

### Deleting Files with Physical Cleanup

The `MediaFile` model provides a `deleteWithFile()` method that deletes both the physical file from storage and soft-deletes the database record:

```php
// Delete file and its physical storage
$file->deleteWithFile();

// With optional Filament notifications
$file->deleteWithFile('File deleted successfully', 'Failed to delete file');
```

The `HasMediaFiles` trait wraps this in a `deleteMediaFile()` convenience method that also verifies the file belongs to the model:

```php
// Returns true on success, false if file not found or delete failed
$product->deleteMediaFile($fileId);
```

## Tags & Collections

### Managing Tags

```php
use Codenzia\FilamentMedia\Services\TagService;

$tagService = app(TagService::class);

// Create or find a tag
$tag = $tagService->findOrCreate('Nature');

// Attach tags to a file
$tagService->attachTags($file, ['nature', 'landscape', 'mountains']);

// Sync tags (replaces existing)
$tagService->syncTags($file, ['nature', 'updated']);

// Detach tags by ID
$tagService->detachTags($file, [$tagId]);

// Get popular tags
$popular = $tagService->getPopularTags(20);

// Merge tags
$tagService->mergeTags([$sourceTagId1, $sourceTagId2], $targetTagId);
```

### Collections

Collections are special tags (type = 'collection') that group related files:

```php
// Create a collection
$collection = $tagService->createCollection('Hero Banners', 'Homepage banner images');

// Add files to collection
$tagService->addToCollection($collection->id, [$fileId1, $fileId2]);

// Remove from collection
$tagService->removeFromCollection($collection->id, [$fileId1]);

// List all collections
$collections = $tagService->getCollections();

// Get files in a collection
$files = $tagService->getCollectionContents($collection->id);
```

### Query Scopes

```php
use Codenzia\FilamentMedia\Models\MediaFile;

// Files with specific tags
$files = MediaFile::tagged([$tagId1, $tagId2])->get();

// Files in a collection
$files = MediaFile::inCollection($collectionId)->get();
```

## Custom Metadata

### Defining Fields

```php
use Codenzia\FilamentMedia\Services\MetadataService;

$metadata = app(MetadataService::class);

// Create a text field
$metadata->createField([
    'name' => 'Copyright',
    'slug' => 'copyright',
    'type' => 'text',
    'is_required' => false,
    'is_searchable' => true,
    'sort_order' => 1,
]);

// Create a select field
$metadata->createField([
    'name' => 'License',
    'slug' => 'license',
    'type' => 'select',
    'options' => ['MIT', 'Apache 2.0', 'GPL', 'Proprietary'],
    'is_required' => true,
]);

// Update a field
$metadata->updateField($fieldId, ['name' => 'Photo Credit']);

// Delete a field
$metadata->deleteField($fieldId);

// List all fields
$fields = $metadata->getFields();
```

### Setting Metadata on Files

```php
// Set metadata values
$metadata->setMetadata($file, [
    $copyrightFieldId => '2025 Acme Inc.',
    $licenseFieldId => 'MIT',
]);

// Bulk set for multiple files
$metadata->bulkSetMetadata([$fileId1, $fileId2], [
    $licenseFieldId => 'Apache 2.0',
]);

// Read metadata
$allMeta = $metadata->getMetadata($file);
$value = $metadata->getMetadataValue($file, 'copyright');
```

### Query Scope

```php
$files = MediaFile::withMetadataValue('license', 'MIT')->get();
```

## Full-Text Search

### Basic Search

```php
use Codenzia\FilamentMedia\Services\SearchService;

$search = app(SearchService::class);

// Search by name
$results = $search->search('annual report');

// Search within a folder
$results = $search->searchFiles('report', $folderId);

// Search by tag
$results = $search->searchByTag('nature');

// Search by metadata
$results = $search->searchByMetadata('copyright', 'Acme');
```

### Advanced Search

```php
$results = $search->advancedSearch([
    'name' => 'report',
    'type' => 'document',
    'date_from' => '2025-01-01',
    'date_to' => '2025-12-31',
]);
```

### Laravel Scout Integration

For advanced search capabilities, install Laravel Scout and change the search driver:

```php
// config/media.php
'search' => [
    'driver' => 'scout',
],
```

Check if Scout is active:

```php
$search->isScoutEnabled(); // true if driver is 'scout' and Scout is installed
```

## File Versioning

### Uploading a New Version

```php
use Codenzia\FilamentMedia\Services\VersionService;

$versions = app(VersionService::class);

// Upload a new version (snapshots current file, replaces with new one)
$version = $versions->createVersion($file, $uploadedFile, 'Updated design v2');
```

### Version History

```php
// Get all versions for a file
$history = $versions->getVersions($file);

foreach ($history as $version) {
    echo "v{$version->version_number}: {$version->changelog} ({$version->created_at})";
}
```

### Reverting

```php
// Revert to a previous version (creates snapshot of current state first)
$file = $versions->revertToVersion($file, $versionId);
```

### Maintenance

```php
// Delete a specific version
$versions->deleteVersion($versionId);

// Prune old versions (keep most recent N)
$deleted = $versions->pruneOldVersions($file, keepCount: 5);

// Compare two versions
$diff = $versions->getVersionDiff($versionId1, $versionId2);
```

## Export & Import

### Exporting Files

```php
use Codenzia\FilamentMedia\Services\ExportImportService;

$exporter = app(ExportImportService::class);

// Export specific files as ZIP
$response = $exporter->exportFiles([$fileId1, $fileId2]);

// Export entire folder
$response = $exporter->exportFolder($folderId, includeSubfolders: true);

// Export with metadata (includes manifest.json with tags, collections, metadata)
$response = $exporter->exportWithMetadata([$fileId1, $fileId2]);
```

### Importing Files

```php
// Import from ZIP
$result = $exporter->importFromZip($uploadedZipFile, $targetFolderId);
// Returns: ['error' => false, 'imported' => 5, 'message' => '...']

// Import from local folder
$result = $exporter->importFromFolder('/path/to/folder', $targetFolderId);
```

### Manifest Format

When exporting with metadata, the ZIP includes a `manifest.json`:

```json
{
  "exported_at": "2025-01-15T10:30:00Z",
  "files": [
    {
      "path": "documents/report.pdf",
      "name": "Annual Report",
      "tags": ["reports", "2024"],
      "collection": "annual-reports",
      "metadata": {
        "author": "John Doe",
        "department": "Finance"
      }
    }
  ]
}
```

## Orphan File Management

The media manager includes tools for managing orphaned files — files that exist in storage but have no corresponding database record.

### Settings UI

Visit the **Media Settings** page and expand the **Storage Scanner** section to:
- Scan storage for untracked files
- Select and import orphaned files into the media library
- Select and delete orphaned files from storage

### Programmatic API

```php
use Codenzia\FilamentMedia\Services\OrphanScanService;

$scanner = app(OrphanScanService::class);

// Scan for orphaned files (returns Collection of file info arrays)
$orphans = $scanner->scan();

// Import orphaned files into the database
$imported = $scanner->import(
    paths: ['uploads/photo.jpg', 'uploads/doc.pdf'],
    folderId: 0,
    userId: auth()->id(),
);

// Delete orphaned files from disk
$deleted = $scanner->delete(['uploads/old-file.jpg']);
```

For removing **database entries** that point to missing files (the opposite direction), use the `media:cleanup` artisan command.

## Events

All file operations dispatch Laravel events. Listen to them in your `EventServiceProvider` or using closures:

```php
use Codenzia\FilamentMedia\Events\MediaFileUploaded;

Event::listen(MediaFileUploaded::class, function (MediaFileUploaded $event) {
    Log::info("File uploaded: {$event->file->name}");
});
```

### File Events

| Event | Properties |
|-------|------------|
| `MediaFileUploaded` | `MediaFile $file` |
| `MediaFileRenaming` | `MediaFile $file`, `string $newName`, `bool $renameOnDisk` |
| `MediaFileRenamed` | `MediaFile $file` |
| `MediaFileDeleting` | `MediaFile $file` |
| `MediaFileDeleted` | `MediaFile $file` |
| `MediaFileTrashed` | `MediaFile $file` |
| `MediaFileRestored` | `MediaFile $file` |
| `MediaFileMoved` | `MediaFile $file`, `$oldFolderId`, `$newFolderId` |
| `MediaFileCopied` | `MediaFile $newFile`, `MediaFile $originalFile` |
| `MediaFileTagged` | `MediaFile $file`, `array $tagIds` |
| `MediaFileVersionCreated` | `MediaFile $file`, `MediaFileVersion $version` |

### Folder Events

| Event | Properties |
|-------|------------|
| `MediaFolderCreated` | `MediaFolder $folder` |
| `MediaFolderRenaming` | `MediaFolder $file`, `string $newName`, `bool $renameOnDisk` |
| `MediaFolderRenamed` | `MediaFolder $folder` |
| `MediaFolderDeleted` | `MediaFolder $folder` |
| `MediaFolderMoved` | `MediaFolder $folder`, `$oldParentId`, `$newParentId` |

## Permissions

The plugin includes permission keys for integration with your authorization system:

| Permission | Description |
|------------|-------------|
| `folders.create` | Create new folders |
| `folders.edit` | Edit/rename folders |
| `folders.trash` | Move folders to trash |
| `folders.destroy` | Permanently delete folders |
| `folders.favorite` | Add folders to favorites |
| `files.create` | Upload files |
| `files.read` | View files |
| `files.edit` | Edit files (rename, alt text) |
| `files.trash` | Move files to trash |
| `files.destroy` | Permanently delete files |
| `files.favorite` | Add files to favorites |
| `settings.access` | Access settings page |

```php
use Codenzia\FilamentMedia\FilamentMedia;

$media = app(FilamentMedia::class);
$media->hasPermission('files.create');
$media->hasAnyPermission(['files.edit', 'files.trash']);
$media->addPermission('files.export');
```

## Security

- **Authorization Checks** - All actions verify user permissions
- **Private File Access Control** - Private files served through authenticated controller with customizable authorization callback
- **URL Obfuscation** - Private file URLs use SHA-1 hash verification
- **XSS Prevention** - User content is properly escaped via `SafeContentService`
- **File Validation** - Uploads validated for MIME type and size
- **SSRF Protection** - URL downloads validated against internal network ranges
- **Upload Limits** - Maximum 50 files per upload session
- **CSRF Protection** - All Livewire actions protected
- **URL Download Security** - Configurable domain allowlist
- **Rate Limiting** - Private file routes throttled to prevent abuse

## Architecture

The media manager uses a **service-based architecture**:

### Services (registered as singletons)

| Service | Responsibility |
|---------|---------------|
| `UploadService` | File uploads, validation, SSRF checks |
| `FileOperationService` | Rename, copy, move, delete operations |
| `ImageService` | Thumbnails, watermarks, image processing |
| `MediaUrlService` | URL generation, path resolution, MIME detection |
| `StorageDriverService` | Cloud disk configuration (S3, R2, DO, etc.) |
| `FavoriteService` | Favorites and recent items |
| `TagService` | Tags and collections management |
| `MetadataService` | Custom metadata fields |
| `SearchService` | Full-text search (DB or Scout) |
| `VersionService` | File versioning |
| `ExportImportService` | ZIP export/import with metadata |
| `OrphanScanService` | Storage scan, orphan import/delete |
| `ThumbnailService` | Image resize and crop |

### Design Principles
- **Service-based DI** - All operations through dedicated services, resolved via Laravel's container
- **Query scopes** - Model scopes replace the repository pattern (`MediaFile::inFolder()`, `::tagged()`, etc.)
- **Laravel Events** - 16 events for all file lifecycle operations (no custom hook system)
- **Livewire + Alpine.js** - Livewire handles data/actions, Alpine.js handles UI (uploads, drag-drop, context menu)

### Livewire Components
- `Media.php` - Main media manager page with full state management
- `UploadModal.php` - File uploads with progress tracking
- `PreviewModal.php` - Gallery-style preview with version history
- `MediaPicker.php` - Embeddable file browser used by `MediaPickerField`
- `MediaFileGrid.php` - File grid with context menu and Filament Actions for embedding in any page

## Extending

### Custom Service Bindings

Override any service via Laravel's container:

```php
// In a service provider
$this->app->singleton(TagService::class, MyCustomTagService::class);
```

### Custom Event Listeners

```php
// In EventServiceProvider
protected $listen = [
    MediaFileUploaded::class => [
        GenerateAiDescription::class,
        SyncToExternalCdn::class,
    ],
    MediaFileTagged::class => [
        UpdateSearchIndex::class,
    ],
];
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for recent changes.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Codenzia](https://github.com/Codenzia)
- [All Contributors](../../contributors)

## License

This project uses a **dual license**:

### Open Source Projects
For open source projects released under an OSI-approved license, this plugin is available under the [MIT License](LICENSE.md).

### Commercial Projects
For commercial or proprietary projects, a commercial license is required. Visit [codenzia.com](https://codenzia.com) for licensing options.

See [LICENSE.md](LICENSE.md) for full details.
