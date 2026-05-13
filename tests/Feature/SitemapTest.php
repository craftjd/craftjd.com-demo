<?php

namespace Tests\Feature;

use Tests\TestCase;

class SitemapTest extends TestCase
{
    public function test_sitemap_returns_xml_with_discoverable_urls(): void
    {
        $response = $this->get('/sitemap.xml');

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee('/contact', false)
            ->assertSee('/services', false)
            ->assertSee('/about', false)
            ->assertDontSee('/records', false);
    }

    public function test_robots_txt_references_sitemap(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Sitemap:')
            ->assertSee('/sitemap.xml');
    }

    public function test_page_includes_canonical_and_open_graph_tags(): void
    {
        config([
            'client.site_name' => 'Test Shop',
            'client.site_description' => 'A test shop.',
            'client.seo.og_image' => 'https://example.com/og.jpg',
            'client.seo.twitter_handle' => '@testshop',
        ]);

        $this->get('/contact')
            ->assertOk()
            ->assertSee('rel="canonical"', false)
            ->assertSee('property="og:title"', false)
            ->assertSee('property="og:image"', false)
            ->assertSee('name="twitter:site"', false);
    }
}
