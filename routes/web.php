<?php

use App\Http\Controllers\ClaimJoesChangeRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/joes/requests/{publicId}/claim', ClaimJoesChangeRequestController::class)
    ->whereUuid('publicId')
    ->name('joes.requests.claim');
