<?php

namespace App\Starter\Http\Controllers;

use App\Starter\Services\ContentRenderer;

class BasePageController extends Controller
{
    public function show(string $slug)
    {
        $content = app(ContentRenderer::class)->render($slug);

        if (!$content) {
            abort(404);
        }

        return view('starter.page', $content);
    }
}
