<?php

use App\Http\Controllers\Api\Farmer\AlertController;
use App\Http\Controllers\Api\Farmer\ApiaryController;
use App\Http\Controllers\Api\Farmer\AuthController;
use App\Http\Controllers\Api\Farmer\InspectionController;
use App\Http\Controllers\Api\Farmer\MediaController;
use App\Http\Controllers\Api\Farmer\MessageController;
use App\Http\Controllers\Api\Farmer\SensorDataController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Farmer Mobile API — /api/v1/farmer
|--------------------------------------------------------------------------
| Included from routes/api.php (which adds the /api prefix and the general
| 60 req/min limit). This file is the only place farmer routes are defined.
|
| Auth: a farmer is a User (role = farmer) linked to a Farmer profile.
| Ownership: hive → apiary → farmer, enforced in the services.
|
| Responses: reads { data, meta }, actions { message, data }, errors { message, errors }.
*/

Route::prefix('v1/farmer')->group(function () {

    // Public — throttled harder to slow down password guessing.
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('password/forgot', [AuthController::class, 'forgotPassword']);
        Route::post('password/reset', [AuthController::class, 'resetPassword']);
    });

    Route::middleware(['auth:sanctum', 'role:farmer'])->group(function () {

        // Account
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('profile', [AuthController::class, 'profile']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
        Route::post('device-token', [AuthController::class, 'registerDeviceToken']);

        // Apiaries and their hives
        Route::get('apiaries', [ApiaryController::class, 'index']);
        Route::get('apiaries/{apiaryId}/hives', [ApiaryController::class, 'hives'])->whereNumber('apiaryId');

        // Per-hive data
        Route::prefix('hives/{hiveId}')->whereNumber('hiveId')->group(function () {
            Route::get('temperature', [SensorDataController::class, 'temperature']);
            Route::get('humidity', [SensorDataController::class, 'humidity']);
            Route::get('carbondioxide', [SensorDataController::class, 'carbonDioxide']);
            Route::get('weight', [SensorDataController::class, 'weight']);
            Route::get('latest', [SensorDataController::class, 'latest']);

            Route::get('photos', [MediaController::class, 'photos']);
            Route::get('audio', [MediaController::class, 'audio']);
            Route::get('videos', [MediaController::class, 'videos']);

            Route::get('inspections', [InspectionController::class, 'index']);
        });

        // Alerts
        Route::get('alerts', [AlertController::class, 'index']);
        Route::patch('alerts/{alertId}/read', [AlertController::class, 'markRead'])->whereNumber('alertId');

        // Farmer-to-admin messages
        Route::get('messages', [MessageController::class, 'index']);
        Route::post('messages', [MessageController::class, 'store']);
    });
});
