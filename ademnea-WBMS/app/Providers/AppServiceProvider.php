<?php

namespace App\Providers;

use App\Models\Farmer;
use App\Models\Hive;
use App\Services\DashboardService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind DashboardService as a singleton so only one instance is
        // created per request cycle — avoids redundant DB connections.
        $this->app->singleton(DashboardService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Share $unreadAlerts with every view that uses the admin layout.
        // This drives the topbar bell badge without requiring each controller
        // to pass the count individually.
        View::composer('layouts.app', function ($view) {
            if (auth()->check()) {
                $count = 0;

                // Hives in critical status
                $count += Hive::whereIn('current_status', ['Queenless', 'Absconded', 'Under Inspection'])->count();

                // Farmers awaiting approval
                $count += Farmer::where('profile_status', 'pending')->count();

                // TODO: add IotDevice offline count once model exists

                $view->with('unreadAlerts', $count);
            }
        });
    }
}
