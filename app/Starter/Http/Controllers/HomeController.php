<?php

namespace App\Starter\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Starter\Contracts\ContentRenderer;

class HomeController extends Controller
{
    public function index(ContentRenderer $renderer)
    {
        if (! starter_config('content.use_markdown_home', true)) {
            return view(starter_config('content.home_view', 'client.home'));
        }

        $content = $renderer->render(starter_config('content.home_slug', 'home'));

        if (! $content) {
            return view(starter_config('content.home_view', 'client.home'));
        }

        return view(starter_config('content.layout', 'starter.page'), $content);
    }
}
