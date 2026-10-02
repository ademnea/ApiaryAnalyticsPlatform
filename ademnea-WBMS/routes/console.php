<?php

use App\Jobs\CheckDeviceHealth;
use App\Jobs\CheckFeedAlerts;
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
|
| Run by `php artisan schedule:run` every minute (cron on the server,
| `php artisan schedule:work` locally). The jobs are queued, so a queue
| worker must also be running.
|
*/

Schedule::job(new CheckFeedAlerts())->hourly();
Schedule::job(new CheckDeviceHealth())->everyFiveMinutes();
Schedule::command('sanctum:prune-expired --hours=24')->daily();
