<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        'gcs' => [
            'driver'         => 'gcs',
            'key_file'       => env('GOOGLE_CLOUD_KEY_FILE'),
            'project_id'     => env('GOOGLE_CLOUD_PROJECT_ID'),
            'bucket'         => env('GOOGLE_CLOUD_BUCKET'),
            'path_prefix'    => env('GOOGLE_CLOUD_PATH_PREFIX', ''),
            'visibility'     => 'private',
            'throw'          => true,
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
