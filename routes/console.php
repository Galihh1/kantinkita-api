<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ─── Scheduled Jobs ───────────────────────────────────────────
Schedule::command('orders:cancel-expired')->everyFiveMinutes();
Schedule::command('backup:auto')->dailyAt('02:00');
Schedule::command('queue:restart')->hourly();
