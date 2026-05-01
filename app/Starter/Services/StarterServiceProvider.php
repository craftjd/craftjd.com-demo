<?php

namespace App\Starter\Services;

use App\Starter\Contracts\ContentRenderer as ContentRendererContract;
use Illuminate\Support\ServiceProvider;

class StarterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once __DIR__.'/../Support/helpers.php';

        $this->app->bind(
            ContentRendererContract::class,
            config('client.bindings.content_renderer', MarkdownRenderer::class)
        );
    }

    public function boot(): void
    {
        $this->mergeClientConfig();

        if (starter_feature('flat_file_content')) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }
    }

    protected function mergeClientConfig(): void
    {
        config([
            'starter.features' => array_merge(
                config('starter.features', []),
                config('client.features', [])
            ),
            'starter.content' => array_merge(
                config('starter.content', []),
                config('client.content', [])
            ),
        ]);
    }
}
