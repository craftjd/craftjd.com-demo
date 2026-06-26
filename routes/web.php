<?php

use App\Http\Controllers\ClaimJoesChangeRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/joes/requests/{mainRequestId}/claim', ClaimJoesChangeRequestController::class)
    ->whereNumber('mainRequestId')
    ->name('joes.requests.claim');
