<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Module-specific disks
    |--------------------------------------------------------------------------
    |
    | Read these via config(), never env(), so they still work after
    | `php artisan config:cache` in production.
    |
    */

    // Disk for public feedback attachments (defaults to the default disk).
    'feedback_disk' => env('FEEDBACK_FILES_DISK', env('FILESYSTEM_DISK', 'public')),

    // "s3" = real presigned S3 uploads for IoT media; anything else = local mock.
    'iot_media_disk' => env('IOT_MEDIA_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

         'local_iot_mock' => [
        'driver' => 'local',
        'root' => storage_path('app/iot-media-mock'),
        'url' => env('APP_URL') . '/storage/iot-media-mock',
        'visibility' => 'private',
    ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
            // Keep TLS verification on. Only set AWS_VERIFY_SSL=false on a machine with a broken
            // CA bundle, and fix the bundle instead (it exposes S3 traffic to interception).
            'http' => [
                'verify' => (bool) env('AWS_VERIFY_SSL', true),
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
