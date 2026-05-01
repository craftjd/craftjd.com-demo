<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Platform Features
    |--------------------------------------------------------------------------
    |
    | Toggle starter capabilities. Clients override individual flags in
    | config/client.php under the same keys.
    |
    */

    'features' => [
        'flat_file_content' => true,
        'blog' => false,
        'user_accounts' => false,
        'campaign_tracking' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Flat-File Content
    |--------------------------------------------------------------------------
    |
    | Markdown pages live in resources/content/{slug}.md
    |
    */

    'content' => [
        'path' => resource_path('content'),
        'home_slug' => 'home',
        'layout' => 'starter.page',
        'use_markdown_home' => true,
        'home_view' => 'client.home',
    ],

    /*
    |--------------------------------------------------------------------------
    | Reserved URL Segments
    |--------------------------------------------------------------------------
    |
    | Slugs that must never resolve to markdown files.
    |
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
