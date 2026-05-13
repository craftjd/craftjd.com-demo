<?php

namespace App\Starter\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class SitemapGenerator
{
  public function urls(): Collection
  {
    $paths = collect(['/']);

    $paths = $paths
      ->merge($this->bladePaths())
      ->merge($this->markdownPaths())
      ->merge($this->navigationPaths())
      ->map(fn (string $path) => '/'.trim($path, '/'))
      ->map(fn (string $path) => $path === '/' ? '/' : $path)
      ->unique()
      ->filter(fn (string $path) => $this->isIncluded($path))
      ->sort()
      ->values();

    return $paths->map(fn (string $path) => url($path));
  }

  protected function bladePaths(): Collection
  {
    $base = resource_path('views/pages');

    if (! File::isDirectory($base)) {
      return collect();
    }

    return collect(File::allFiles($base))
      ->filter(fn ($file) => str_ends_with($file->getFilename(), '.blade.php'))
      ->map(function ($file) use ($base) {
        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($base) + 1));

        return str_replace('.blade.php', '', $relative);
      })
      ->reject(fn (string $slug) => $slug === 'home');
  }

  protected function markdownPaths(): Collection
  {
    if (! starter_feature('flat_file_content')) {
      return collect();
    }

    $base = config('starter.content.path');

    if (! File::isDirectory($base)) {
      return collect();
    }

    return collect(File::allFiles($base))
      ->filter(fn ($file) => str_ends_with($file->getFilename(), '.md'))
      ->map(function ($file) use ($base) {
        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($base) + 1));

        return str_replace('.md', '', $relative);
      })
      ->reject(fn (string $slug) => $slug === 'home')
      ->reject(fn (string $slug) => File::exists(resource_path('views/pages/'.$slug.'.blade.php')));
  }

  protected function navigationPaths(): Collection
  {
    return collect(config('client.navigation', []))
      ->pluck('url')
      ->filter();
  }

  protected function isIncluded(string $path): bool
  {
    $slug = trim($path, '/') ?: 'home';
    $firstSegment = explode('/', $slug)[0];

    if (in_array($firstSegment, config('starter.reserved_slugs', []), true)) {
      return false;
    }

    $excluded = config('starter.sitemap.excluded', []);

    return ! in_array($slug, $excluded, true)
      && ! in_array($firstSegment, $excluded, true);
  }
}
