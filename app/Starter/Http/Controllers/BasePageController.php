<?php

namespace App\Starter\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Starter\Contracts\ContentRenderer;

class BasePageController extends Controller
{
    public function show(string $slug, ContentRenderer $renderer)
    {
        if ($this->isReservedSlug($slug)) {
            abort(404);
        }

        $content = $renderer->render($slug);

        if (! $content) {
            abort(404);
        }

        return view(starter_config('content.layout', 'starter.page'), $content);
    }

    protected function isReservedSlug(string $slug): bool
    {
        $firstSegment = explode('/', trim($slug, '/'))[0] ?? '';

        return in_array($firstSegment, config('starter.reserved_slugs', []), true);
    }
}
