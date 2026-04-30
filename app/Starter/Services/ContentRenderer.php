<?php

namespace App\Starter\Services;

use Illuminate\Support\Facades\File;
use League\CommonMark\CommonMarkConverter;

class ContentRenderer
{
    protected CommonMarkConverter $converter;

    public function __construct()
    {
        $this->converter = new CommonMarkConverter();
    }

    public function render(string $slug): ?array
    {
        $path = resource_path("content/{$slug}.md");

        if (!File::exists($path)) {
            return null;
        }

        $content = File::get($path);
        $html = $this->converter->convert($content)->getContent();

        // Parse YAML front matter if present
        // Return array with meta and html
    }
}
