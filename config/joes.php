<?php

use App\Support\JoesMainSiteUrl;

return [

    'main_site_webhook_url' => JoesMainSiteUrl::resolve(
        env('MAIN_SITE_WEBHOOK_URL'),
        (bool) env('LARAVEL_SAIL'),
    ),

    'webhook_secret' => env('JOES_WEBHOOK_SECRET'),

    /*
    | Local parallel testing (main site + Joe's both running on localhost).
    | Allows record-deploy / fulfill without a new git commit. Never enable in production.
    */
    'local_deploy_bypass_git' => (bool) env('JOES_LOCAL_DEPLOY_BYPASS_GIT', false),

];
