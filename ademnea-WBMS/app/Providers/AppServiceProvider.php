<?php

namespace App\Providers;

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
                \App\Services\External\ApiaryDirectoryServiceMock::class
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
