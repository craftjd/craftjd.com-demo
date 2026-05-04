<?php

namespace App\Starter\Services;

use App\Starter\Contracts\ContentRenderer;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\View as ViewFactory;

class PageResolver
{
    public function bladeViewForSlug(string $slug): ?string
    {
        $view = 'pages.'.str_replace('/', '.', $slug);

        return ViewFactory::exists($view) ? $view : null;
    }

    public function resolve(string $slug, ContentRenderer $renderer): ?View
    {
        if ($view = $this->bladeViewForSlug($slug)) {
            return view($view);
        }

        if (! starter_feature('flat_file_content')) {
            return null;
        }

        $content = $renderer->render($slug);

        if (! $content) {
            return null;
        }

        return view('pages.markdown', $content);
    }
}
