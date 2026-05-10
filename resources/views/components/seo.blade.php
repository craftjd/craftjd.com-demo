@props([
  'title',
  'description' => '',
])

@php
  $canonical = url()->current();
  $siteName = config('client.site_name');
  $ogImage = config('client.seo.og_image');

  if ($ogImage && ! str_starts_with($ogImage, 'http')) {
    $ogImage = asset($ogImage);
  }
@endphp

<meta name="description" content="{{ $description }}">
<link rel="canonical" href="{{ $canonical }}">

<meta property="og:type" content="website">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:site_name" content="{{ $siteName }}">
@if ($description)
  <meta property="og:description" content="{{ $description }}">
@endif
@if ($ogImage)
  <meta property="og:image" content="{{ $ogImage }}">
@endif

<meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">
@if ($handle = config('client.seo.twitter_handle'))
  <meta name="twitter:site" content="{{ $handle }}">
@endif
<meta name="twitter:title" content="{{ $title }}">
@if ($description)
  <meta name="twitter:description" content="{{ $description }}">
@endif
@if ($ogImage)
  <meta name="twitter:image" content="{{ $ogImage }}">
@endif
