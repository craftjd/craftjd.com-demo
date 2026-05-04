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
        'home_view' => 'home',
    ],

    /*
    |--------------------------------------------------------------------------
    | Reserved URL Segments
    |--------------------------------------------------------------------------
    */

    'reserved_slugs' => [
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

];
