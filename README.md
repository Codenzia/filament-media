# Filament Media Manager

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codenzia/filament-media.svg?style=flat-square)](https://packagist.org/packages/codenzia/filament-media)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/codenzia/filament-media/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/codenzia/filament-media/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/codenzia/filament-media.svg?style=flat-square)](https://packagist.org/packages/codenzia/filament-media)

A powerful, full-featured media manager plugin for Filament v4 with drag-and-drop uploads, folder organization, image editing, and cloud storage support. Built with a modern Livewire-first architecture for optimal performance and user experience.

## 🎯 Features

### File Management
- ✅ Drag-and-drop file uploads with real-time progress tracking
- ✅ Chunked uploads for large files (configurable chunk size)
- ✅ Upload from URL - download files directly from external URLs
- ✅ Multi-file selection and batch operations
- ✅ Download files (single file or multiple as ZIP)
- ✅ Copy file link to clipboard
- ✅ Rename files with optional physical file rename on disk
- ✅ Move files between folders
- ✅ Alt text support for images (accessibility/SEO)
- ✅ Automatic thumbnail generation
- ✅ Watermark support with configurable position and opacity

### Folder Management
- ✅ Create, rename, and delete folders
- ✅ Nested folder structure (unlimited depth)
- ✅ Color-coded folders (10 customizable colors)
- ✅ Move folders between parent folders
- ✅ Folder properties panel

### Organization & Discovery
- ✅ Favorites system for files and folders
- ✅ Search by filename
- ✅ Filter by file type (images, videos, documents, audio, archives)
- ✅ Sort by name, date, or size
- ✅ Grid and list view layouts
- ✅ Breadcrumb navigation

### Trash & Recovery
- ✅ Soft delete with trash folder
- ✅ Restore items from trash
- ✅ Empty trash (permanent delete)
- ✅ Orphaned file detection (files missing from disk)

### Preview & Viewing
- ✅ Full-screen gallery preview modal
- ✅ Image preview
- ✅ Video player with native controls
- ✅ Audio player with native controls
- ✅ Document preview (PDF, Office documents via Google/Microsoft viewers)
- ✅ Keyboard navigation in gallery (arrow keys, escape)
- ✅ Thumbnail strip for quick navigation

### User Interface
- ✅ Modern, responsive design
- ✅ Dark mode support
- ✅ Context menu (right-click actions)
- ✅ Details panel with file metadata
- ✅ Drag & drop to move items between folders
- ✅ Selection with Ctrl/Cmd and Shift keys

### Storage & Cloud Support
- ✅ Local storage (public disk)
- ✅ Amazon S3
- ✅ Cloudflare R2
- ✅ DigitalOcean Spaces
- ✅ Wasabi
- ✅ Backblaze B2
- ✅ Custom CDN URL support

### Admin Features
- ✅ Settings page for runtime configuration
- ✅ Permission system integration
- ✅ Artisan command to sync existing files
- ✅ Artisan command to cleanup orphaned files

### Developer Features
- ✅ Facade for programmatic access
- ✅ Hook/filter system for extensibility
- ✅ Events for file operations (uploaded, renamed, etc.)
- ✅ Configurable via config file or database settings

## ⌨️ Keyboard Shortcuts

Navigate efficiently with full keyboard support:

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
| `Home` | Jump to first item |
| `End` | Jump to last item |

### Preview Modal
| Shortcut | Action |
|----------|--------|
| `Arrow Left` | Previous file |
| `Arrow Right` | Next file |
| `Escape` | Close preview |

## 📋 Requirements

- PHP 8.2+
- Laravel 11+
- Filament 4.0+
- GD or Imagick PHP extension (for thumbnails)
- ZIP PHP extension (for multi-file download)

## 📦 Installation

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

## ⚙️ Setup

### Register the Plugin

Add the plugin to your Filament panel provider:

```php
use Codenzia\FilamentMedia\FilamentMediaPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
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

## 🔧 Configuration

The published config file (`config/media.php`) contains all available options:

```php
return [
    // Pagination
    'pagination' => [
        'per_page' => 30,
    ],

    // Thumbnail sizes (format: 'name' => 'WIDTHxHEIGHT')
    'sizes' => [
        'thumb' => '150x150',
    ],

    // Allowed file extensions
    'allowed_mime_types' => 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,mp4,mp3,...',

    // Maximum upload size (in bytes) - default 10MB
    'max_file_size' => 10 * 1024 * 1024,

    // Storage driver: 'public', 's3', or any configured disk
    'driver' => 'public',

    // Chunked upload settings
    'chunk' => [
        'enabled' => false,
        'chunk_size' => 1024 * 1024, // 1MB chunks
        'max_file_size' => 1024 * 1024, // Max file size in MB
    ],

    // Watermark settings
    'watermark' => [
        'enabled' => false,
        'source' => null, // Path to watermark image
        'size' => 10,     // Percentage of image
        'opacity' => 70,
        'position' => 'bottom-right',
    ],

    // Document preview settings
    'preview' => [
        'document' => [
            'enabled' => true,
            'default' => 'microsoft', // or 'google'
        ],
    ],

    // Folder color options
    'folder_colors' => [
        '#3498db', '#2ecc71', '#e74c3c', '#f39c12', '#9b59b6',
        '#1abc9c', '#34495e', '#e67e22', '#27ae60', '#c0392b',
    ],

    // Navigation settings
    'navigation' => [
        'icon' => 'heroicon-o-photo',
        'label' => null,
        'group' => null,
    ],

    // Thumbnail generation
    'generate_thumbnails_enabled' => true,

    // Allowed domains for URL downloads (empty = all allowed)
    'allowed_download_domains' => [],

    // Settings page
    'settings' => [
        'enabled' => true,
        'access' => 'all', // 'all', 'super_admin', or permission name
    ],

    // Permissions
    'permissions' => [
        'folders.create',
        'folders.edit',
        'folders.trash',
        'folders.destroy',
        'folders.favorite',
        'files.create',
        'files.read',
        'files.edit',
        'files.trash',
        'files.destroy',
        'files.favorite',
        'settings.access',
    ],
];
```

## 📖 Usage

### Accessing the Media Manager

Once installed, the media manager is available at `/admin/media` (or your Filament panel prefix + `/media`).

### Using the Facade

Interact with media programmatically:

```php
use Codenzia\FilamentMedia\Facades\FilamentMedia;

// Upload a file
$result = FilamentMedia::handleUpload($uploadedFile, $folderId);

// Get full URL for a file
$url = FilamentMedia::url($file->url);

// Generate thumbnails for a file
FilamentMedia::generateThumbnails($mediaFile);

// Check if file type supports thumbnails
$canGenerate = FilamentMedia::canGenerateThumbnails($mimeType);

// Check user permissions
$canUpload = FilamentMedia::hasPermission('files.create');
$canManage = FilamentMedia::hasAnyPermission(['files.edit', 'files.trash']);

// Get configuration values
$maxSize = FilamentMedia::getMaxSize();
$allowedTypes = FilamentMedia::getAllowedMimeTypes();

// Upload from URL
FilamentMedia::uploadFromUrl('https://example.com/image.jpg', $folderId);
```

### Artisan Commands

Sync existing files from storage to database:

```bash
php artisan filament-media:sync
```

Cleanup orphaned media entries:

```bash
php artisan filament-media:cleanup
```

## 🔐 Permissions

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

Configure available permissions in `config/media.php`:

```php
'permissions' => [
    'files.create',
    'files.edit',
    'files.trash',
    // Add only the permissions your users should have
],
```

## 🪝 Hooks and Filters

The plugin provides a hook/filter system for extensibility:

```php
use function add_action;
use function add_filter;

// Action: After file upload
add_action('media.file.uploaded', function ($file) {
    // Process the uploaded file
    Log::info('File uploaded: ' . $file->name);
});

// Filter: Modify allowed MIME types
add_filter('media.allowed_mime_types', function ($types) {
    $types[] = 'image/svg+xml';
    return $types;
});
```

## 🔒 Security

The plugin includes several security measures:

- **Authorization Checks** - All actions verify user permissions
- **XSS Prevention** - User content is properly escaped
- **File Validation** - Uploads validated for MIME type and size
- **Upload Limits** - Maximum 50 files per upload session
- **CSRF Protection** - All Livewire actions protected
- **URL Download Security** - Configurable domain allowlist, internal network blocking

## 🏗️ Architecture

The media manager uses a **Livewire-first architecture**:

- **Main Page** (`Media.php`) - Livewire page with full state management
- **Upload Modal** (`UploadModal.php`) - File uploads with progress tracking
- **Preview Modal** (`PreviewModal.php`) - Gallery-style preview with navigation
- **Minimal JavaScript** - Only Alpine.js for UI, no jQuery

Benefits:
- Faster initial page loads
- Real-time reactivity without full page refreshes
- Smaller bundle size
- Better Filament ecosystem integration

## 🧪 Testing

```bash
composer test
```

## 📝 Changelog

Please see [CHANGELOG](CHANGELOG.md) for recent changes.

## 🤝 Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## 🔐 Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## 👏 Credits

- [Codenzia](https://github.com/Codenzia)
- [All Contributors](../../contributors)

## 📄 License

This project uses a **dual license**:

### Open Source Projects
For open source projects released under an OSI-approved license, this plugin is available under the [MIT License](LICENSE.md).

### Commercial Projects
For commercial or proprietary projects, a commercial license is required. Visit [codenzia.com](https://codenzia.com) for licensing options.

See [LICENSE.md](LICENSE.md) for full details.
