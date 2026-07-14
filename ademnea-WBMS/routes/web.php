<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ApiaryManagement\HiveController;
use App\Http\Controllers\Admin\ApiaryManagement\ApiaryController;
use App\Http\Controllers\Admin\ApiaryManagement\FarmerController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\GalleryController as AdminGalleryController;
use App\Http\Controllers\Admin\ScholarshipController as AdminScholarshipController;
use App\Http\Controllers\Admin\FeedbackController as AdminFeedbackController;
use App\Http\Controllers\Public\GalleryController as PublicGalleryController;
use App\Http\Controllers\Public\ScholarshipController as PublicScholarshipController;
use App\Http\Controllers\Public\FeedbackController as PublicFeedbackController;

// ============================================================
// PUBLIC ROUTES (no auth middleware)
// ============================================================

Route::get('/', function () {
    return redirect()->route('admin.login');
});

// --- Public Gallery Routes ---
Route::get('/gallery', [PublicGalleryController::class, 'index'])->name('public.gallery.index');
Route::get('/gallery/{gallery:slug}', [PublicGalleryController::class, 'show'])->name('public.gallery.show');

// --- Public Scholarship Routes ---
Route::get('/scholarships', [PublicScholarshipController::class, 'index'])->name('public.scholarships.index');
Route::get('/scholarships/{scholarship}', [PublicScholarshipController::class, 'show'])->name('public.scholarships.show');

// --- Public Feedback Routes ---
Route::get('/feedback', [PublicFeedbackController::class, 'create'])->name('public.feedback.create');
Route::post('/feedback', [PublicFeedbackController::class, 'store'])->name('public.feedback.store');
Route::get('/feedback/success', [PublicFeedbackController::class, 'success'])->name('public.feedback.success');

// --- Auth: Login ---
Route::get('/admin/login', [LoginController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [LoginController::class, 'login'])->name('admin.login.submit');

Route::get('/login', function () {
    return redirect()->route('admin.login');
})->name('login');

// --- Auth: Forgot Password ---
Route::get('/admin/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('admin.password.request');
Route::post('/admin/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('admin.password.email');

// --- Auth: Reset Password ---
Route::get('/admin/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('admin.password.reset');
Route::post('/admin/reset-password', [ResetPasswordController::class, 'reset'])->name('admin.password.update');

// ============================================================
// AUTHENTICATED ROUTES — middleware: auth + EnsureNotFarmer
// ============================================================

Route::middleware(['auth', 'ensure.not.farmer'])->group(function () {

    // --- Logout ---
    Route::post('/admin/logout', [LoginController::class, 'logout'])->name('admin.logout');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // --- Dashboard ---
    Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    // --- Profile ---
    Route::get('/admin/profile', [ProfileController::class, 'show'])->name('admin.profile');
    Route::post('/admin/profile/update', [ProfileController::class, 'update'])->name('admin.profile.update');
    Route::post('/admin/profile/change-password', [ProfileController::class, 'changePassword'])->name('admin.profile.change-password');

    // ============================================================
    // USER MANAGEMENT — additional middleware: permission:manage-users
    // ============================================================
    Route::middleware(['permission:manage-users'])->group(function () {
        Route::get('/admin/users', [UserController::class, 'index'])->name('admin.users.index');
        Route::get('/admin/users/create', [UserController::class, 'create'])->name('admin.users.create');
        Route::post('/admin/users', [UserController::class, 'store'])->name('admin.users.store');
        Route::get('/admin/users/{id}/edit', [UserController::class, 'edit'])->name('admin.users.edit');
        Route::post('/admin/users/{id}/update', [UserController::class, 'update'])->name('admin.users.update');
        Route::post('/admin/users/{id}/activate', [UserController::class, 'activate'])->name('admin.users.activate');
        Route::post('/admin/users/{id}/suspend', [UserController::class, 'suspend'])->name('admin.users.suspend');
        Route::post('/admin/users/{id}/delete', [UserController::class, 'destroy'])->name('admin.users.delete');
    });

    // ============================================================
    // ROLE MANAGEMENT — additional middleware: permission:manage-roles
    // ============================================================
    Route::middleware(['permission:manage-roles'])->group(function () {
        Route::get('/admin/roles', [RoleController::class, 'index'])->name('admin.roles.index');
        Route::get('/admin/roles/create', [RoleController::class, 'create'])->name('admin.roles.create');
        Route::post('/admin/roles', [RoleController::class, 'store'])->name('admin.roles.store');
        Route::get('/admin/roles/{id}/edit', [RoleController::class, 'edit'])->name('admin.roles.edit');
        Route::post('/admin/roles/{id}/rename', [RoleController::class, 'rename'])->name('admin.roles.rename');
        Route::post('/admin/roles/{id}/delete', [RoleController::class, 'destroy'])->name('admin.roles.delete');
        Route::get('/admin/roles/{id}/permissions', [RoleController::class, 'showPermissions'])->name('admin.roles.permissions');
        Route::post('/admin/roles/{id}/permissions', [RoleController::class, 'syncPermissions'])->name('admin.roles.permissions.sync');
    });

    // ============================================================
    // GALLERY MODULE
    // ============================================================
    Route::middleware(['permission:manage-gallery'])->group(function () {
        Route::prefix('/admin/gallery')->name('admin.gallery.')->group(function () {
            Route::get('/', [AdminGalleryController::class, 'index'])->name('index');
            Route::get('/create', [AdminGalleryController::class, 'create'])->name('create');
            Route::post('/', [AdminGalleryController::class, 'store'])->name('store');
            Route::get('/{gallery}/edit', [AdminGalleryController::class, 'edit'])->name('edit');
            Route::put('/{gallery}', [AdminGalleryController::class, 'update'])->name('update');
            Route::delete('/{gallery}', [AdminGalleryController::class, 'destroy'])->name('destroy');
            Route::post('/images/{image}/replace', [AdminGalleryController::class, 'replaceImage'])->name('images.replace');
            Route::delete('/images/{image}', [AdminGalleryController::class, 'deleteImage'])->name('images.delete');
        });
    });

    // ============================================================
    // SCHOLARSHIP MODULE
    // ============================================================
    Route::middleware(['permission:manage-scholarship'])->group(function () {
        Route::prefix('/admin/scholarships')->name('admin.scholarship.')->group(function () {
            Route::get('/', [AdminScholarshipController::class, 'index'])->name('index');
            Route::get('/create', [AdminScholarshipController::class, 'create'])->name('create');
            Route::post('/', [AdminScholarshipController::class, 'store'])->name('store');
            Route::get('/{scholarship}', [AdminScholarshipController::class, 'show'])->name('show');
            Route::get('/{scholarship}/edit', [AdminScholarshipController::class, 'edit'])->name('edit');
            Route::put('/{scholarship}', [AdminScholarshipController::class, 'update'])->name('update');
            Route::delete('/{scholarship}', [AdminScholarshipController::class, 'destroy'])->name('destroy');
        });
    });

    // ============================================================
    // FEEDBACK MODULE
    // ============================================================
    Route::middleware(['permission:manage-feedback'])->group(function () {
        Route::prefix('/admin/feedback')->name('admin.feedback.')->group(function () {
            Route::get('/', [AdminFeedbackController::class, 'index'])->name('index');
            Route::get('/{feedback}', [AdminFeedbackController::class, 'show'])->name('show');
            Route::put('/{feedback}', [AdminFeedbackController::class, 'update'])->name('update');
            Route::delete('/{feedback}', [AdminFeedbackController::class, 'destroy'])->name('destroy');
        });
    });

    // ============================================================
    // PLACEHOLDER ROUTES — sidebar links not yet implemented.
    // Each group carries the correct Spatie permission so that
    // when other developers replace placeholders with real
    // controllers the RBAC is already wired up.
    // APIARY MANAGEMENT MODULE (SDD §4.2.4)
    // ============================================================

    // Farmers
    Route::middleware(['permission:manage-farmers'])->group(function () {
        Route::get('/admin/farmers', [FarmerController::class, 'index'])->name('admin.farmers.index');
        Route::get('/admin/farmers/create', [FarmerController::class, 'create'])->name('admin.farmers.create');
        Route::post('/admin/farmers', [FarmerController::class, 'store'])->name('admin.farmers.store');
        Route::get('/admin/farmers/pending', function () {
            return view('admin.placeholder', ['title' => 'Pending Farmers', 'subtitle' => 'Placeholder for farmers.pending']);
        })->name('admin.farmers.pending');
        Route::get('/admin/farmers/messages', function () {
            return view('admin.placeholder', ['title' => 'Farmer Messages', 'subtitle' => 'Placeholder for farmers.messages']);
        })->name('admin.farmers.messages');
        Route::get('/admin/farmers/{farmer}', [FarmerController::class, 'show'])->name('admin.farmers.show');
        Route::get('/admin/farmers/{farmer}/edit', [FarmerController::class, 'edit'])->name('admin.farmers.edit');
        Route::put('/admin/farmers/{farmer}', [FarmerController::class, 'update'])->name('admin.farmers.update');
        Route::delete('/admin/farmers/{farmer}', [FarmerController::class, 'destroy'])->name('admin.farmers.destroy');
        Route::patch('/admin/farmers/{farmer}/restore', [FarmerController::class, 'restore'])
            ->name('admin.farmers.restore')
            ->withTrashed();
    });

    // Apiaries
    Route::middleware(['permission:manage-apiaries'])->group(function () {
        Route::get('/admin/apiaries', [ApiaryController::class, 'index'])->name('admin.apiaries.index');
        Route::get('/admin/apiaries/create', [ApiaryController::class, 'create'])->name('admin.apiaries.create');
        Route::post('/admin/apiaries', [ApiaryController::class, 'store'])->name('admin.apiaries.store');
        Route::get('/admin/apiaries/{apiary}', [ApiaryController::class, 'show'])->name('admin.apiaries.show');
        Route::get('/admin/apiaries/{apiary}/edit', [ApiaryController::class, 'edit'])->name('admin.apiaries.edit');
        Route::put('/admin/apiaries/{apiary}', [ApiaryController::class, 'update'])->name('admin.apiaries.update');
        Route::delete('/admin/apiaries/{apiary}', [ApiaryController::class, 'destroy'])->name('admin.apiaries.destroy');
        Route::patch('/admin/apiaries/{apiary}/restore', [ApiaryController::class, 'restore'])
            ->name('admin.apiaries.restore')
            ->withTrashed();
        Route::patch('/admin/apiaries/{apiary}/deactivate', [ApiaryController::class, 'deactivate'])
            ->name('admin.apiaries.deactivate');
    });

    // Hives
    Route::middleware(['permission:manage-hives'])->group(function () {
        Route::get('/admin/hives', [HiveController::class, 'index'])->name('admin.hives.index');
        Route::get('/admin/hives/create', [HiveController::class, 'create'])
            ->name('admin.hives.create');
        Route::post('/admin/hives', [HiveController::class, 'store'])
            ->name('admin.hives.store');
        Route::get('/admin/hives/{hive}', [HiveController::class, 'show'])->name('admin.hives.show');
        Route::get('/admin/hives/{hive}/edit', [HiveController::class, 'edit'])->name('admin.hives.edit');
        Route::put('/admin/hives/{hive}', [HiveController::class, 'update'])->name('admin.hives.update');
        Route::patch('/admin/hives/{hive}/status', [HiveController::class, 'updateStatus'])
            ->name('admin.hives.updateStatus');
        Route::delete('/admin/hives/{hive}', [HiveController::class, 'destroy'])->name('admin.hives.destroy');

        foreach (['hives.map', 'inspections.index', 'harvests.index', 'alert-thresholds.index'] as $name) {
            Route::get('/admin/' . str_replace('.', '/', $name), function () use ($name) {
                return view('admin.placeholder', ['title' => ucwords(str_replace(['.', '-'], ' ', $name)), 'subtitle' => 'Placeholder for ' . $name]);
            })->name('admin.' . $name);
        }
    });

    // ============================================================
    // PLACEHOLDER ROUTES — sidebar links not yet implemented.
    // Each group carries the correct Spatie permission so that
    // when other developers replace placeholders with real
    // controllers the RBAC is already wired up.
    // ============================================================

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

    // Farmer Management (non-CRUD placeholders)
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

    // Search — any authenticated non-farmer user
    Route::get('/admin/search', function () {
        return view('admin.placeholder', ['title' => 'Search', 'subtitle' => 'Placeholder for search']);
    })->name('admin.search');

});
