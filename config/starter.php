<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Platform Features
    |--------------------------------------------------------------------------
    */

    'features' => [
        'flat_file_content' => true,
        'blog' => false,
        'user_accounts' => false,
        'campaign_tracking' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Flat-File Pages
    |--------------------------------------------------------------------------
    |
    | Pages resolve in this order:
    |   1. resources/views/pages/{slug}.blade.php  (hand-built, primary)
    |   2. resources/content/{slug}.md             (markdown fallback)
    |
    | Disable flat_file_content to serve Blade pages only.
    |
    */

    'content' => [
        'path' => resource_path('content'),
        'home_slug' => 'home',
        'home_view' => 'pages.home',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sitemap
    |--------------------------------------------------------------------------
    |
    | Slugs excluded from /sitemap.xml (demo pages, etc.)
    |
    */

    'sitemap' => [
        'excluded' => [
            'records',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Flat-File Data
    |--------------------------------------------------------------------------
    |
    | Structured JSON for tables, listings, etc. Stored outside the public web
    | root at storage/app/private/data/{name}.json
    |
    |   FlatFileStore::all('{name}')
    |
    */

    'data' => [
        'path' => storage_path('app/private/data'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Reserved URL Segments
    |--------------------------------------------------------------------------
    */

    'reserved_slugs' => [
        'health',
        'livewire',
        'up',
        '_ignition',
        'telescope',
        'horizon',
        'sanctum',
    ],

    /*
    |--------------------------------------------------------------------------
    | Platform Defaults
    |--------------------------------------------------------------------------
    */

    'mail_driver' => 'log',
    'filesystem_disk' => 'public',

    /*
    |--------------------------------------------------------------------------
    | Monitoring
    |--------------------------------------------------------------------------
    |
    | GET /health returns JSON when X-Monitor-Key matches MONITOR_KEY.
    | Returns 404 when MONITOR_KEY is unset (endpoint hidden).
    |
    */

    'monitor' => [
        'key' => env('MONITOR_KEY'),
    ],

];
