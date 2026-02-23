<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable or disable features. Disabled features won't load their UI
    | components or register their routes.
    |
    */
    'features' => [
        'tags' => true,
        'collections' => true,
        'metadata' => true,
        'versioning' => true,
        'search' => true,
        'export_import' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Controls how many files load per batch. If the total file count in a
    | folder is within this limit, everything loads at once. When the total
    | exceeds it, infinite scroll loads additional batches automatically
    | as the user scrolls down.
    |
    */
    'pagination' => [
        'per_page' => 200,
    ],

    /*
    |--------------------------------------------------------------------------
    | Thumbnail Sizes
    |--------------------------------------------------------------------------
    |
    | Define thumbnail sizes generated for uploaded images.
    | Format: 'name' => 'WIDTHxHEIGHT'
    |
    */
    'sizes' => [
        'thumb' => '150x150',
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | Allowed File Extensions
    |--------------------------------------------------------------------------
    */
    'allowed_mime_types' => 'jpg,jpeg,png,gif,txt,docx,zip,mp3,bmp,csv,xls,xlsx,ppt,pptx,pdf,mp4,m4v,doc,mpga,wav,webp,webm,mov,jfif,avif,rar,x-rar',

    /*
    |--------------------------------------------------------------------------
    | MIME Type Groups
    |--------------------------------------------------------------------------
    */
    'mime_types' => [
        'image' => [
            'image/png',
            'image/jpeg',
            'image/gif',
            'image/bmp',
            'image/svg+xml',
            'image/webp',
            'image/avif',
        ],
        'video' => [
            'video/mp4',
            'video/m4v',
            'video/mov',
            'video/quicktime',
        ],
        'document' => [
            'application/pdf',
            'application/vnd.ms-excel',
            'application/excel',
            'application/x-excel',
            'application/x-msexcel',
            'text/plain',
            'application/msword',
            'text/csv',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ],
        'zip' => [
            'application/zip',
            'application/x-zip-compressed',
            'application/x-compressed',
            'multipart/x-zip',
            'multipart/x-rar',
        ],
        'audio' => [
            'audio/mpeg',
            'audio/mp3',
            'audio/wav',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Placeholder Image
    |--------------------------------------------------------------------------
    */
    'default_image' => null,

    'sidebar_display' => 'horizontal',

    /*
    |--------------------------------------------------------------------------
    | Watermark
    |--------------------------------------------------------------------------
    */
    'watermark' => [
        'enabled' => 0,
        'source' => null,
        'size' => 10,
        'opacity' => 70,
        'position' => 'bottom-right',
        'x' => 10,
        'y' => 10,
    ],

    'custom_s3_path' => '',

    /*
    |--------------------------------------------------------------------------
    | Chunk Upload
    |--------------------------------------------------------------------------
    */
    'chunk' => [
        'enabled' => false,
        'chunk_size' => 1024 * 1024,
        'max_file_size' => 1024 * 1024,
        'storage' => [
            'chunks' => 'chunks',
            'disk' => 'local',
        ],
        'clear' => [
            'timestamp' => '-3 HOURS',
            'schedule' => [
                'enabled' => true,
                'cron' => '25 * * * *',
            ],
        ],
        'chunk' => [
            'name' => [
                'use' => [
                    'session' => true,
                    'browser' => false,
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Preview
    |--------------------------------------------------------------------------
    */
    'preview' => [
        'document' => [
            'enabled' => true,
            'providers' => [
                'google' => 'https://docs.google.com/gview?embedded=true&url={url}',
                'microsoft' => 'https://view.officeapps.live.com/op/view.aspx?src={url}',
            ],
            'default' => 'microsoft',
            'type' => 'iframe',
            'mime_types' => [
                'application/pdf',
                'application/vnd.ms-excel',
                'application/excel',
                'application/x-excel',
                'application/x-msexcel',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-Resolve Folders
    |--------------------------------------------------------------------------
    |
    | When enabled, files created without an explicit folder_id will
    | have their folder automatically resolved from the URL path.
    | E.g. url "avatars/photo.jpg" creates an "Avatars" folder.
    |
    */
    'auto_resolve_folders' => true,

    'default_upload_folder' => '',
    'default_upload_url' => '',
    'generate_thumbnails_enabled' => true,
    'generate_thumbnails_chunk_limit' => 50,

    'folder_colors' => [
        '#3498db',
        '#2ecc71',
        '#e74c3c',
        '#f39c12',
        '#9b59b6',
        '#1abc9c',
        '#34495e',
        '#e67e22',
        '#27ae60',
        '#c0392b',
    ],

    'use_storage_symlink' => false,

    /*
    |--------------------------------------------------------------------------
    | Max File Size (bytes)
    |--------------------------------------------------------------------------
    */
    'max_file_size' => 10 * 1024 * 1024,

    /*
    |--------------------------------------------------------------------------
    | Storage Driver
    |--------------------------------------------------------------------------
    |
    | Options: 'public', 's3', 'r2', 'do_spaces', 'wasabi', 'bunnycdn', 'backblaze'
    |
    */
    'driver' => 'public',

    /*
    |--------------------------------------------------------------------------
    | Allowed Download Domains
    |--------------------------------------------------------------------------
    |
    | Restrict URL downloads to these domains. Empty = all external domains allowed.
    |
    */
    'allowed_download_domains' => [],

    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */
    'search' => [
        'driver' => 'database', // 'database' or 'scout'
        'min_query_length' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | File Versioning
    |--------------------------------------------------------------------------
    */
    'versioning' => [
        'max_versions' => 10,
        'auto_prune' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Metadata
    |--------------------------------------------------------------------------
    */
    'metadata' => [
        'auto_extract' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | File Picker
    |--------------------------------------------------------------------------
    */
    'picker' => [
        'default_view' => 'grid',
        'show_upload' => true,
        'show_folders' => true,
        'direct_upload' => false,

        // Display style for MediaPickerField: 'compact', 'dropdown', 'thumbnail', 'integratedLinks', or 'integratedDropdown'
        // - compact:            Text links for browse/upload with chip-style file list (default)
        // - dropdown:           Button with dropdown menu for browse/upload options
        // - thumbnail:          Visual preview card, click to browse, drag & drop
        // - integratedLinks:    Thumbnail preview + text links below, drag & drop
        // - integratedDropdown: Thumbnail preview + dropdown button below, drag & drop
        'display_style' => 'compact',

        // Chip size preset for compact and dropdown styles: 'xs', 'sm', 'md', 'lg', 'xl', '2xl'.
        // Controls thumbnail size, text size, and spacing within the file chips.
        'chip_size' => 'sm',

        // Preview container width (CSS value, e.g. '12rem', '256px'). Used by thumbnail, integratedLinks, integratedDropdown styles.
        // Set to null to let the container take its natural/parent width.
        'preview_width' => '12rem',

        // Preview container height (CSS value, e.g. '8rem', '128px'). When null, the container uses aspect-square.
        // Set to a height class (e.g. '8rem') for non-square preview areas like banners.
        'preview_height' => null,

        // Lightbox (full-screen image preview) max dimensions.
        // Set to a CSS value (e.g. '800px', '50vw') to constrain the preview image.
        // Null = image fills the available viewport (default).
        'lightbox_max_width' => null,
        'lightbox_max_height' => null,

        // Lightbox backdrop opacity (0-100). 0 = fully transparent, 100 = fully opaque.
        'lightbox_opacity' => 90,
    ],

    /*
    |--------------------------------------------------------------------------
    | Filament Sidebar Navigation
    |--------------------------------------------------------------------------
    |
    | Configure how the media pages appear in the Filament sidebar.
    | Set 'shared_group' to group all media pages under one heading.
    |
    */
    'navigation' => [
        'media' => [
            'label' => null,
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
        'shared_group' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Color Theme
    |--------------------------------------------------------------------------
    |
    | Customize the media library colors for light and dark mode.
    | These are injected as CSS custom properties (--fm-*).
    |
    */
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

    /*
    |--------------------------------------------------------------------------
    | Settings Page
    |--------------------------------------------------------------------------
    */
    'settings' => [
        'enabled' => true,
        'access' => 'all',
    ],

    /*
    |--------------------------------------------------------------------------
    | Private Files
    |--------------------------------------------------------------------------
    |
    | Configure how private files are stored and served. Private files are
    | served through an authenticated controller instead of direct URLs.
    |
    */
    'private_files' => [
        'enabled' => true,
        'signed_url_expiry' => 30, // minutes for cloud temporary URLs
        'private_disk' => 'local',
    ],
];
