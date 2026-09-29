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

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
| These were previously declared in app/Console/Kernel.php. Since Laravel 11
| the console kernel is no longer auto-discovered — bootstrap/app.php builds
| the framework's own kernel and never binds App\Console\Kernel — so that
| schedule() method was never called and `schedule:list` reported nothing.
| The result was that no alert was ever generated: UC-FAPI-15's hourly feed
| sweep simply did not run.
|
| Requires a cron entry driving `php artisan schedule:run` every minute.
|--------------------------------------------------------------------------
*/

// UC-FAPI-15: feed-requirement sweep. Hourly, matching the alert cooldown.
Schedule::job(new CheckFeedAlerts())->hourly();

// UC-FAPI-17: device offline / battery / signal checks.
Schedule::job(new CheckDeviceHealth())->everyFiveMinutes();

// REQ-F-IOT-17: flag devices whose recent readings are mostly suspect.
Schedule::job(new CheckAnomalyRate())->everyFifteenMinutes();

// Condition monitoring: trim old IoT telemetry history.
Schedule::job(new PruneTelemetryHistory())->daily();

// Sanctum issues farmer tokens with a 30-day expiry; clear out the dead rows.
Schedule::command('sanctum:prune-expired --hours=24')->daily();
