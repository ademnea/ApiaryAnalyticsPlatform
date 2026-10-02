<?php

namespace App\Providers;

use App\Contracts\ApiaryDirectoryServiceContract;
use App\Contracts\ApiaryRegistryServiceContract;
use App\Contracts\FarmerRegistryServiceContract;
use App\Contracts\HiveRegistryServiceContract;
use App\Contracts\HiveStatusChangeServiceContract;
use App\Contracts\IotQueueTransportContract;
use App\Contracts\MediaUploadStorageContract;
use App\Models\Farmer;
use App\Models\Hive;
use App\Services\ApiaryManagement\ApiaryDirectoryService;
use App\Services\ApiaryManagement\ApiaryRegistrationService;
use App\Services\ApiaryManagement\FarmerRegistrationService;
use App\Services\ApiaryManagement\HiveRegistrationService;
use App\Services\ApiaryManagement\HiveStatusChangeService;
use App\Services\DashboardService;
use App\Services\Iot\Transport\RedisIotQueueTransport;
use App\Services\Iot\Transport\SqsIotQueueTransport;
use App\Services\Storage\LocalMediaUploadMockService;
use App\Services\Storage\S3MediaUploadService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ApiaryDirectoryServiceContract::class, ApiaryDirectoryService::class);

        // IOT_MEDIA_DISK=s3 uses real presigned S3 uploads; anything else uses the local mock.
        $this->app->bind(MediaUploadStorageContract::class, function () {
            return config('filesystems.iot_media_disk') === 's3'
                ? new S3MediaUploadService()
                : new LocalMediaUploadMockService();
        });

        // IOT_QUEUE_DRIVER picks where the IoT worker reads device envelopes from.
        $this->app->bind(IotQueueTransportContract::class, function () {
            $cfg = config('services.iot');

            return match ($cfg['queue_driver']) {
                'sqs' => new SqsIotQueueTransport(
                    $cfg['sqs_queue_url'],
                    $cfg['aws_region'],
                    $cfg['verify_ssl'],
                ),
                default => new RedisIotQueueTransport(
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

        $this->app->bind(ApiaryRegistryServiceContract::class, ApiaryRegistrationService::class);
        $this->app->bind(HiveRegistryServiceContract::class, HiveRegistrationService::class);
        $this->app->bind(HiveStatusChangeServiceContract::class, HiveStatusChangeService::class);
        $this->app->bind(FarmerRegistryServiceContract::class, FarmerRegistrationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Applied to every /api route via throttleApi() in bootstrap/app.php.
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

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
