<?php

use App\Starter\Services\MarkdownRenderer;

return [

  /*
  |--------------------------------------------------------------------------
  | Site Identity
  |--------------------------------------------------------------------------
  */

  'site_name' => env('APP_NAME', 'My Site'),
  'site_description' => env('SITE_DESCRIPTION', ''),
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
    ['label' => 'Home', 'url' => '/'],
    ['label' => 'Services', 'url' => '/services'],
    ['label' => 'Records', 'url' => '/records'],
    ['label' => 'About', 'url' => '/about'],
    ['label' => 'Contact', 'url' => '/contact'],
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
    'footer_text' => null,
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
