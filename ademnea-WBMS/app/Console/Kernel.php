<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(new \App\Jobs\CheckFeedAlerts())->hourly();
        $schedule->job(new \App\Jobs\CheckDeviceHealth())->everyFiveMinutes();
        $schedule->command('sanctum:prune-expired --hours=24')->daily();
    }
}
