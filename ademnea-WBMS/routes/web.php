<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\IotDeviceRegistryController;
use App\Http\Controllers\Admin\IotHardwareTeamRegistryController;
use App\Http\Controllers\Admin\IotHardwareTeamMemberController;
use App\Http\Controllers\Admin\AnomalyDashboardController;
use App\Http\Controllers\Admin\AnomalyAnalyticsController;
use App\Http\Controllers\Admin\AnomalyDeviceDetailController;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\ApiaryManagement\HiveController;
use App\Http\Controllers\Admin\ApiaryManagement\HiveMapController;
use App\Http\Controllers\Admin\ApiaryManagement\ApiaryController;
use App\Http\Controllers\Admin\ApiaryManagement\FarmerController;
use App\Http\Controllers\Admin\ApiaryManagement\AlertThresholdController;
use App\Http\Controllers\Admin\ApiaryManagement\InspectionController;
use App\Http\Controllers\Admin\ApiaryManagement\HarvestController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\GalleryController as AdminGalleryController;
use App\Http\Controllers\Admin\ScholarshipController as AdminScholarshipController;
use App\Http\Controllers\Admin\FeedbackController as AdminFeedbackController;
use App\Http\Controllers\Public\GalleryController as PublicGalleryController;
use App\Http\Controllers\Public\ScholarshipController as PublicScholarshipController;
use App\Http\Controllers\Public\FeedbackController as PublicFeedbackController;
use App\Http\Controllers\Admin\WorkPackageController as AdminWorkPackageController;
use App\Http\Controllers\Admin\TeamProfileController as AdminTeamProfileController;

use App\Http\Controllers\Public\WorkPackageController as PublicWorkPackageController;
use App\Http\Controllers\Public\TeamProfileController as PublicTeamProfileController;
// ============================================================
// PUBLIC ROUTES (no auth middleware)
// ============================================================

Route::get('/', function () {
    return view('welcome');
})->name('home');

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

// ============================================================
// PUBLIC INFORMATION MANAGEMENT
// ============================================================

// Public Work Packages
Route::get('/work-packages',[PublicWorkPackageController::class, 'index'])->name('public.work-packages.index');
Route::get('/work-packages/{workPackage}',[PublicWorkPackageController::class, 'show'])->name('public.work-packages.show');


// Public Team Profiles
Route::get('/team',[PublicTeamProfileController::class, 'index'])->name('public.team.index');
Route::get('/team/{teamProfile}',[PublicTeamProfileController::class, 'show'])->name('public.team.show');

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
            Route::get('/{gallery}', [AdminGalleryController::class, 'show'])
                ->name('show');
            Route::get('/{gallery}/edit', [AdminGalleryController::class, 'edit'])->name('edit');
            Route::put('/{gallery}', [AdminGalleryController::class, 'update'])->name('update');
            Route::delete('/{gallery}', [AdminGalleryController::class, 'destroy'])->name('destroy');
            Route::post('/images/{image}/replace', [AdminGalleryController::class, 'replaceImage'])->name('images.replace');
            Route::delete('/images/{image}', [AdminGalleryController::class, 'deleteImage'])->name('images.delete');
            Route::post('/{gallery}/reorder', [AdminGalleryController::class, 'reorder'])->name('reorder');
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
    // PUBLIC INFORMATION MANAGEMENT
    // ============================================================

    /*
    |--------------------------------------------------------------------------
    | WORK PACKAGE MANAGEMENT — permission: manage-work-packages
    |--------------------------------------------------------------------------
    */
    Route::middleware(['permission:manage-work-packages'])->group(function () {
        Route::prefix('/admin/work-packages')
            ->name('admin.work-packages.')
            ->group(function () {
                Route::get('/', [AdminWorkPackageController::class, 'index'])->name('index');
                Route::get('/create', [AdminWorkPackageController::class, 'create'])->name('create');
                Route::post('/', [AdminWorkPackageController::class, 'store'])->name('store');
                Route::get('/{workPackage}', [AdminWorkPackageController::class, 'show'])->name('show');
                Route::get('/{workPackage}/edit', [AdminWorkPackageController::class, 'edit'])->name('edit');
                Route::put('/{workPackage}', [AdminWorkPackageController::class, 'update'])->name('update');
                Route::delete('/{workPackage}', [AdminWorkPackageController::class, 'destroy'])->name('destroy');
            });
    });

    /*
    |--------------------------------------------------------------------------
    | TEAM PROFILE MANAGEMENT — permission: manage-team-profiles
    |--------------------------------------------------------------------------
    */
    Route::middleware(['permission:manage-team-profiles'])->group(function () {
        Route::prefix('/admin/team-profiles')
            ->name('admin.team-profiles.')
            ->group(function () {
                Route::get('/', [AdminTeamProfileController::class, 'index'])->name('index');
                Route::get('/create', [AdminTeamProfileController::class, 'create'])->name('create');
                Route::post('/', [AdminTeamProfileController::class, 'store'])->name('store');
                Route::get('/{teamProfile}', [AdminTeamProfileController::class, 'show'])->name('show');
                Route::get('/{teamProfile}/edit', [AdminTeamProfileController::class, 'edit'])->name('edit');
                Route::put('/{teamProfile}', [AdminTeamProfileController::class, 'update'])->name('update');
                Route::delete('/{teamProfile}', [AdminTeamProfileController::class, 'destroy'])->name('destroy');
            });
    });

    // ============================================================
    // APIARY MANAGEMENT MODULE (SDD §4.2.4)
    // ============================================================

    // Farmers
    Route::middleware(['permission:manage-farmers'])->group(function () {
        Route::get('/admin/farmers', [FarmerController::class, 'index'])->name('admin.farmers.index');
        Route::get('/admin/farmers/create', [FarmerController::class, 'create'])->name('admin.farmers.create');
        Route::post('/admin/farmers', [FarmerController::class, 'store'])->name('admin.farmers.store');
        Route::get('/admin/farmers/pending', [FarmerController::class, 'pending'])->name('admin.farmers.pending');
        Route::get('/admin/farmers/messages', [FarmerController::class, 'messages'])->name('admin.farmers.messages');
        Route::get('/admin/farmers/messages/{farmerMessage}', [FarmerController::class, 'showMessage'])->name('admin.farmers.messages.show');
        Route::patch('/admin/farmers/messages/{farmerMessage}/resolve', [FarmerController::class, 'resolveMessage'])->name('admin.farmers.messages.resolve');
        Route::post('/admin/farmers/{farmer}/approve', [FarmerController::class, 'approve'])->name('admin.farmers.approve');
        Route::post('/admin/farmers/{farmer}/reject', [FarmerController::class, 'reject'])->name('admin.farmers.reject');
        Route::get('/admin/farmers/{farmer}', [FarmerController::class, 'show'])->name('admin.farmers.show');
        Route::get('/admin/farmers/{farmer}/edit', [FarmerController::class, 'edit'])->name('admin.farmers.edit');
        Route::put('/admin/farmers/{farmer}', [FarmerController::class, 'update'])->name('admin.farmers.update');
        Route::delete('/admin/farmers/{farmer}', [FarmerController::class, 'destroy'])->name('admin.farmers.destroy');
        Route::patch('/admin/farmers/{farmer}/restore', [FarmerController::class, 'restore'])
            ->name('admin.farmers.restore')
            ->withTrashed();
    });

    // Apiaries — read-only (view-hive-data or manage-apiaries)
    Route::middleware(['permission:view-hive-data|manage-apiaries'])->group(function () {
        Route::get('/admin/apiaries', [ApiaryController::class, 'index'])->name('admin.apiaries.index');

        // Static paths before wildcard.
        Route::get('/admin/apiaries/create', [ApiaryController::class, 'create'])
            ->name('admin.apiaries.create')
            ->can('manage-apiaries');

   //=================================================================
   //IOT device registray,teammanagement and ingetion module
   //================================================================= 
    // Hardware teams (never hard/soft deleted — deactivate only)
     Route::prefix('admin')->name('admin.')->group(function () {
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

});
//===========================
 // ENDS HERE
//============================================================









Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {

        Route::get('/admin/apiaries/{apiary}', [ApiaryController::class, 'show'])->name('admin.apiaries.show');
    });

    // Apiaries — write access (manage-apiaries only)
    Route::middleware(['permission:manage-apiaries'])->group(function () {
        Route::post('/admin/apiaries', [ApiaryController::class, 'store'])->name('admin.apiaries.store');
        Route::get('/admin/apiaries/{apiary}/edit', [ApiaryController::class, 'edit'])->name('admin.apiaries.edit');
        Route::put('/admin/apiaries/{apiary}', [ApiaryController::class, 'update'])->name('admin.apiaries.update');
        Route::delete('/admin/apiaries/{apiary}', [ApiaryController::class, 'destroy'])->name('admin.apiaries.destroy');
        Route::patch('/admin/apiaries/{apiary}/restore', [ApiaryController::class, 'restore'])
            ->name('admin.apiaries.restore')
            ->withTrashed();
        Route::patch('/admin/apiaries/{apiary}/deactivate', [ApiaryController::class, 'deactivate'])
            ->name('admin.apiaries.deactivate');
    });

    // Hives — read-only access (view-hive-data or manage-hives)
    Route::middleware(['permission:view-hive-data|manage-hives'])->group(function () {
        Route::get('/admin/hives', [HiveController::class, 'index'])->name('admin.hives.index');

        // Static paths before wildcard
        Route::get('/admin/hives/create', [HiveController::class, 'create'])
            ->name('admin.hives.create')
            ->can('manage-hives');

        Route::get('/admin/hives/map', [HiveMapController::class, 'map'])
            ->name('admin.hives.map');

        Route::get('/admin/hives/map-data', [HiveMapController::class, 'index'])
            ->name('admin.hives.map-data');

        // Alert Thresholds — read-only
        Route::get('/admin/alert-thresholds', [AlertThresholdController::class, 'index'])->name('admin.alert-thresholds.index');
        Route::get('/admin/alert-thresholds/create', [AlertThresholdController::class, 'create'])
            ->name('admin.alert-thresholds.create')
            ->can('manage-hives');
        Route::get('/admin/alert-thresholds/{alertThreshold}', [AlertThresholdController::class, 'edit'])
            ->name('admin.alert-thresholds.edit');

        // Wildcard last
        Route::get('/admin/hives/{hive}', [HiveController::class, 'show'])->name('admin.hives.show');
    });

    // Inspections — read access is shared with data viewers; writes require the
    // dedicated inspection permission.
    Route::middleware(['permission:view-hive-data|manage-inspections'])->group(function () {
        Route::get('/admin/inspections', [InspectionController::class, 'index'])->name('admin.inspections.index');
        Route::get('/admin/inspections/create', [InspectionController::class, 'create'])
            ->name('admin.inspections.create')
            ->can('manage-inspections');
        Route::get('/admin/inspections/{inspection}', [InspectionController::class, 'show'])->name('admin.inspections.show');
    });
    Route::middleware(['permission:manage-inspections'])->group(function () {
        Route::get('/admin/inspections/{inspection}/edit', [InspectionController::class, 'edit'])->name('admin.inspections.edit');
        Route::post('/admin/inspections', [InspectionController::class, 'store'])->name('admin.inspections.store');
        Route::put('/admin/inspections/{inspection}', [InspectionController::class, 'update'])->name('admin.inspections.update');
        Route::delete('/admin/inspections/{inspection}', [InspectionController::class, 'destroy'])->name('admin.inspections.destroy');
    });

    // Harvests — read access is shared with data viewers; writes require the
    // dedicated harvest permission.
    Route::middleware(['permission:view-hive-data|manage-harvests'])->group(function () {
        Route::get('/admin/harvests', [HarvestController::class, 'index'])->name('admin.harvests.index');
        Route::get('/admin/harvests/create', [HarvestController::class, 'create'])
            ->name('admin.harvests.create')
            ->can('manage-harvests');
        Route::get('/admin/harvests/{harvest}', [HarvestController::class, 'show'])->name('admin.harvests.show');
    });
    Route::middleware(['permission:manage-harvests'])->group(function () {
        Route::get('/admin/harvests/{harvest}/edit', [HarvestController::class, 'edit'])->name('admin.harvests.edit');
        Route::post('/admin/harvests', [HarvestController::class, 'store'])->name('admin.harvests.store');
        Route::put('/admin/harvests/{harvest}', [HarvestController::class, 'update'])->name('admin.harvests.update');
        Route::delete('/admin/harvests/{harvest}', [HarvestController::class, 'destroy'])->name('admin.harvests.destroy');
    });

    // Hives — write access (manage-hives only)
    Route::middleware(['permission:manage-hives'])->group(function () {
        Route::post('/admin/alert-thresholds', [AlertThresholdController::class, 'store'])->name('admin.alert-thresholds.store');
        Route::put('/admin/alert-thresholds/{alertThreshold}', [AlertThresholdController::class, 'update'])->name('admin.alert-thresholds.update');
        Route::delete('/admin/alert-thresholds/{alertThreshold}', [AlertThresholdController::class, 'destroy'])->name('admin.alert-thresholds.destroy');
    });

    // Hives — write access (manage-hives only)
    Route::middleware(['permission:manage-hives'])->group(function () {
        Route::post('/admin/hives', [HiveController::class, 'store'])->name('admin.hives.store');
        Route::get('/admin/hives/{hive}/edit', [HiveController::class, 'edit'])->name('admin.hives.edit');
        Route::put('/admin/hives/{hive}', [HiveController::class, 'update'])->name('admin.hives.update');
        Route::patch('/admin/hives/{hive}/status', [HiveController::class, 'updateStatus'])
            ->name('admin.hives.updateStatus');
        Route::delete('/admin/hives/{hive}', [HiveController::class, 'destroy'])->name('admin.hives.destroy');
    });

    // ============================================================
    // IOT DEVICE REGISTRY, TEAM MANAGEMENT, AND INGESTION MODULE
    // ============================================================
    Route::middleware(['permission:manage-iot-devices'])->prefix('/admin/iot')->name('admin.')->group(function () {

        // --- Hardware Teams (never hard/soft deleted — deactivate only) ---
        Route::prefix('hardware-teams')->name('hardware-teams.')->group(function () {
            Route::get('/', [IotHardwareTeamRegistryController::class, 'index'])->name('index');
            Route::get('/create', [IotHardwareTeamRegistryController::class, 'create'])->name('create');
            Route::post('/', [IotHardwareTeamRegistryController::class, 'store'])->name('store');
            Route::get('/{hardwareTeam}', [IotHardwareTeamRegistryController::class, 'show'])->name('show');
            Route::get('/{hardwareTeam}/edit', [IotHardwareTeamRegistryController::class, 'edit'])->name('edit');
            Route::put('/{hardwareTeam}', [IotHardwareTeamRegistryController::class, 'update'])->name('update');
            Route::patch('/{hardwareTeam}/deactivate', [IotHardwareTeamRegistryController::class, 'deactivate'])->name('deactivate');
            Route::patch('/{hardwareTeam}/reactivate', [IotHardwareTeamRegistryController::class, 'reactivate'])->name('reactivate');

            // --- Team Members (nested under a team) ---
            Route::prefix('/{hardwareTeam}/members')->name('members.')->group(function () {
                Route::get('/', [IotHardwareTeamMemberController::class, 'index'])->name('index');
                Route::get('/create', [IotHardwareTeamMemberController::class, 'create'])->name('create');
                Route::post('/', [IotHardwareTeamMemberController::class, 'store'])->name('store');
                Route::get('/{member}/edit', [IotHardwareTeamMemberController::class, 'edit'])->name('edit');
                Route::put('/{member}', [IotHardwareTeamMemberController::class, 'update'])->name('update');
                Route::patch('/{member}/deactivate', [IotHardwareTeamMemberController::class, 'deactivate'])->name('deactivate');
                Route::patch('/{member}/reactivate', [IotHardwareTeamMemberController::class, 'reactivate'])->name('reactivate');
            });

            // --- Devices scoped to a team (team device management) ---
            Route::prefix('/{hardwareTeam}/devices')->name('devices.')->group(function () {
                Route::get('/', [IotDeviceRegistryController::class, 'indexForTeam'])->name('index');
                Route::get('/create', [IotDeviceRegistryController::class, 'createForTeam'])->name('create');
                Route::post('/', [IotDeviceRegistryController::class, 'storeForTeam'])->name('store');
            });
        });

        // --- Global IoT Device Registry ---
        Route::prefix('iot-devices')->name('iot-devices.')->group(function () {
            Route::get('/', [IotDeviceRegistryController::class, 'index'])->name('index');
            Route::get('/create', [IotDeviceRegistryController::class, 'create'])->name('create');
            Route::post('/', [IotDeviceRegistryController::class, 'store'])->name('store');
            Route::get('/{iotDevice}', [IotDeviceRegistryController::class, 'show'])->name('show');
            Route::get('/{iotDevice}/edit', [IotDeviceRegistryController::class, 'edit'])->name('edit');
            Route::put('/{iotDevice}', [IotDeviceRegistryController::class, 'update'])->name('update');
            Route::delete('/{iotDevice}', [IotDeviceRegistryController::class, 'destroy'])->name('destroy');

            // Device status management
            Route::patch('/{iotDevice}/revoke', [IotDeviceRegistryController::class, 'revoke'])->name('revoke');
            Route::patch('/{iotDevice}/reactivate', [IotDeviceRegistryController::class, 'reactivate'])->name('reactivate');

            // Device-to-hive assignment wizard
            Route::prefix('/{iotDevice}/assign')->name('assign.')->group(function () {
                Route::get('/', [IotDeviceRegistryController::class, 'assignForm'])->name('form');
                Route::get('/hives', [IotDeviceRegistryController::class, 'assignHives'])->name('hives');
                Route::post('/', [IotDeviceRegistryController::class, 'assign'])->name('store');
            });
            Route::patch('/{iotDevice}/unassign', [IotDeviceRegistryController::class, 'unassign'])->name('unassign');
        });
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
    Route::middleware(['permission:view-monitoring-dashboard|view-hive-data'])->group(function () {
        foreach (['monitoring.temperature', 'monitoring.humidity', 'monitoring.weight', 'monitoring.co2', 'monitoring.audio', 'monitoring.video', 'monitoring.photos', 'alerts.index'] as $name) {
            Route::get('/admin/' . str_replace('.', '/', $name), function () use ($name) {
                return view('admin.placeholder', ['title' => ucwords(str_replace(['.', '-'], ' ', $name)), 'subtitle' => 'Placeholder for ' . $name]);
            })->name('admin.' . $name);
        }
    });

    // Anomaly Detection
    Route::middleware(['permission:view-anomaly-analytics'])->group(function () {
        Route::get('/admin/anomaly/dashboard', [AnomalyDashboardController::class, 'index'])->name('admin.anomaly.dashboard');
        Route::get('/admin/anomaly/analytics', [AnomalyAnalyticsController::class, 'index'])->name('admin.anomaly.analytics');
        Route::get('/admin/anomaly/devices/{device}', [AnomalyDeviceDetailController::class, 'show'])->name('admin.anomaly.devices.show');

        // Part 2 (ML model management) — stays a placeholder until
        // ml_model_versions/AnomalyModelsController exist.
        Route::get('/admin/anomaly/models', function () {
            return view('admin.placeholder', ['title' => 'Anomaly Models', 'subtitle' => 'Placeholder for anomaly.models']);
        })->name('admin.anomaly.models');
    });

    // Reports
    Route::middleware(['permission:generate-reports'])->group(function () {
        foreach (['reports.health', 'reports.production', 'reports.sensor-trends'] as $name) {
            Route::get('/admin/' . str_replace('.', '/', $name), function () use ($name) {
                return view('admin.placeholder', ['title' => ucwords(str_replace(['.', '-'], ' ', $name)), 'subtitle' => 'Placeholder for ' . $name]);
            })->name('admin.' . $name);
        }
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

    // Search — any authenticated non-farmer user
    Route::get('/admin/search', function () {
        return view('admin.placeholder', ['title' => 'Search', 'subtitle' => 'Placeholder for search']);
    })->name('admin.search');

});
