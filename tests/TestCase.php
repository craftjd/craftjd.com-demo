<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Avoid coupling HTTP feature tests to a running Vite dev server.
        $this->withoutVite();
    }
}
