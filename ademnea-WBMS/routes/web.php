<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Http\Request;

// Route::get('/', function () {
//     return view('welcome');
// });

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

Route::middleware(['web','auth'])->group(function () {
    // Logout (admin and generic alias)
    Route::post('/admin/logout', [LoginController::class, 'logout'])->name('admin.logout');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Admin profile (placeholder view)
    Route::get('/admin/profile', function () {
        return view('admin.profile');
    })->name('admin.profile');

    Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    
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
        $routeName = 'admin.' . $name;
        $uri = '/admin/' . str_replace('.', '/', $name);

        Route::get($uri, function () use ($name) {
            $title = ucwords(str_replace(['.', '-'], [' ', ' '], $name));
            return view('admin.placeholder', ['title' => $title, 'subtitle' => 'Placeholder for ' . $title]);
        })->name($routeName);
    }
});
