<?php

use App\Jobs\CheckAnomalyRate;
use App\Jobs\CheckDeviceHealth;
use App\Jobs\CheckFeedAlerts;
use App\Jobs\PruneTelemetryHistory;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Registered here, not in app/Console/Kernel.php: since Laravel 11 the
// framework no longer loads a console kernel's schedule() method.
Schedule::job(new CheckFeedAlerts())->hourly();
Schedule::job(new CheckDeviceHealth())->everyFiveMinutes();
Schedule::job(new CheckAnomalyRate())->everyFifteenMinutes();
Schedule::job(new PruneTelemetryHistory())->daily();
Schedule::command('sanctum:prune-expired --hours=24')->daily();
