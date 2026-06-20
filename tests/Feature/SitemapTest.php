<?php

namespace Tests\Feature;

use Tests\TestCase;

class SitemapTest extends TestCase
{
    public function test_sitemap_returns_xml_with_home_url(): void
    {
        $response = $this->get('/sitemap.xml');

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee(url('/'), false)
            ->assertDontSee('/records', false);
    }

    public function test_sitemap_excludes_anchor_navigation_links(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertDontSee('#coffee', false)
            ->assertDontSee('#contact', false);
    }

    public function test_robots_txt_references_sitemap(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Sitemap:')
            ->assertSee('/sitemap.xml');
    }
}
