# Filament Media Manager

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codenzia/filament-media.svg?style=flat-square)](https://packagist.org/packages/codenzia/filament-media)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/codenzia/filament-media/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/codenzia/filament-media/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/codenzia/filament-media.svg?style=flat-square)](https://packagist.org/packages/codenzia/filament-media)

A powerful media manager plugin for Filament v4 with drag-and-drop uploads, folder organization, image editing, and thumbnail generation.

## Features

- Drag-and-drop file uploads with Dropzone.js
- Chunked uploads for large files
- Folder organization with color coding
- Image cropping and editing
- Automatic thumbnail generation
- Favorites and recent files
- Trash/restore functionality
- Document preview (PDF, Office documents)
- Cloud storage support (S3, etc.)
- Watermark support
- Multi-file download as ZIP

## Requirements

- PHP 8.2+
- Laravel 11+
- Filament 4.0+
- GD or Imagick PHP extension
- ZIP PHP extension

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

Optionally, publish the views:

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

## Configuration

The published config file (`config/media.php`) contains all available options:

```php
return [
    // Thumbnail sizes
    'sizes' => [
        'thumb' => '150x150',
    ],

    // Allowed file types
    'allowed_mime_types' => 'jpg,jpeg,png,gif,txt,docx,zip,mp3,pdf,mp4,...',

    // Chunked upload settings
    'chunk' => [
        'enabled' => false,
        'chunk_size' => 1024 * 1024, // 1MB chunks
        'max_file_size' => 1024 * 1024, // Max file size in MB
    ],

    // Watermark settings
    'watermark' => [
        'enabled' => false,
        'source' => null,
        'size' => 10,
        'opacity' => 70,
        'position' => 'bottom-right',
    ],

    // Document preview
    'preview' => [
        'document' => [
            'enabled' => true,
            'default' => 'microsoft', // or 'google'
        ],
    ],

    // Folder colors
    'folder_colors' => [
        '#3498db', '#2ecc71', '#e74c3c', // ...
    ],

    // Navigation settings
    'navigation' => [
        'icon' => 'heroicon-o-photo',
        'label' => null,
        'group' => null,
    ],

    // Enable automatic thumbnail generation
    'generate_thumbnails_enabled' => true,
];
```

## Usage

### Accessing the Media Manager

Once installed and configured, the media manager is available at `/media` in your application (or under your Filament panel prefix).

### Using the Facade

You can interact with media programmatically using the facade:

```php
use Codenzia\FilamentMedia\Facades\FilamentMedia;

// Upload a file
$result = FilamentMedia::handleUpload($uploadedFile, $folderId);

// Get file URL
$url = FilamentMedia::url($file->url);

// Generate thumbnails
FilamentMedia::generateThumbnails($mediaFile);

// Check if file can have thumbnails
$canGenerate = FilamentMedia::canGenerateThumbnails($mimeType);
```

### Syncing Existing Files

To sync files from storage to the database:

```bash
php artisan filament-media:sync
```

## Permissions

The plugin includes the following permission keys that you can integrate with your authorization system:

- `folders.create` - Create folders
- `folders.edit` - Edit folders
- `folders.trash` - Move folders to trash
- `folders.destroy` - Permanently delete folders
- `files.create` - Upload files
- `files.edit` - Edit files
- `files.trash` - Move files to trash
- `files.destroy` - Permanently delete files
- `files.favorite` - Add files to favorites
- `folders.favorite` - Add folders to favorites

## Hooks and Filters

The plugin provides a simple hook/filter system for extensibility:

```php
use function add_action;
use function add_filter;

// Add an action hook
add_action('media.file.uploaded', function ($file) {
    // Do something after file upload
});

// Add a filter
add_filter('media.allowed_mime_types', function ($types) {
    $types[] = 'image/svg+xml';
    return $types;
});
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Codenzia](https://github.com/Codenzia)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
