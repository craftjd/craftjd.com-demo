<?php

Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'app_env' => app()->environment(),
        'last_deploy' => env('LAST_DEPLOY_AT', 'unknown'),
        'disk_free' => disk_free_space('/'),
    ]);
});
