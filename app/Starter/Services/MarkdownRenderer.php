<?php

namespace App\Starter\Services;

use App\Starter\Contracts\ContentRenderer as ContentRendererContract;
use Illuminate\Support\Facades\File;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;
use Symfony\Component\Yaml\Yaml;

class MarkdownRenderer implements ContentRendererContract
{
    protected MarkdownConverter $converter;

    public function __construct()
    {
        $environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new GithubFlavoredMarkdownExtension);

        $this->converter = new MarkdownConverter($environment);
    }

    public function render(string $slug): ?array
    {
        $path = $this->resolvePath($slug);

        if (! $path || ! File::exists($path)) {
            return null;
        }

        $raw = File::get($path);
        [$meta, $markdown] = $this->parseFrontMatter($raw);
        $html = $this->converter->convert($markdown)->getContent();

        return [
            'title' => $meta['title'] ?? $this->titleFromSlug($slug),
            'metaDescription' => $meta['description'] ?? config('client.site_description'),
            'html' => $html,
            'meta' => $meta,
            'slug' => $slug,
        ];
    }

    protected function resolvePath(string $slug): ?string
    {
        $slug = trim(str_replace(['..', '\\'], '', $slug), '/');

        if ($slug === '') {
            return null;
        }

        $base = rtrim(config('starter.content.path'), '/');

        return "{$base}/{$slug}.md";
    }

    /**
     * @return array{0: array<string, mixed>, 1: string}
     */
    protected function parseFrontMatter(string $content): array
    {
        if (! preg_match('/^---\s*\r?\n(.*?)\r?\n---\s*\r?\n(.*)$/s', $content, $matches)) {
            return [[], $content];
        }

        try {
            $meta = Yaml::parse($matches[1]) ?? [];
        } catch (\Throwable) {
            $meta = [];
        }

        return [is_array($meta) ? $meta : [], $matches[2]];
    }

    protected function titleFromSlug(string $slug): string
    {
        $segment = basename(str_replace('/', ' ', $slug));

        return ucwords(str_replace(['-', '_'], ' ', $segment));
    }
}
