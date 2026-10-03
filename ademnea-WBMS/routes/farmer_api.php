<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Farmer\{
    AuthController,
    ProfileController,
    ApiaryController,
    SensorDataController,
    MediaController,
    InspectionController,
    HiveLocationController,
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

    // UC-FAPI-02 alt-flow D: 10 attempts per IP per minute.
    Route::post('login',            [AuthController::class, 'login'])
        ->middleware('throttle:farmer-login');

    // The password broker's own throttle is per-user, so it does nothing
    // against an attacker spraying many addresses — limit by IP as well.
    Route::post('password/forgot',  [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:farmer-password-forgot');

    Route::post('password/reset',   [AuthController::class, 'resetPassword']);

    // -------------------------------------------------------------------------
    // Authenticated, but not role-gated
    // -------------------------------------------------------------------------
    // UC-FAPI-03. Revoking your own token is token self-management, not a
    // farmer-scoped data operation. Gating it on the role would strand a
    // session whose role was changed or removed, with no way to sign out.
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
    });

    // -------------------------------------------------------------------------
    // Protected — must be an authenticated farmer with an active account
    // -------------------------------------------------------------------------
    // farmer-write is the extended role (UC-FAPI-01): it is granted in addition
    // to farmer, but accepted here too so an elevated account can never be
    // locked out by a role sync that drops the base role.
    Route::middleware(['auth:sanctum', 'role:farmer|farmer-write'])->group(function () {

        // Profile — REQ-F-FAPI-05
        Route::get ('profile', [ProfileController::class, 'show']);
        Route::put ('profile', [ProfileController::class, 'update']);

        // FCM device token — UC-FAPI-14. Belongs with the profile, not with
        // alerts: it writes to the farmer's own record, not to an alert.
        Route::post('device-token', [ProfileController::class, 'storeDeviceToken']);

        // Apiaries and their hives — apiary is the single physical-site model.
        Route::get('apiaries', [ApiaryController::class, 'index']);
        Route::get('apiaries/{apiaryId}/hives', [ApiaryController::class, 'hives']);

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

            // Hive position, taken from the phone at the hive. A write, so
            // it needs the extended farmer-write role.
            Route::put('location', [HiveLocationController::class, 'update'])->middleware('role:farmer-write');
        });

        // Alerts — REQ-F-FAPI-25, 26
        Route::get  ('alerts',                  [AlertController::class, 'index']);
        Route::patch('alerts/{alert_id}/read',  [AlertController::class, 'markRead']);

        // Farmer-to-admin messages — REQ-F-FAPI-31, 32
        Route::post('messages', [MessageController::class, 'store']);
        Route::get ('messages', [MessageController::class, 'index']);
    });
});
