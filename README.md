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
- `MediaPickerField` form component for Filament forms
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

### Artisan Commands

```bash
# Remove database entries for files that no longer exist on disk
php artisan media:cleanup
php artisan media:cleanup --dry-run  # Preview what would be deleted
php artisan media:cleanup --force    # Skip confirmation prompt
```

To import untracked files from storage into the database, use the **Orphan File Scanner** in the Media Settings page (see [Orphan File Management](#orphan-file-management)).

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
- **XSS Prevention** - User content is properly escaped via `SafeContentService`
- **File Validation** - Uploads validated for MIME type and size
- **SSRF Protection** - URL downloads validated against internal network ranges
- **Upload Limits** - Maximum 50 files per upload session
- **CSRF Protection** - All Livewire actions protected
- **URL Download Security** - Configurable domain allowlist

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
