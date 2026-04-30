<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('client.home'));
Route::get('/{slug}', [BasePageController::class, 'show'])->where('slug', '.*');
