<?php

return [
    'class_namespace' => 'App\\Http\\Livewire',
    'view_path' => resource_path('views/livewire'),
    'layout' => 'layouts.app',
    'temporary_file_upload' => [
        'disk' => null,
        'rules' => null,
        'directory' => null,
        'middleware' => null,
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
        ],
        'max_upload_time' => 5,
    ],
    'manifest_path' => null,
    'back_button_cache' => false,
    'render_on_redirect' => false,
    /*
    |--------------------------------------------------------------------------
    | Livewire Assets URL
    |--------------------------------------------------------------------------
    |
    | This value sets the path to Livewire JavaScript assets, for cases where
    | your app's domain root is not the correct path. By default, Livewire
    | will load its JavaScript assets from the app's "relative root".
    |
    | Examples: "/assets", "myurl.com/app".
    |
    */

    'asset_url' => null,

    /*
    |--------------------------------------------------------------------------
    | Livewire App URL
    |--------------------------------------------------------------------------
    |
    | This value should be used if livewire assets are served from CDN.
    | Livewire will communicate with an app through this url.
    |
    | Examples: "https://my-app.com", "myurl.com/app".
    |
    */

    'app_url' => null,

    /*
    |--------------------------------------------------------------------------
    | Livewire Endpoint Middleware Group
    |--------------------------------------------------------------------------
    |
    | This value sets the middleware group that will be applied to the main
    | endpoint ("livewire/message") as the application is booted.
    |
    */

    'middleware_group' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Livewire Temporary File Endpoint Middleware Group
    |--------------------------------------------------------------------------
    |
    | This value sets the middleware group that will be applied to the temporary
    | file endpoint ("livewire/preview-file") as the application is booted.
    |
    */

    'temporary_file_middleware_group' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Performance Optimizations
    |--------------------------------------------------------------------------
    |
    | These settings help optimize Livewire performance
    |
    */

    'optimize_performance' => [
        'enable_query_cache' => true,
        'cache_queries_for' => 300, // 5 minutes
        'lazy_load_components' => true,
        'debounce_updates' => 150, // 150ms
    ],
]; 