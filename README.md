# Filament Media Manager

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codenzia/filament-media.svg?style=flat-square)](https://packagist.org/packages/codenzia/filament-media)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/codenzia/filament-media/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/codenzia/filament-media/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/codenzia/filament-media.svg?style=flat-square)](https://packagist.org/packages/codenzia/filament-media)

A full-featured Digital Asset Management plugin for Filament v4. Upload, organize, tag, version, and serve media files across local and cloud storage — with a modern UI, fine-grained access control, and a developer-friendly service architecture.

## Features

**File Management** — Drag-and-drop uploads with progress tracking, chunked uploads for large files, upload from URL (with SSRF protection), multi-file selection, batch operations, copy/move/rename, alt text, automatic thumbnails with optional watermarks.

**Folders** — Nested folder structure with unlimited depth, color-coded folders, drag-and-drop organization, automatic folder resolution from file paths.

**Tags & Collections** — Tag files and folders, organize into named collections, filter and search by tags, bulk tagging, popular tags with usage counts.

**Custom Metadata** — Define custom fields (text, number, date, select, boolean, URL), attach to files, search and filter by metadata, auto-extract EXIF data.

**Search** — Database search out of the box, optional Laravel Scout integration, search by name, tags, metadata, file type, and date range.

**Versioning** — Upload new versions, view history with changelogs, revert to any previous version, configurable retention with auto-prune.

**Export & Import** — Export as ZIP with metadata manifest, import from ZIP or local folder with automatic metadata restoration.

**Organization** — Favorites, recent items, type filters (image, video, document, audio, archive), sort by name/date/size, grid and list views, breadcrumb navigation.

**Trash & Recovery** — Soft delete with trash folder, restore, permanent delete.

**Preview** — Full-screen gallery modal with version history, image/video/audio preview, document preview (PDF, Office via Google/Microsoft viewers), keyboard navigation.

**UI** — Responsive design, dark mode, configurable theme colors, context menu, details panel, drag-and-drop between folders, multi-select with Ctrl/Cmd and Shift.

**Visibility & Access Control** — Per-file public/private visibility, HMAC-SHA256 hash verification for private URLs, custom authorization callbacks, automatic file movement between storage disks, per-user media scoping.

**Cloud Storage** — Local, Amazon S3, Cloudflare R2, DigitalOcean Spaces, Wasabi, Backblaze B2, BunnyCDN.

**Developer Tools** — 15 singleton services with DI, 16 Laravel events, `MediaFileUpload` and `MediaPickerField` form components, `MediaFileGrid` / `MediaFileList` / `MediaFiles` embeddable Livewire components, `FilesUploadWidget`, `HasMediaFiles` and `InteractsWithMediaCollections` traits, `MediaAdder` fluent builder, typed exceptions, query scopes, per-panel page visibility, configurable navigation.

## Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| `Arrow Keys` | Navigate between items |
| `Enter` | Open folder or preview file |
| `Space` | Toggle selection |
| `Ctrl/Cmd+A` | Select all |
| `Delete` | Move to trash |
| `F2` | Rename |
| `Escape` | Clear selection / Close preview |
| `Arrow Left/Right` | Previous/next in preview |

## Requirements

- PHP 8.2+
- Laravel 11+
- Filament 4.0+
- GD or Imagick PHP extension (for thumbnails)
- ZIP PHP extension (for export/import and multi-file download)

## Installation

```bash
composer require codenzia/filament-media
```

Publish and run migrations:

```bash
php artisan vendor:publish --tag="filament-media-migrations"
php artisan migrate
```

Publish the config:

```bash
php artisan vendor:publish --tag="filament-media-config"
```

Optionally publish views:

```bash
php artisan vendor:publish --tag="filament-media-views"
```

## Setup

### Register the Plugin

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

Control which pages are registered per panel:

```php
// Admin panel — full access
FilamentMediaPlugin::make(),

// User dashboard — picker only, no standalone pages
FilamentMediaPlugin::make()
    ->showMediaManager(false)
    ->showSettings(false),
```

### Storage Link

For local storage:

```bash
php artisan storage:link
```

### Custom Theme (Tailwind v4)

If your panel uses a custom theme (`->viteTheme()`), add these `@source` directives to your theme CSS so Tailwind discovers the package's utility classes:

```css
@source '../../../../vendor/codenzia/filament-media/resources/views/**/*.blade.php';
@source '../../../../vendor/codenzia/filament-media/src/**/*.php';
```

Then rebuild: `npm run build`

> This is only needed with custom themes. Filament's default theme works without changes.

## Configuration

The config file (`config/media.php`) provides full control over all options.

### Feature Flags

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

### Navigation

```php
'navigation' => [
    'media' => [
        'label' => null,                // null = use translation key
        'icon' => 'heroicon-o-photo',
        'group' => null,
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
],
```

### Theme Colors

Colors are injected as CSS custom properties (`--fm-*`) with separate light and dark mode values:

```php
'theme' => [
    'light' => [
        'primary' => '#6366f1',
        'surface' => '#ffffff',
        'border' => '#e5e7eb',
        'text' => '#111827',
        // ... see config for all options
    ],
    'dark' => [
        'primary' => '#818cf8',
        'surface' => '#111827',
        // ...
    ],
],
```

### Upload Limits

```php
'max_file_size' => 10 * 1024 * 1024, // 10MB
'allowed_mime_types' => 'jpg,jpeg,png,gif,pdf,doc,docx,...',
'allowed_download_domains' => [], // empty = all domains allowed for URL uploads
```

### Thumbnails & Watermarks

```php
'sizes' => ['thumb' => '150x150'],
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

```php
'private_files' => [
    'enabled' => true,
    'signed_url_expiry' => 30, // minutes (cloud temporary URLs)
    'private_disk' => 'local', // storage disk for private files
],
```

### Search & Versioning

```php
'search' => ['driver' => 'database', 'min_query_length' => 2], // or 'scout'
'versioning' => ['max_versions' => 10, 'auto_prune' => true],
```

## Usage

### Media Manager Page

Available at `/admin/media` (or your panel prefix + `/media`).

### Programmatic Access

All operations use dedicated service classes:

```php
use Codenzia\FilamentMedia\Services\UploadService;
use Codenzia\FilamentMedia\Services\FileOperationService;
use Codenzia\FilamentMedia\Services\MediaUrlService;
use Codenzia\FilamentMedia\Services\TagService;
use Codenzia\FilamentMedia\Exceptions\MediaUploadException;

// Upload a file
try {
    $file = app(UploadService::class)->handleUpload($uploadedFile, $folderId);
} catch (MediaUploadException $e) {
    // Handle: invalidFileType, fileTooLarge, unableToWrite, etc.
}

// Get URL, copy, tag
$url = app(MediaUrlService::class)->url($file->url);
$copy = app(FileOperationService::class)->copyFile($file, $targetFolderId);
app(TagService::class)->attachTags($file, ['nature', 'landscape']);
```

Upload methods return `MediaFile` directly and throw `MediaUploadException` on failure.

### File Visibility & Access Control

Files have a `visibility` attribute — `public` (default) or `private`. Public files are served via direct storage URL. Private files are served through an authenticated controller with HMAC-SHA256 hash verification.

#### Changing Visibility

```php
$fileOps = app(FileOperationService::class);
$fileOps->changeVisibility($file, 'private'); // moves to private disk
$fileOps->changeVisibility($file, 'public');  // moves back
```

On local storage, files and thumbnails are physically moved between disks.

#### How Private URLs Work

Public files: `/storage/photos/image.jpg`
Private files: `/media/private/{hash}/{id}`

The hash is an HMAC-SHA256 of the file ID, keyed to `APP_KEY`. URLs cannot be guessed or enumerated without the secret key. The controller verifies authentication and authorization before streaming. Append `?download=1` to force download.

#### Custom Authorization

```php
use Codenzia\FilamentMedia\FilamentMedia;
use Codenzia\FilamentMedia\Models\MediaFile;

// In a service provider's boot() method:
app(FilamentMedia::class)->authorizeFileAccessUsing(function (MediaFile $file, $user) {
    return $user && $file->user_id === $user->id;
});
```

The callback receives the `MediaFile` and the authenticated user (or `null`). Return `true` to allow, `false` to deny. Public files bypass the callback.

#### Per-User Media Scoping

Control which files each user sees in the Media page:

```php
app(FilamentMedia::class)->scopeMediaQueryUsing(function ($query, $user) {
    $query->where('media_files.created_by_user_id', $user->id);
});
```

The callback applies a global scope on `MediaFile` and `MediaFolder` queries across all views (media, trash, recent, favorites, collections). Not invoked when no user is authenticated.

> `scopeMediaQueryUsing()` controls query-level filtering (what appears in the UI), while `authorizeFileAccessUsing()` controls file-level download authorization. Use both together for complete access control.

### Artisan Commands

```bash
php artisan media:cleanup              # Remove DB entries for missing files
php artisan media:cleanup --dry-run    # Preview changes
php artisan media:cleanup --force      # Skip confirmation
```

## Form Components

### MediaFileUpload

Pre-configured `FileUpload` that reads settings from `config/media.php`:

```php
use Codenzia\FilamentMedia\Forms\MediaFileUpload;

MediaFileUpload::make(),              // inherits all config settings
MediaFileUpload::make('avatars'),     // upload to specific directory
```

Automatically resolves MIME types, respects max file size and server limits, uses the configured storage disk, and preserves original filenames.

### MediaPickerField

File picker for selecting existing media:

```php
use Codenzia\FilamentMedia\Forms\MediaPickerField;

MediaPickerField::make('featured_image')
    ->imageOnly()
    ->required(),

MediaPickerField::make('attachments')
    ->multiple()
    ->maxFiles(10),

MediaPickerField::make('contracts')
    ->documentOnly()
    ->directory('contracts')
    ->collection('legal'),
```

#### Direct Upload

By default, the field shows a single "Browse Media" button that opens the full media library picker. Enable `directUpload()` to add a quick "Upload File" option alongside it — the button becomes a dropdown with both choices:

```php
MediaPickerField::make('featured_image')
    ->imageOnly()
    ->directUpload(),
```

The upload zone supports drag-and-drop and uses the same upload endpoint as the media library. Uploaded files are saved to the root folder and the field state is updated automatically.

To enable direct upload globally for all `MediaPickerField` instances, set the config default:

```php
// config/media.php
'picker' => [
    'direct_upload' => true,
],
```

You can still override the global default per-field:

```php
// Disable direct upload for a specific field even when the global default is true
MediaPickerField::make('logo')->directUpload(false),
```

#### Per-Field File Type Control

By default, uploads are validated against the global `allowed_mime_types` in `config/media.php`. Two methods let you customize this per field:

```php
// Add extra extensions to the global list for this field only
// (e.g., .ico is not in the global list, but favicons need it)
MediaPickerField::make('favicon')
    ->imageOnly()
    ->includeFileTypes(['ico']),

// Restrict to ONLY these extensions, ignoring the global config entirely
MediaPickerField::make('contract')
    ->allowedFileTypesOnly(['pdf', 'docx']),
```

Both methods enforce validation on both client-side (browser) and server-side (upload endpoint). Server-side overrides are protected with HMAC-SHA256 signatures to prevent tampering.

#### Display Styles

The field supports five visual styles via `displayStyle()`:

```php
// Compact (default): Text links for browse/upload with chip-style file list
MediaPickerField::make('document')
    ->documentOnly()
    ->displayStyle('compact'),

// Dropdown: Button with dropdown menu for browse/upload options
MediaPickerField::make('document')
    ->documentOnly()
    ->directUpload()
    ->displayStyle('dropdown'),

// Thumbnail: Visual preview card — click to browse, hover for actions, drag & drop
MediaPickerField::make('featured_image')
    ->imageOnly()
    ->displayStyle('thumbnail'),

// Integrated Links: Thumbnail preview + text links below, drag & drop
MediaPickerField::make('avatar')
    ->imageOnly()
    ->displayStyle('integratedLinks'),

// Integrated Dropdown: Thumbnail preview + dropdown button below, drag & drop
MediaPickerField::make('logo')
    ->imageOnly()
    ->directUpload()
    ->displayStyle('integratedDropdown'),
```

| Style | Best for | Drag & Drop | Description |
|-------|----------|:-----------:|-------------|
| `compact` | Documents, mixed files | No | Text links + chip list with small icons |
| `dropdown` | Documents, mixed files | No | Dropdown button + chip list with small icons |
| `thumbnail` | Images, visual content | Yes | Large preview card, hover overlay with change/remove actions |
| `integratedLinks` | Images + text links | Yes | Thumbnail preview area with text links below |
| `integratedDropdown` | Images + dropdown button | Yes | Thumbnail preview area with dropdown button below |

To set a global default for all fields:

```php
// config/media.php
'picker' => [
    'display_style' => 'integratedLinks',
],
```

Per-field values always override the config default.

#### Preview Size

Control the dimensions of the thumbnail preview container (used by `thumbnail`, `integratedLinks`, and `integratedDropdown` styles). The image itself always maintains its natural aspect ratio via `object-contain`.

```php
// Square 256px (aspect-square kept when only width is set)
MediaPickerField::make('logo')
    ->displayStyle('integratedDropdown')
    ->previewWidth('16rem'),

// Rectangle 256x128px (aspect-square removed when height is set)
MediaPickerField::make('banner')
    ->displayStyle('integratedDropdown')
    ->previewWidth('16rem')
    ->previewHeight('8rem'),

// Only change height, keep default width
MediaPickerField::make('icon')
    ->displayStyle('thumbnail')
    ->previewHeight('6rem'),
```

Default: `12rem` width with `aspect-square` (192x192px). Any CSS length value works (`rem`, `px`, `%`, etc.). Global defaults can be set in config:

```php
// config/media.php
'picker' => [
    'preview_width' => '12rem',    // CSS value, e.g. '12rem', '256px'
    'preview_height' => null,       // null = aspect-square, or e.g. '8rem'
],
```

#### Chip Size

Control the size of the file chips used in `compact` and `dropdown` display styles. This affects the thumbnail size, icon size, and text size within each chip.

```php
MediaPickerField::make('avatar')
    ->displayStyle('dropdown')
    ->chipSize('lg'),    // 64px thumbnails, 16px text

MediaPickerField::make('documents')
    ->displayStyle('compact')
    ->chipSize('xs'),    // 20px thumbnails, 12px text
```

Available sizes:

| Size | Thumbnail | Text | Description |
|------|-----------|------|-------------|
| `xs` | 20px | 12px | Tiny — minimal footprint |
| `sm` | 32px | 14px | Small — default |
| `md` | 48px | 14px | Medium — easier to see previews |
| `lg` | 64px | 16px | Large — prominent file display |
| `xl` | 80px | 18px | Extra large — visual emphasis |
| `2xl` | 96px | 20px | Huge — maximum preview size |

Global default can be set in config:

```php
// config/media.php
'picker' => [
    'chip_size' => 'sm',    // 'xs', 'sm', 'md', 'lg', 'xl', '2xl'
],
```

#### Lightbox Size

Control the maximum dimensions of the full-screen image preview (lightbox) that appears when clicking a thumbnail. By default, the image fills the available viewport.

```php
// Constrain the lightbox to a smaller area
MediaPickerField::make('avatar')
    ->displayStyle('thumbnail')
    ->lightboxMaxWidth('600px')
    ->lightboxMaxHeight('400px'),
```

Global defaults can be set in config:

```php
// config/media.php
'picker' => [
    'lightbox_max_width' => null,     // null = full viewport, or e.g. '800px', '50vw'
    'lightbox_max_height' => null,    // null = full viewport, or e.g. '600px', '80vh'
],
```

#### Lightbox Opacity

Control the backdrop opacity of the lightbox overlay. The value is a percentage from 0 (fully transparent) to 100 (fully opaque). Default: 80.

```php
// More opaque backdrop
MediaPickerField::make('avatar')
    ->displayStyle('thumbnail')
    ->lightboxOpacity(95),
```

Global default can be set in config:

```php
// config/media.php
'picker' => [
    'lightbox_opacity' => 80,  // 0 = transparent, 100 = fully opaque
],
```

| Method | Description |
|--------|-------------|
| `multiple()` | Allow selecting multiple files |
| `imageOnly()` | Restrict to images |
| `videoOnly()` | Restrict to videos |
| `documentOnly()` | Restrict to documents |
| `acceptedFileTypes(array)` | Custom MIME types for picker filtering |
| `includeFileTypes(array)` | Add extra file extensions to the global allowed list for this field |
| `allowedFileTypesOnly(array)` | Restrict uploads to ONLY these file extensions (ignores global config) |
| `maxFiles(int)` | Limit selections |
| `directory(string)` | Default upload directory |
| `collection(string)` | Auto-assign collection |
| `directUpload(bool)` | Show inline upload option alongside media browser (default: `false`, or from config) |
| `displayStyle(string)` | Visual style: `'compact'`, `'dropdown'`, `'thumbnail'`, `'integratedLinks'`, or `'integratedDropdown'` (default: `'compact'`, or from config) |
| `previewWidth(string)` | Preview container width as CSS value, e.g. `'16rem'`, `'256px'` (default: `'12rem'`, or from config) |
| `previewHeight(string)` | Preview container height as CSS value, e.g. `'8rem'`, `'128px'`. Setting height removes aspect-square (default: `null` / aspect-square, or from config) |
| `chipSize(string)` | Chip size preset: `'xs'`, `'sm'`, `'md'`, `'lg'`, `'xl'`, `'2xl'`. Controls thumbnail, icon, and text size in compact/dropdown styles (default: `'sm'`, or from config) |
| `lightboxMaxWidth(string)` | Lightbox image max width as CSS value, e.g. `'800px'`, `'50vw'` (default: `null` / full viewport, or from config) |
| `lightboxMaxHeight(string)` | Lightbox image max height as CSS value, e.g. `'600px'`, `'80vh'` (default: `null` / full viewport, or from config) |
| `lightboxOpacity(int)` | Lightbox backdrop opacity as percentage 0–100 (default: `80`, or from config) |

## Livewire Components

### MediaFileGrid

Displays a grid of media files with full context menu. The parent model must use `HasMediaFiles`:

```blade
{{-- Single model --}}
<livewire:filament-media::media-file-grid :record="$record" />
<livewire:filament-media::media-file-grid :record="$record" :deletable="true" />

{{-- Multi-model mode --}}
<livewire:filament-media::media-file-grid
    fileable-type="App\Models\Project"
    :fileable-ids="[1, 2, 3]" />
```

### MediaFileList

Same features as `MediaFileGrid` in a table/list layout:

```blade
<livewire:filament-media::media-file-list :record="$record" :deletable="true" />
```

### MediaFiles

Unified viewer with a toggle between grid and list layouts:

```blade
<livewire:filament-media::media-files :record="$record" :deletable="true" />

{{-- Disable layout toggle --}}
<livewire:filament-media::media-files :record="$record" :show-layout-toggle="false" layout="list" />
```

### Shared Props

All three components accept the same props:

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `record` | Model\|null | `null` | Parent model (single-model mode) |
| `relationship` | string | `'files'` | Relationship method name |
| `fileableType` | string\|null | `null` | Morph class (multi-model mode) |
| `fileableIds` | array | `[]` | Model IDs (multi-model mode) |
| `deletable` | bool | `false` | Enable trash/delete |
| `columns` | string | responsive grid | Tailwind grid classes |
| `emptyMessage` | string | `'No files attached'` | Empty state message |
| `contextMenu` | bool | `true` | Enable right-click menu |
| `contextMenuExclude` | array | `[]` | Menu items to hide |

**Context menu keys:** `preview`, `download`, `copy_link`, `view_parent`, `rename`, `alt_text`, `tags`, `collections`, `versions`, `metadata`, `export`, `visibility`, `favorites`, `trash`.

### FilesUploadWidget

Filament widget for adding uploads to any resource page:

```php
use Codenzia\FilamentMedia\Widgets\FilesUploadWidget;

protected function getFooterWidgets(): array
{
    return [
        FilesUploadWidget::make([
            'record' => $this->record,
            'directory' => 'project-files',
            'submitLabel' => 'Upload Files',
            'submitColor' => 'success',
            'submitAlignment' => 'center',
            'visibility' => 'public',
        ]),
    ];
}
```

## Attaching Media to Models

### Setup

```php
use Codenzia\FilamentMedia\Traits\HasMediaFiles;

class Product extends Model
{
    use HasMediaFiles;
}
```

### Uploading

```php
$product->addMedia($uploadedFile)->save();

$product->addMedia($uploadedFile)
    ->usingName('Product Photo')
    ->toCollection('gallery')
    ->save();

$product->addMediaFromUrl('https://example.com/photo.jpg')
    ->withAlt('Product hero image')
    ->toCollection('gallery')
    ->toFolder($folderId)
    ->save();

$product->addMedia('/path/to/file.jpg')
    ->usingName('Local import')
    ->save();
```

### Attaching & Detaching

```php
$product->attachMediaFile($mediaFile);
$product->attachMediaFiles($mediaFiles);
$product->attachMediaWithMeta($mediaFile, ['alt' => 'Product photo']);

$product->syncMediaFiles($mediaFiles);
$product->syncMediaByIds([1, 2, 3]);

$product->detachMediaFile($mediaFile);
$product->detachAllMediaFiles();
$product->deleteMediaFile($fileId);
```

### Querying

```php
$product->files;
$product->images;
$product->videos;
$product->documents;
$product->audio;

$product->mediaByCollection('gallery')->get();
$product->mediaByTag('featured')->get();

$url = $product->getFirstImageUrl();
$url = $product->getFirstImageUrl('/images/placeholder.jpg');
$file = $product->getFirstMediaFile();
$urls = $product->getMediaUrls('gallery');

$product->hasMedia();
$product->hasImages();
$product->clearMedia('gallery');
```

### URL Attributes on MediaFile

```php
$file->preview_url;    // Full public URL for display
$file->indirect_url;   // Controller-routed URL with hash verification
$file->url;            // Raw relative storage path
```

### Physical File Deletion

```php
$file->deleteWithFile();                                  // deletes file + soft-deletes record
$file->deleteWithFile('Deleted!', 'Failed to delete');    // with Filament notifications
$product->deleteMediaFile($fileId);                       // verifies ownership first
```

## Named Media Collections

For models that need structured, constrained media:

```php
use Codenzia\FilamentMedia\Traits\HasMediaFiles;
use Codenzia\FilamentMedia\Traits\InteractsWithMediaCollections;

class User extends Model
{
    use HasMediaFiles, InteractsWithMediaCollections;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->acceptsMimeTypes(['image/*'])
            ->useFallbackUrl('/images/default-avatar.png');

        $this->addMediaCollection('gallery')
            ->onlyKeepLatest(20);

        $this->addMediaCollection('documents')
            ->acceptsMimeTypes(['application/pdf', 'application/msword']);
    }
}
```

| Method | Description |
|--------|-------------|
| `singleFile()` | One file only — new uploads auto-detach the previous |
| `onlyKeepLatest(int)` | Keep at most N files, auto-prune oldest |
| `acceptsMimeTypes(array)` | Restrict MIME types (supports `image/*` wildcards) |
| `useFallbackUrl(string)` | URL returned when collection is empty |

```php
$user->addMedia($file)->toCollection('avatar')->save();
$avatarUrl = $user->getFirstCollectionUrl('avatar');
$user->validateCollectionMimeType('avatar', 'image/jpeg'); // true
```

Collections build on the tag system (`MediaTag` with `type='collection'`). No additional migrations needed.

## Error Handling

```php
use Codenzia\FilamentMedia\Exceptions\MediaUploadException;

try {
    $file = $product->addMedia($uploadedFile)->save();
} catch (MediaUploadException $e) {
    logger()->error('Upload failed: ' . $e->getMessage());
}
```

| Method | When Thrown |
|--------|------------|
| `invalidFileType()` | MIME type not in allowed list |
| `fileTooLarge(string $size)` | Exceeds configured max size |
| `unableToWrite(string $folder)` | Storage write failed |
| `networkError(string $url)` | URL download failed |
| `ssrfBlocked(string $message)` | URL targets internal network |
| `invalidUrl()` | Malformed or empty URL |
| `invalidPath()` | Local file path doesn't exist |
| `noFileDetected()` | Could not detect file type |
| `tempFileError()` | Temp file creation failed |

## Automatic Folder Resolution

When creating a `MediaFile` without an explicit `folder_id`, the folder is resolved from the `url` path:

```php
// Creates "Avatars" folder automatically
MediaFile::create([
    'url' => 'avatars/photo.jpg',
    'name' => 'Profile Photo',
    'mime_type' => 'image/jpeg',
    'size' => 12345,
    'visibility' => 'public',
    'user_id' => $user->id,
]);

// Nested: creates Products > Gallery
MediaFile::create(['url' => 'products/gallery/photo.jpg', ...]);

// Explicit folder_id skips auto-resolution
MediaFile::create(['url' => 'avatars/photo.jpg', 'folder_id' => $id, ...]);
```

Auto-resolution only runs when `folder_id` is `0` or not set. Uses `firstOrCreate()` internally, so concurrent uploads are safe.

## Tags & Collections

```php
$tagService = app(TagService::class);

$tagService->attachTags($file, ['nature', 'landscape']);
$tagService->syncTags($file, ['nature', 'updated']);
$tagService->detachTags($file, [$tagId]);
$popular = $tagService->getPopularTags(20);
$tagService->mergeTags([$sourceId1, $sourceId2], $targetId);

// Collections (special tags with type='collection')
$collection = $tagService->createCollection('Hero Banners', 'Homepage banners');
$tagService->addToCollection($collection->id, [$fileId1, $fileId2]);
$files = $tagService->getCollectionContents($collection->id);

// Query scopes
$files = MediaFile::tagged([$tagId1, $tagId2])->get();
$files = MediaFile::inCollection($collectionId)->get();
```

## Custom Metadata

```php
$metadata = app(MetadataService::class);

$metadata->createField([
    'name' => 'Copyright', 'slug' => 'copyright',
    'type' => 'text', 'is_searchable' => true,
]);

$metadata->setMetadata($file, [$fieldId => '2025 Acme Inc.']);
$value = $metadata->getMetadataValue($file, 'copyright');

// Query scope
$files = MediaFile::withMetadataValue('license', 'MIT')->get();
```

## Search

```php
$search = app(SearchService::class);

$results = $search->search('annual report');
$results = $search->searchFiles('report', $folderId);
$results = $search->searchByTag('nature');
$results = $search->searchByMetadata('copyright', 'Acme');
$results = $search->advancedSearch([
    'name' => 'report', 'type' => 'document',
    'date_from' => '2025-01-01', 'date_to' => '2025-12-31',
]);
```

## File Versioning

```php
$versions = app(VersionService::class);

$version = $versions->createVersion($file, $uploadedFile, 'Updated design v2');
$history = $versions->getVersions($file);
$file = $versions->revertToVersion($file, $versionId);
$versions->deleteVersion($versionId);
$deleted = $versions->pruneOldVersions($file, keepCount: 5);
```

## Export & Import

```php
$exporter = app(ExportImportService::class);

$response = $exporter->exportFiles([$fileId1, $fileId2]);
$response = $exporter->exportFolder($folderId, includeSubfolders: true);
$response = $exporter->exportWithMetadata([$fileId1, $fileId2]);

$result = $exporter->importFromZip($uploadedZipFile, $targetFolderId);
$result = $exporter->importFromFolder('/path/to/folder', $targetFolderId);
```

Exports with metadata include a `manifest.json` preserving tags, collections, and custom metadata.

## Orphan File Management

Manage files in storage that have no database record:

```php
$scanner = app(OrphanScanService::class);

$orphans = $scanner->scan();
$imported = $scanner->import(
    paths: ['uploads/photo.jpg'],
    folderId: 0,
    userId: auth()->id(),
);
$deleted = $scanner->delete(['uploads/old-file.jpg']);
```

Also available via the **Storage Scanner** section in Media Settings.

## Events

All operations dispatch Laravel events:

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
| `MediaFolderRenaming` | `MediaFolder $folder`, `string $newName`, `bool $renameOnDisk` |
| `MediaFolderRenamed` | `MediaFolder $folder` |
| `MediaFolderDeleted` | `MediaFolder $folder` |
| `MediaFolderMoved` | `MediaFolder $folder`, `$oldParentId`, `$newParentId` |

## Permissions

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
$media = app(FilamentMedia::class);
$media->hasPermission('files.create');
$media->hasAnyPermission(['files.edit', 'files.trash']);
$media->addPermission('files.export');
```

## Security

- **Private File Access** — Authenticated controller with customizable authorization callbacks
- **HMAC-SHA256 URLs** — Private file URLs keyed to the application secret, not guessable
- **SSRF Protection** — URL downloads validated against internal networks, cloud metadata IPs, and configurable domain allowlists
- **XSS Prevention** — User content escaped via `SafeContentService`
- **File Validation** — Uploads validated for MIME type and size
- **Rate Limiting** — Private file routes throttled
- **CSRF Protection** — All Livewire actions protected

## Architecture

### Services (registered as singletons)

| Service | Responsibility |
|---------|---------------|
| `UploadService` | File uploads, validation, SSRF checks |
| `FileOperationService` | Rename, copy, move, delete, visibility changes |
| `ImageService` | Thumbnails, watermarks, image processing |
| `MediaUrlService` | URL generation, path resolution, MIME detection |
| `StorageDriverService` | Cloud disk configuration (S3, R2, DO, etc.) |
| `FavoriteService` | Favorites and recent items |
| `TagService` | Tags and collections |
| `MetadataService` | Custom metadata fields |
| `SearchService` | Full-text search (DB or Scout) |
| `VersionService` | File versioning |
| `ExportImportService` | ZIP export/import with metadata |
| `OrphanScanService` | Storage scan, orphan import/delete |
| `ThumbnailService` | Image resize and crop |

### Support Classes

| Class | Purpose |
|-------|---------|
| `MediaAdder` | Fluent builder for uploads (`->usingName()->toCollection()->save()`) |
| `MediaCollection` | Collection definition with constraints |
| `MediaHash` | HMAC-SHA256 hash generation for URL obfuscation |
| `MediaUploadException` | Typed exceptions for upload failures |

### Traits

| Trait | Purpose |
|-------|---------|
| `HasMediaFiles` | Polymorphic relationships, attach/detach/sync, fluent upload builder |
| `InteractsWithMediaCollections` | Named collections with constraints |

### Livewire Components

| Component | Purpose |
|-----------|---------|
| `Media` | Main media manager page |
| `UploadModal` | File uploads with progress tracking |
| `PreviewModal` | Gallery-style preview with version history |
| `MediaPicker` | Embeddable file browser for `MediaPickerField` |
| `MediaFileGrid` | File grid with context menu |
| `MediaFileList` | File list/table with context menu |
| `MediaFiles` | Unified viewer with grid/list toggle |

## Extending

Override any service:

```php
$this->app->singleton(TagService::class, MyCustomTagService::class);
```

Listen to events:

```php
protected $listen = [
    MediaFileUploaded::class => [
        GenerateAiDescription::class,
        SyncToExternalCdn::class,
    ],
];
```

## Testing

```bash
composer test
```

## Image Gallery Component

A standalone Alpine.js image gallery with lightbox, thumbnails, keyboard navigation, and RTL support. Works with any array of image URLs — no dependency on the media manager models.

```blade
<x-filament-media::image-gallery
    :urls="$imageUrls"
    :alt="$altText"
/>
```

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `urls` | array | `[]` | Array of image URLs |
| `alt` | string | `''` | Alt text for accessibility and lightbox title |

Features: main image display, prev/next arrows, thumbnail strip, fullscreen lightbox overlay, keyboard navigation (arrow keys + Escape), image counter badge, RTL-aware arrow direction, no-images placeholder.

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

- **Open Source** — Available under the [MIT License](LICENSE.md) for OSI-approved open source projects.
- **Commercial** — A commercial license is required for proprietary projects. Visit [codenzia.com](https://codenzia.com) for options.

See [LICENSE.md](LICENSE.md) for full details.
