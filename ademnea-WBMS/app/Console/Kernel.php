<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Feed alert check - runs every hour
        $schedule->job(new \App\Jobs\CheckFeedAlerts())->hourly();

        // Device health check - runs every 5 minutes
        $schedule->job(new \App\Jobs\CheckDeviceHealth())->everyFiveMinutes();

        // Clean up expired tokens - daily
        $schedule->command('sanctum:prune-expired --hours=24')->daily();
    }
}