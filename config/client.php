<?php

use App\Starter\Services\MarkdownRenderer;

return [

    /*
  |--------------------------------------------------------------------------
  | Site Identity
  |--------------------------------------------------------------------------
  */

    'site_name' => env('APP_NAME', "Joe's"),
    'site_description' => env('SITE_DESCRIPTION', 'Drinkable Coffee.'),
    'site_url' => env('APP_URL', 'http://localhost'),
    'contact_email' => env('CONTACT_EMAIL'),

    /*
  |--------------------------------------------------------------------------
  | Navigation
  |--------------------------------------------------------------------------
  |
  | Simple header links for markdown sites. Clients customize freely.
  |
  */

    'navigation' => [
        ['label' => 'Coffee', 'url' => '#coffee'],
        ['label' => 'Food', 'url' => '#food'],
        ['label' => 'Specials', 'url' => '#specials'],
        ['label' => 'Takeout', 'url' => '#takeout'],
        ['label' => 'Hours', 'url' => '#hours'],
        ['label' => 'Team', 'url' => '#team'],
        ['label' => 'Contact', 'url' => '#contact'],
        ['label' => 'Club', 'url' => '#club'],
    ],

    /*
  |--------------------------------------------------------------------------
  | Social & SEO
  |--------------------------------------------------------------------------
  */

    'social' => [
        // 'twitter' => 'https://twitter.com/handle',
        // 'linkedin' => 'https://linkedin.com/company/example',
        // 'facebook' => null,
        // 'instagram' => null,
    ],

    'seo' => [
        'og_image' => null,
        'twitter_handle' => null,
        'robots' => 'index, follow',
    ],

    /*
  |--------------------------------------------------------------------------
  | Analytics
  |--------------------------------------------------------------------------
  */

    'analytics' => [
        'google_analytics_id' => env('GA_MEASUREMENT_ID'),
    ],

    /*
  |--------------------------------------------------------------------------
  | Branding
  |--------------------------------------------------------------------------
  */

    'theme' => [
        'logo' => null,
        'logo_alt' => null,
        'footer_text' => 'Independent coffee shop. No franchise. No nonsense.',
    ],

    /*
  |--------------------------------------------------------------------------
  | Feature Overrides
  |--------------------------------------------------------------------------
  |
  | Set any starter.features flag to true/false to override platform defaults.
  |
  */

    'features' => [
        // 'flat_file_content' => true,
        // 'blog' => false,
    ],

    /*
  |--------------------------------------------------------------------------
  | Content Overrides
  |--------------------------------------------------------------------------
  |
  | Blade pages live at resources/views/pages/{slug}.blade.php
  | Markdown pages are an optional fallback at resources/content/{slug}.md
  |
  */

    'content' => [
        // 'home_slug' => 'home',
        // 'home_view' => 'home',
    ],

    /*
  |--------------------------------------------------------------------------
  | Service Bindings
  |--------------------------------------------------------------------------
  */

    'bindings' => [
        'content_renderer' => MarkdownRenderer::class,
    ],

];
