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

    'default' => env('FILESYSTEM_DISK', 'local'),

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
        ],

        'seaweedfs' => [
            'driver' => 's3',
            'key' => env('SEAWEEDFS_S3_ACCESS_KEY', 'seaweedfs_access_key_123'),
            'secret' => env('SEAWEEDFS_S3_SECRET_KEY', 'seaweedfs_secret_key_456'),
            'region' => env('SEAWEEDFS_S3_REGION', 'us-east-1'),
            'bucket' => env('SEAWEEDFS_DEFAULT_BUCKET', 'files'),
            'url' => env('SEAWEEDFS_S3_ENDPOINT', 'http://localhost:8333'),
            'endpoint' => env('SEAWEEDFS_S3_ENDPOINT', 'http://localhost:8333'),
            'use_path_style_endpoint' => true, // Required for SeaweedFS
            'throw' => false,
            'report' => false,
        ],

        'public-image-user' => [
            'driver' => 'local',
            'root' => public_path() . '/storage/images/user/',
            'url' => env('APP_URL') . '/storage/images/user/',
            'visibility' => 'public',
            'throw' => false,
        ],

        'public-file-attachment' => [
            'driver' => 'local',
            'root' => public_path() . '/storage/attachment/',
            'url' => env('APP_URL') . '/storage/attachment/',
            'visibility' => 'public',
            'throw' => false,
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
