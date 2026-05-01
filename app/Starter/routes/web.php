<?php

use App\Starter\Http\Controllers\BasePageController;
use App\Starter\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Starter Markdown Routes
|--------------------------------------------------------------------------
|
| Loaded by StarterServiceProvider when flat_file_content is enabled.
| Client-specific routes belong in routes/web.php at the project root.
|
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/{slug}', [BasePageController::class, 'show'])
    ->where('slug', '[a-zA-Z0-9_\-\/]+')
    ->name('page');
