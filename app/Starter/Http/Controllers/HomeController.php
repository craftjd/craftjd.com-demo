<?php

namespace App\Starter\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Starter\Contracts\ContentRenderer;
use App\Starter\Services\PageResolver;

class HomeController extends Controller
{
    public function index(PageResolver $pages, ContentRenderer $renderer)
    {
        $view = $pages->resolve(starter_config('content.home_slug', 'home'), $renderer);

        if ($view) {
            return $view;
        }

        return view(starter_config('content.home_view', 'home'));
    }
}
