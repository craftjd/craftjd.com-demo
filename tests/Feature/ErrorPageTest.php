<?php

namespace Tests\Feature;

use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    public function test_missing_page_returns_branded_404(): void
    {
        $this->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertSee('Not on the menu', false)
            ->assertSee('Back to the counter', false)
            ->assertSee('See the menu', false);
    }
}
