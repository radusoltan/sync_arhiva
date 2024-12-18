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
        ],

        'alpha_images' => [
            'driver' => 'local',
            'root' => env('IMAGE_STORAGE_PATH', '/var/www/html/images/alpha'),
            'url' => env('APP_URL').'/images',
            'visibility' => 'public',
        ],

        'beta_images' => [
            'driver' => 'local',
            'root' => env('BETA_IMAGE_STORAGE_PATH', '/var/www/html/images/beta'),
            'url' => env('APP_URL').'/images/beta',
            'visibility' => 'public',
        ],

        'external' => [
            'driver' => 'local',
            'root' => storage_path('app/external'),
        ],

        'sftp' => [
            'driver' => 'sftp',
            'host' => env('SFTP_HOST'),
            'port' => 2022,
            'username' => env('SFTP_USERNAME'),
            'password' => env('SSH_PASS'),
            'root' => '/var/www/html/images/beta',
            'timeout' => 10,
            'permPublic' => 0755,
            'directoryPerm' => 0755,
            'visibility' => 'public',
        ],

        'alpha' => [
            'driver' => 'local',
            'root' => env('EXTERNAL_HDD'),
        ],

        'beta' => [
            'driver' => 'local',
            'root' => env('BETA_EXTERNAL_HDD'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
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
