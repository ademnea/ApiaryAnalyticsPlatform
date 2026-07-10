<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\IotDeviceRegistryController;
use App\Http\Controllers\Admin\IotHardwareTeamRegistryController;
use App\Http\Controllers\Admin\IotHardwareTeamMemberController;
use Illuminate\Http\Request;

// Route::get('/', function () {
//     return view('welcome');
// });



// In routes/web.php, outside any middleware group
Route::get('login', fn() => redirect()->route('admin.login'))->name('login');
// ------ Admin auth + dashboard routes
Route::get('/admin/login', [LoginController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [LoginController::class, 'login'])->name('admin.login.submit');

// Minimal password-reset request flow for admin (placeholder)
Route::get('/admin/password/reset', function () {
    return view('auth.passwords.email');
})->name('admin.password.request');

Route::post('/admin/password/email', function (Request $request) {
    $request->validate(['email' => ['required', 'email']]);

    // Minimal behaviour: do not send mail here — show generic success message
    return back()->with('status', 'If an account with that email exists, a password reset link has been sent.');
})->name('admin.password.email');

// Route::prefix('admin')
//     ->name('admin.')->middleware(['auth'])->group(function () {
        Route::prefix('admin')
    ->name('admin.')->group(function () {

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    // Logout (admin and generic alias)
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');
  

    // Admin profile (placeholder view)
    Route::get('profile', function () {
        return view('admin.profile');
    })->name('profile');

    
    
    // ---- Placeholder routes for sidebar and dashboard links ----
    $placeholders = [
        // Apiaries & Hives
        'apiaries.index','apiaries.create',
        'hives.index','hives.create','hives.map','inspections.index','harvests.index','alert-thresholds.index',

        // IoT & Monitoring
        'devices.index','devices.create','devices.fleet',
        'monitoring.temperature','monitoring.humidity','monitoring.weight','monitoring.co2','monitoring.audio','monitoring.video','monitoring.photos',

        // Anomaly
        'anomaly.dashboard','anomaly.analytics','anomaly.models',

        // Reports
        'reports.health','reports.production','reports.sensor-trends',

        // Alerts
        'alerts.index',

        // Communication
        'feedback.index',

        // Farmers
        'farmers.index','farmers.create','farmers.pending','farmers.messages',

        // System: users & roles
        'users.index','users.create','roles.index','roles.create',

        // Website content
        'newsletter.index','newsletter.create',
        'publications.index','publications.create',
        'events.index','events.create',
        'gallery.index','gallery.create',
        'scholarship.index','scholarship.create',
        'team.index','team.create',

        // Work packages
        'workpackages.index','workpackages.create',

        // Generic search
        'search',
    ];

   
    foreach ($placeholders as $name) {
            $routeName = $name;                              // group adds 'admin.'
            $uri = '/' . str_replace('.', '/', $name);      // group adds '/admin'

            Route::get($uri, function () use ($name) {
                $title = ucwords(str_replace(['.', '-'], [' ', ' '], $name));
                return view('admin.placeholder', ['title' => $title, 'subtitle' => 'Placeholder for ' . $title]);
            })->name($routeName);
        }

    
    // Hardware teams (never hard/soft deleted — deactivate only)
    Route::resource('hardware-teams', IotHardwareTeamRegistryController::class)->except(['destroy']);
    Route::patch('hardware-teams/{hardwareTeam}/deactivate', [IotHardwareTeamRegistryController::class, 'deactivate'])
        ->name('hardware-teams.deactivate');
    Route::patch('hardware-teams/{hardwareTeam}/reactivate', [IotHardwareTeamRegistryController::class, 'reactivate'])
        ->name('hardware-teams.reactivate');

    // Team members (nested under a team)
    Route::prefix('hardware-teams/{hardwareTeam}/members')->name('hardware-teams.members.')->group(function () {
        Route::get('create', [IotHardwareTeamMemberController::class, 'create'])->name('create');
        Route::post('/', [IotHardwareTeamMemberController::class, 'store'])->name('store');
        Route::get('{member}/edit', [IotHardwareTeamMemberController::class, 'edit'])->name('edit');
        Route::put('{member}', [IotHardwareTeamMemberController::class, 'update'])->name('update');
        Route::patch('{member}/deactivate', [IotHardwareTeamMemberController::class, 'deactivate'])->name('deactivate');
        Route::patch('{member}/reactivate', [IotHardwareTeamMemberController::class, 'reactivate'])->name('reactivate');
    });

    // Devices scoped to a team (the "Add Device" flow from a team page)
    Route::prefix('hardware-teams/{hardwareTeam}/devices')->name('hardware-teams.devices.')->group(function () {
        Route::get('/', [IotDeviceRegistryController::class, 'indexForTeam'])->name('index');
        Route::get('create', [IotDeviceRegistryController::class, 'createForTeam'])->name('create');
        Route::post('/', [IotDeviceRegistryController::class, 'storeForTeam'])->name('store');
    });

    // Global IoT device registry
    Route::resource('iot-devices', IotDeviceRegistryController::class);
    Route::patch('iot-devices/{iotDevice}/revoke', [IotDeviceRegistryController::class, 'revoke'])
        ->name('iot-devices.revoke');
    Route::patch('iot-devices/{iotDevice}/reactivate', [IotDeviceRegistryController::class, 'reactivate'])
        ->name('iot-devices.reactivate');

    // Device-to-hive assignment wizard
    Route::get('iot-devices/{iotDevice}/assign', [IotDeviceRegistryController::class, 'assignForm'])
        ->name('iot-devices.assign.form');
    Route::get('iot-devices/{iotDevice}/assign/hives', [IotDeviceRegistryController::class, 'assignHives'])
        ->name('iot-devices.assign.hives');
    Route::post('iot-devices/{iotDevice}/assign', [IotDeviceRegistryController::class, 'assign'])
        ->name('iot-devices.assign.store');
    Route::patch('iot-devices/{iotDevice}/unassign', [IotDeviceRegistryController::class, 'unassign'])
        ->name('iot-devices.unassign');
});










Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {


});
