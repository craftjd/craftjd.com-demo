<?php

use App\Starter\Http\Controllers\BasePageController;
use App\Starter\Http\Controllers\HealthController;
use App\Starter\Http\Controllers\HomeController;
use App\Starter\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Starter Routes
|--------------------------------------------------------------------------
|
| Loaded by StarterServiceProvider when flat_file_content is enabled.
| Client-specific routes belong in routes/web.php at the project root.
|
*/

Route::get('/health', HealthController::class)->name('health');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

Route::get('/robots.txt', function () {
    $sitemap = url('/sitemap.xml');

    return response("User-agent: *\nDisallow:\n\nSitemap: {$sitemap}\n", 200, [
        'Content-Type' => 'text/plain',
    ]);
})->name('robots');

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/{slug}', [BasePageController::class, 'show'])
    ->where('slug', '[a-zA-Z0-9_\-\/]+')
    ->name('page');
