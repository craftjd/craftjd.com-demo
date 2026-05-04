<?php

namespace App\Starter\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Starter\Contracts\ContentRenderer;
use App\Starter\Services\PageResolver;

class BasePageController extends Controller
{
    public function show(string $slug, PageResolver $pages, ContentRenderer $renderer)
    {
        if ($this->isReservedSlug($slug)) {
            abort(404);
        }

        $view = $pages->resolve($slug, $renderer);

        if (! $view) {
            abort(404);
        }

        return $view;
    }

    protected function isReservedSlug(string $slug): bool
    {
        $firstSegment = explode('/', trim($slug, '/'))[0] ?? '';

        return in_array($firstSegment, config('starter.reserved_slugs', []), true);
    }
}
