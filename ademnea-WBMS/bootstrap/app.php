<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web:      __DIR__.'/../routes/web.php',
        api:      __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health:   '/up',
    )
    
    ->withMiddleware(function (Middleware $middleware): void {
        // Increase upload limits for gallery and media modules
        if (function_exists('ini_set')) {
            @ini_set('upload_max_filesize', '30M');
            @ini_set('post_max_size', '32M');
            @ini_set('memory_limit', '256M');
            @ini_set('max_execution_time', '300');
        }

        // Redirect unauthenticated users to the admin login page instead of
        // the default 'login' route (which doesn't exist in this project).
        $middleware->redirectGuestsTo(fn () => route('admin.login'));

        $middleware->alias([
            'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'ensure.not.farmer'  => \App\Http\Middleware\EnsureNotFarmer::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
