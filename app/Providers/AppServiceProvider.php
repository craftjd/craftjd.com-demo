<?php

namespace App\Providers;

use App\Services\DeployMeta;
use App\Services\SiteContent;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SiteContent::class);
        $this->app->singleton(DeployMeta::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('pages.home', function ($view) {
            $view->with('content', app(SiteContent::class));
        });
    }
}
