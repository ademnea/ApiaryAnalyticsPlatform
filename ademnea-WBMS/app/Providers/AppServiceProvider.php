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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
