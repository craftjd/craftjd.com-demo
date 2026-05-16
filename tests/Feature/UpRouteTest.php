<?php

namespace Tests\Feature;

use Tests\TestCase;

class UpRouteTest extends TestCase
{
    public function test_up_returns_ok(): void
    {
        $this->get('/up')->assertOk();
    }
}
