<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;

// ============================================================
// PUBLIC ROUTES (no auth middleware)
// ============================================================
// Route::get('/', function () {
//     return view('welcome');
// });
Route::get('/', function () {
    return view('welcome');
});

Route::get('/', function () {
    return redirect()->route('admin.login');
});

// --- Auth: Login ---
Route::get('/admin/login',  [LoginController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [LoginController::class, 'login'])->name('admin.login.submit');

// --- Auth: Forgot Password ---
Route::get('/admin/forgot-password',  [ForgotPasswordController::class, 'showLinkRequestForm'])->name('admin.password.request');
Route::post('/admin/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('admin.password.email');

// --- Auth: Reset Password ---
Route::get('/admin/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('admin.password.reset');
Route::post('/admin/reset-password',        [ResetPasswordController::class, 'reset'])->name('admin.password.update');

// ============================================================
// AUTHENTICATED ROUTES — middleware: auth + EnsureNotFarmer
// ============================================================

Route::middleware(['auth', 'ensure.not.farmer'])->group(function () {

    // --- Logout ---
    Route::post('/admin/logout', [LoginController::class, 'logout'])->name('admin.logout');
    Route::post('/logout',       [LoginController::class, 'logout'])->name('logout'); // topbar alias

    // --- Dashboard ---
    Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    // --- Profile ---
    Route::get('/admin/profile',                  [ProfileController::class, 'show'])->name('admin.profile');
    Route::post('/admin/profile/update',          [ProfileController::class, 'update'])->name('admin.profile.update');
    Route::post('/admin/profile/change-password', [ProfileController::class, 'changePassword'])->name('admin.profile.change-password');

    // ============================================================
    // USER MANAGEMENT — additional middleware: permission:manage-users
    // ============================================================
    Route::middleware(['permission:manage-users'])->group(function () {
        Route::get('/admin/users',                [UserController::class, 'index'])->name('admin.users.index');
        Route::get('/admin/users/create',         [UserController::class, 'create'])->name('admin.users.create');
        Route::post('/admin/users',               [UserController::class, 'store'])->name('admin.users.store');
        Route::get('/admin/users/{id}/edit',      [UserController::class, 'edit'])->name('admin.users.edit');
        Route::post('/admin/users/{id}/update',   [UserController::class, 'update'])->name('admin.users.update');
        Route::post('/admin/users/{id}/activate', [UserController::class, 'activate'])->name('admin.users.activate');
        Route::post('/admin/users/{id}/suspend',  [UserController::class, 'suspend'])->name('admin.users.suspend');
        Route::post('/admin/users/{id}/delete',   [UserController::class, 'destroy'])->name('admin.users.delete');
    });

    // ============================================================
    // ROLE MANAGEMENT — additional middleware: permission:manage-roles
    // ============================================================
    Route::middleware(['permission:manage-roles'])->group(function () {
        Route::get('/admin/roles',                   [RoleController::class, 'index'])->name('admin.roles.index');
        Route::get('/admin/roles/create',            [RoleController::class, 'create'])->name('admin.roles.create');
        Route::post('/admin/roles',                  [RoleController::class, 'store'])->name('admin.roles.store');
        Route::get('/admin/roles/{id}/edit',         [RoleController::class, 'edit'])->name('admin.roles.edit');
        Route::post('/admin/roles/{id}/rename',      [RoleController::class, 'rename'])->name('admin.roles.rename');
        Route::post('/admin/roles/{id}/delete',      [RoleController::class, 'destroy'])->name('admin.roles.delete');
        Route::get('/admin/roles/{id}/permissions',  [RoleController::class, 'showPermissions'])->name('admin.roles.permissions');
        Route::post('/admin/roles/{id}/permissions', [RoleController::class, 'syncPermissions'])->name('admin.roles.permissions.sync');
    });

    // ============================================================
    // PLACEHOLDER ROUTES — sidebar links not yet implemented.
    // Each group carries the correct Spatie permission so that
    // when other developers replace placeholders with real
    // controllers the RBAC is already wired up.
    // ============================================================

    // Apiaries & Hives
    Route::middleware(['permission:manage-apiaries'])->group(function () {
        foreach (['apiaries.index', 'apiaries.create'] as $name) {
            Route::get('/admin/' . str_replace('.', '/', $name), function () use ($name) {
                return view('admin.placeholder', ['title' => ucwords(str_replace(['.', '-'], ' ', $name)), 'subtitle' => 'Placeholder for ' . $name]);
            })->name('admin.' . $name);
        }
    });

    Route::middleware(['permission:manage-hives'])->group(function () {
        foreach (['hives.index', 'hives.create', 'hives.map', 'inspections.index', 'harvests.index', 'alert-thresholds.index'] as $name) {
            Route::get('/admin/' . str_replace('.', '/', $name), function () use ($name) {
                return view('admin.placeholder', ['title' => ucwords(str_replace(['.', '-'], ' ', $name)), 'subtitle' => 'Placeholder for ' . $name]);
            })->name('admin.' . $name);
        }
    });

    // IoT Devices
    Route::middleware(['permission:manage-iot-devices'])->group(function () {
        foreach (['devices.index', 'devices.create', 'devices.fleet'] as $name) {
            Route::get('/admin/' . str_replace('.', '/', $name), function () use ($name) {
                return view('admin.placeholder', ['title' => ucwords(str_replace(['.', '-'], ' ', $name)), 'subtitle' => 'Placeholder for ' . $name]);
            })->name('admin.' . $name);
        }
    });

    // Sensor Monitoring
    Route::middleware(['permission:view-monitoring-dashboard'])->group(function () {
        foreach (['monitoring.temperature', 'monitoring.humidity', 'monitoring.weight', 'monitoring.co2', 'monitoring.audio', 'monitoring.video', 'monitoring.photos', 'alerts.index'] as $name) {
            Route::get('/admin/' . str_replace('.', '/', $name), function () use ($name) {
                return view('admin.placeholder', ['title' => ucwords(str_replace(['.', '-'], ' ', $name)), 'subtitle' => 'Placeholder for ' . $name]);
            })->name('admin.' . $name);
        }
    });

    // Anomaly Detection
    Route::middleware(['permission:view-anomaly-analytics'])->group(function () {
        foreach (['anomaly.dashboard', 'anomaly.analytics', 'anomaly.models'] as $name) {
            Route::get('/admin/' . str_replace('.', '/', $name), function () use ($name) {
                return view('admin.placeholder', ['title' => ucwords(str_replace(['.', '-'], ' ', $name)), 'subtitle' => 'Placeholder for ' . $name]);
            })->name('admin.' . $name);
        }
    });

    // Reports
    Route::middleware(['permission:generate-reports'])->group(function () {
        foreach (['reports.health', 'reports.production', 'reports.sensor-trends'] as $name) {
            Route::get('/admin/' . str_replace('.', '/', $name), function () use ($name) {
                return view('admin.placeholder', ['title' => ucwords(str_replace(['.', '-'], ' ', $name)), 'subtitle' => 'Placeholder for ' . $name]);
            })->name('admin.' . $name);
        }
    });

    // Farmer Management
    Route::middleware(['permission:manage-farmers'])->group(function () {
        foreach (['farmers.index', 'farmers.create'] as $name) {
            Route::get('/admin/' . str_replace('.', '/', $name), function () use ($name) {
                return view('admin.placeholder', ['title' => ucwords(str_replace(['.', '-'], ' ', $name)), 'subtitle' => 'Placeholder for ' . $name]);
            })->name('admin.' . $name);
        }
    });

    Route::middleware(['permission:approve-farmer-registrations'])->group(function () {
        Route::get('/admin/farmers/pending', function () {
            return view('admin.placeholder', ['title' => 'Pending Farmers', 'subtitle' => 'Placeholder for farmers.pending']);
        })->name('admin.farmers.pending');
    });

    Route::middleware(['permission:manage-farmer-messages'])->group(function () {
        Route::get('/admin/farmers/messages', function () {
            return view('admin.placeholder', ['title' => 'Farmer Messages', 'subtitle' => 'Placeholder for farmers.messages']);
        })->name('admin.farmers.messages');
    });

    // Newsletter
    Route::middleware(['permission:manage-newsletter'])->group(function () {
        foreach (['newsletter.index', 'newsletter.create'] as $name) {
            Route::get('/admin/' . str_replace('.', '/', $name), function () use ($name) {
                return view('admin.placeholder', ['title' => ucwords(str_replace(['.', '-'], ' ', $name)), 'subtitle' => 'Placeholder for ' . $name]);
            })->name('admin.' . $name);
        }
    });

    // Publications
    Route::middleware(['permission:manage-publications'])->group(function () {
        foreach (['publications.index', 'publications.create'] as $name) {
            Route::get('/admin/' . str_replace('.', '/', $name), function () use ($name) {
                return view('admin.placeholder', ['title' => ucwords(str_replace(['.', '-'], ' ', $name)), 'subtitle' => 'Placeholder for ' . $name]);
            })->name('admin.' . $name);
        }
    });

    // Events
    Route::middleware(['permission:manage-events'])->group(function () {
        foreach (['events.index', 'events.create'] as $name) {
            Route::get('/admin/' . str_replace('.', '/', $name), function () use ($name) {
                return view('admin.placeholder', ['title' => ucwords(str_replace(['.', '-'], ' ', $name)), 'subtitle' => 'Placeholder for ' . $name]);
            })->name('admin.' . $name);
        }
    });

    // Gallery
    Route::middleware(['permission:manage-gallery'])->group(function () {
        foreach (['gallery.index', 'gallery.create'] as $name) {
            Route::get('/admin/' . str_replace('.', '/', $name), function () use ($name) {
                return view('admin.placeholder', ['title' => ucwords(str_replace(['.', '-'], ' ', $name)), 'subtitle' => 'Placeholder for ' . $name]);
            })->name('admin.' . $name);
        }
    });

    // Scholarships
    Route::middleware(['permission:manage-scholarship'])->group(function () {
        foreach (['scholarship.index', 'scholarship.create'] as $name) {
            Route::get('/admin/' . str_replace('.', '/', $name), function () use ($name) {
                return view('admin.placeholder', ['title' => ucwords(str_replace(['.', '-'], ' ', $name)), 'subtitle' => 'Placeholder for ' . $name]);
            })->name('admin.' . $name);
        }
    });

    // Team Profiles
    Route::middleware(['permission:manage-team-profiles'])->group(function () {
        foreach (['team.index', 'team.create'] as $name) {
            Route::get('/admin/' . str_replace('.', '/', $name), function () use ($name) {
                return view('admin.placeholder', ['title' => ucwords(str_replace(['.', '-'], ' ', $name)), 'subtitle' => 'Placeholder for ' . $name]);
            })->name('admin.' . $name);
        }
    });

    // Work Packages
    Route::middleware(['permission:manage-work-packages'])->group(function () {
        foreach (['workpackages.index', 'workpackages.create'] as $name) {
            Route::get('/admin/' . str_replace('.', '/', $name), function () use ($name) {
                return view('admin.placeholder', ['title' => ucwords(str_replace(['.', '-'], ' ', $name)), 'subtitle' => 'Placeholder for ' . $name]);
            })->name('admin.' . $name);
        }
    });

    // Feedback
    Route::middleware(['permission:manage-feedback'])->group(function () {
        Route::get('/admin/feedback/index', function () {
            return view('admin.placeholder', ['title' => 'Feedback', 'subtitle' => 'Placeholder for feedback.index']);
        })->name('admin.feedback.index');
    });

    // Search — any authenticated non-farmer user
    Route::get('/admin/search', function () {
        return view('admin.placeholder', ['title' => 'Search', 'subtitle' => 'Placeholder for search']);
    })->name('admin.search');

});
