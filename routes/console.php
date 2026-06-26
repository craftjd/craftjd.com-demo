<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use \App\Models\JoesChangeFulfillment;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('abc', function () {
    $pending = JoesChangeFulfillment::pending();
    $fulfilled = JoesChangeFulfillment::fulfilled();
    $this->comment($pending->toJson());
    $this->comment($fulfilled->toJson());
});
