<?php

namespace App\Starter\Contracts;

interface ContentRenderer
{
    public function render(string $slug): ?array;
}
