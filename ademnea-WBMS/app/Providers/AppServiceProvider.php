<?php

namespace App\Providers;

use App\Models\Farmer;
use App\Models\Hive;
use App\Services\DashboardService;
use App\Contracts\ApiaryRegistryServiceContract;
use App\Contracts\HiveRegistryServiceContract;
use App\Contracts\HiveStatusChangeServiceContract;
use App\Contracts\FarmerRegistryServiceContract;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Contracts\ApiaryDirectoryServiceContract::class,
            \App\Services\ApiaryManagement\ApiaryDirectoryService::class
        );

                $this->app->bind(\App\Contracts\MediaUploadStorageContract::class, function () {
                    return config('filesystems.default_iot_media_driver', env('IOT_MEDIA_DISK')) === 's3'
                        ? new \App\Services\Storage\S3MediaUploadService()
                        : new \App\Services\Storage\LocalMediaUploadMockService();
                });

                $this->app->bind(\App\Contracts\IotQueueTransportContract::class, function () {
                                 $cfg = config('services.iot');

                                return match ($cfg['queue_driver']) {
                                    'sqs' => new \App\Services\Iot\Transport\SqsIotQueueTransport(
                                        $cfg['sqs_queue_url'],
                                        $cfg['aws_region'],
                                    ),
                                    default => new \App\Services\Iot\Transport\RedisIotQueueTransport(
                                        $cfg['queue_name'],
                                        $cfg['dead_letter_name'],
                                        $cfg['max_delivery_attempts'],
                                        $cfg['redis_connection'],
                                    ),
    };
});
        // Bind DashboardService as a singleton so only one instance is
        // created per request cycle — avoids redundant DB connections.
        $this->app->singleton(DashboardService::class);

        $this->app->bind(ApiaryRegistryServiceContract::class, \App\Services\ApiaryManagement\ApiaryRegistrationService::class);
        $this->app->bind(HiveRegistryServiceContract::class, \App\Services\ApiaryManagement\HiveRegistrationService::class);
        $this->app->bind(HiveStatusChangeServiceContract::class, \App\Services\ApiaryManagement\HiveStatusChangeService::class);
        $this->app->bind(FarmerRegistryServiceContract::class, \App\Services\ApiaryManagement\FarmerRegistrationService::class);

        $this->app->bind(
            \App\Contracts\AnomalyStatusContract::class,
            \App\Services\Anomaly\AnomalyStatusService::class
        );
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
