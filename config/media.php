<?php

return [
    'sizes' => [
        'thumb' => '150x150',
    ],
    'permissions' => [
        'folders.create',
        'folders.edit',
        'folders.trash',
        'folders.destroy',
        'files.create',
        'files.edit',
        'files.trash',
        'files.destroy',
        'files.favorite',
        'folders.favorite',
    ],
    'allowed_mime_types' =>'jpg,jpeg,png,gif,txt,docx,zip,mp3,bmp,csv,xls,xlsx,ppt,pptx,pdf,mp4,m4v,doc,mpga,wav,webp,webm,mov,jfif,avif,rar,x-rar',
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
    'default_image' =>  '/vendor/core/core/base/images/placeholder.png',
    'sidebar_display' => 'horizontal', // Use "vertical" or "horizontal"
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

    'chunk' => [
        'enabled' => false,
        'chunk_size' => 1024 * 1024, // Bytes
        'max_file_size' => 1024 * 1024, // MB

        /*
         * The storage config
         */
        'storage' => [
            /*
             * Returns the folder name of the chunks. The location is in storage/app/{folder_name}
             */
            'chunks' => 'chunks',
            'disk' => 'local',
        ],
        'clear' => [
            /*
             * How old chunks we should delete
             */
            'timestamp' => '-3 HOURS',
            'schedule' => [
                'enabled' => true,
                'cron' => '25 * * * *', // run every hour on the 25th minute
            ],
        ],
        'chunk' => [
            // setup for the chunk naming setup to ensure same name upload at same time
            'name' => [
                'use' => [
                    'session' => true, // should the chunk name use the session id? The uploader must send cookie!,
                    'browser' => false, // instead of session we can use the ip and browser?
                ],
            ],
        ],
    ],

    'preview' => [
        'document' => [
            'enabled' => true,
            'providers' => [
                'google' => 'https://docs.google.com/gview?embedded=true&url={url}',
                'microsoft' => 'https://view.officeapps.live.com/op/view.aspx?src={url}',
            ],
            'default' => 'microsoft',
            'type' => 'iframe',          // use iframe or popup
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
    'navigation' => [
        'icon' => 'heroicon-o-photo',
        'label' => null,
        'group' => null,
    ],
    'use_storage_symlink' => false,
];
