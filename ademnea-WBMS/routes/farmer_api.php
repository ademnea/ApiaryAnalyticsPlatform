<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Farmer\{
    AuthController,
    ProfileController,
    FarmController,
    HiveController,
    SensorDataController,
    MediaController,
    InspectionController,
    AlertController,
    MessageController,
};

/*
|--------------------------------------------------------------------------
| Farmer Mobile API Routes
|--------------------------------------------------------------------------
| All routes are prefixed /api/v1/farmer (REQ-F-FAPI-33).
| Public routes (no token): register, login, password reset.
| Protected routes: auth:sanctum + role:farmer middleware.
|
| Include this file from routes/api.php:
|   require __DIR__.'/farmer_api.php';
|--------------------------------------------------------------------------
*/

Route::prefix('v1/farmer')->group(function () {

    // -------------------------------------------------------------------------
    // Public — no authentication required
    // -------------------------------------------------------------------------
    Route::post('register',         [AuthController::class, 'register']);
    Route::post('login',            [AuthController::class, 'login']);
    Route::post('password/forgot',  [AuthController::class, 'forgotPassword']);
    Route::post('password/reset',   [AuthController::class, 'resetPassword']);

    // -------------------------------------------------------------------------
    // Protected — must be authenticated farmer with active account
    // -------------------------------------------------------------------------
    Route::middleware(['auth:sanctum', 'role:farmer'])->group(function () {

        // Auth
        Route::post('logout', [AuthController::class, 'logout']);

        // Profile — REQ-F-FAPI-05
        Route::get ('profile', [ProfileController::class, 'show']);
        Route::put ('profile', [ProfileController::class, 'update']);

        // FCM device token — REQ-F-FAPI-27
        Route::post('device-token', [AlertController::class, 'storeDeviceToken']);

        // Farms — REQ-F-FAPI-06
        Route::get('farms', [FarmController::class, 'index']);

        // Hives under a farm — REQ-F-FAPI-07
        Route::get('farms/{farm_id}/hives', [HiveController::class, 'index']);

        // Hive-scoped routes (all require hive ownership check in Form Request)
        Route::prefix('hives/{hive_id}')->group(function () {

            // Sensor data — REQ-F-FAPI-14 to 18
            Route::get('temperature',   [SensorDataController::class, 'temperature']);
            Route::get('humidity',      [SensorDataController::class, 'humidity']);
            Route::get('carbondioxide', [SensorDataController::class, 'carbonDioxide']);
            Route::get('weight',        [SensorDataController::class, 'weight']);
            Route::get('latest',        [SensorDataController::class, 'latest']);

            // Media — REQ-F-FAPI-19 to 21
            Route::get('photos', [MediaController::class, 'photos']);
            Route::get('audio',  [MediaController::class, 'audio']);
            Route::get('videos', [MediaController::class, 'videos']);

            // Inspections — REQ-F-FAPI-22
            Route::get('inspections', [InspectionController::class, 'index']);
        });

        // Alerts — REQ-F-FAPI-25, 26
        Route::get  ('alerts',                  [AlertController::class, 'index']);
        Route::patch('alerts/{alert_id}/read',  [AlertController::class, 'markRead']);

        // Farmer-to-admin messages — REQ-F-FAPI-31, 32
        Route::post('messages', [MessageController::class, 'store']);
        Route::get ('messages', [MessageController::class, 'index']);
    });
});
