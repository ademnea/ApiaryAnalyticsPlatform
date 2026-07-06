<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GalleryController as AdminGalleryController;
use App\Http\Controllers\Admin\ScholarshipController as AdminScholarshipController;
use App\Http\Controllers\Admin\FeedbackController as AdminFeedbackController;
use App\Http\Controllers\Public\GalleryController as PublicGalleryController;
use App\Http\Controllers\Public\ScholarshipController as PublicScholarshipController;
use App\Http\Controllers\Public\FeedbackController as PublicFeedbackController;
use Illuminate\Http\Request;

// ============================================================
// PUBLIC ROUTES
// ============================================================

Route::get('/', function () {
    return redirect()->route('admin.login');
});

Route::get('/gallery', [PublicGalleryController::class, 'index'])->name('public.gallery.index');
Route::get('/gallery/{gallery:slug}', [PublicGalleryController::class, 'show'])->name('public.gallery.show');
Route::get('/scholarships', [PublicScholarshipController::class, 'index'])->name('public.scholarships.index');
Route::get('/scholarships/{scholarship}', [PublicScholarshipController::class, 'show'])->name('public.scholarships.show');

Route::get('/gallery', [PublicGalleryController::class, 'index'])
    ->name('public.gallery.index');

Route::get('/gallery/{gallery:slug}', [PublicGalleryController::class, 'show'])
    ->name('public.gallery.show');


Route::get('/scholarships', [PublicScholarshipController::class, 'index'])
    ->name('public.scholarships.index');

Route::get('/scholarships/{scholarship}', [PublicScholarshipController::class, 'show'])
    ->name('public.scholarships.show');


Route::get('/feedback', [PublicFeedbackController::class, 'create'])
    ->name('public.feedback.create');

Route::post('/feedback', [PublicFeedbackController::class, 'store'])
    ->name('public.feedback.store');

Route::get('/feedback/success', [PublicFeedbackController::class, 'success'])
    ->name('public.feedback.success');



// ============================================================
// AUTH ROUTES
// ============================================================

Route::get('/admin/login', [LoginController::class, 'showLoginForm'])
    ->name('admin.login');

Route::post('/admin/login', [LoginController::class, 'login'])
    ->name('admin.login.submit');


Route::get('/login', function () {
    return redirect()->route('admin.login');
})->name('login');


// Password reset

Route::get('/admin/forgot-password',
    [ForgotPasswordController::class, 'showLinkRequestForm']
)->name('admin.password.request');


Route::post('/admin/forgot-password',
    [ForgotPasswordController::class, 'sendResetLinkEmail']
)->name('admin.password.email');


Route::get('/admin/reset-password/{token}',
    [ResetPasswordController::class, 'showResetForm']
)->name('admin.password.reset');


Route::post('/admin/reset-password',
    [ResetPasswordController::class, 'reset']
)->name('admin.password.update');




// ============================================================
// AUTHENTICATED ADMIN ROUTES
// ============================================================

Route::middleware(['auth','ensure.not.farmer'])->group(function () {


    Route::post('/admin/logout',
        [LoginController::class,'logout']
    )->name('admin.logout');


    Route::post('/logout',
        [LoginController::class,'logout']
    )->name('logout');



    Route::get('/admin/dashboard',
        [DashboardController::class,'index']
    )->name('admin.dashboard');



    // ========================================================
    // GALLERY MODULE
    // ========================================================

    Route::middleware(['permission:manage-gallery'])->group(function () {

        Route::prefix('/admin/gallery')
            ->name('admin.gallery.')
            ->group(function () {

                Route::get('/',[AdminGalleryController::class,'index'])
                    ->name('index');

                Route::get('/create',[AdminGalleryController::class,'create'])
                    ->name('create');

                Route::post('/',[AdminGalleryController::class,'store'])
                    ->name('store');

                Route::get('/{gallery}/edit',[AdminGalleryController::class,'edit'])
                    ->name('edit');

                Route::put('/{gallery}',[AdminGalleryController::class,'update'])
                    ->name('update');

                Route::delete('/{gallery}',[AdminGalleryController::class,'destroy'])
                    ->name('destroy');

                Route::post('/images/{image}/replace',
                    [AdminGalleryController::class,'replaceImage']
                )->name('images.replace');


                Route::delete('/images/{image}',
                    [AdminGalleryController::class,'deleteImage']
                )->name('images.delete');

            });

    });



    // ========================================================
    // SCHOLARSHIP MODULE
    // ========================================================

    Route::middleware(['permission:manage-scholarship'])->group(function () {


        Route::prefix('/admin/scholarships')
            ->name('admin.scholarship.')
            ->group(function () {


                Route::get('/',
                    [AdminScholarshipController::class,'index']
                )->name('index');


                Route::get('/create',
                    [AdminScholarshipController::class,'create']
                )->name('create');


                Route::post('/',
                    [AdminScholarshipController::class,'store']
                )->name('store');


                Route::get('/{scholarship}',
                    [AdminScholarshipController::class,'show']
                )->name('show');


                Route::get('/{scholarship}/edit',
                    [AdminScholarshipController::class,'edit']
                )->name('edit');


                Route::put('/{scholarship}',
                    [AdminScholarshipController::class,'update']
                )->name('update');


                Route::delete('/{scholarship}',
                    [AdminScholarshipController::class,'destroy']
                )->name('destroy');

            });

    });



    // ========================================================
    // FEEDBACK MODULE
    // ========================================================

    Route::middleware(['permission:manage-feedback'])->group(function () {


        Route::prefix('/admin/feedback')
            ->name('admin.feedback.')
            ->group(function () {


                Route::get('/',
                    [AdminFeedbackController::class,'index']
                )->name('index');


                Route::get('/{feedback}',
                    [AdminFeedbackController::class,'show']
                )->name('show');


                Route::put('/{feedback}',
                    [AdminFeedbackController::class,'update']
                )->name('update');


                Route::delete('/{feedback}',
                    [AdminFeedbackController::class,'destroy']
                )->name('destroy');


            });

    });




    // ========================================================
    // PLACEHOLDER ROUTES
    // ========================================================

    Route::prefix('/admin/scholarships')->name('admin.scholarship.')->group(function () {
        Route::get('/', [AdminScholarshipController::class, 'index'])->name('index');
        Route::get('/create', [AdminScholarshipController::class, 'create'])->name('create');
        Route::post('/', [AdminScholarshipController::class, 'store'])->name('store');
        Route::get('/{scholarship}', [AdminScholarshipController::class, 'show'])->name('show');
        Route::get('/{scholarship}/edit', [AdminScholarshipController::class, 'edit'])->name('edit');
        Route::put('/{scholarship}', [AdminScholarshipController::class, 'update'])->name('update');
        Route::delete('/{scholarship}', [AdminScholarshipController::class, 'destroy'])->name('destroy');
    });
    
    // ---- Placeholder routes for sidebar and dashboard links ----
    $placeholders = [

        'apiaries.index',
        'apiaries.create',

        'hives.index',
        'hives.create',

        'devices.index',
        'devices.create',

        'monitoring.temperature',
        'monitoring.humidity',
        'monitoring.weight',

        'alerts.index',

        'farmers.index',
        'farmers.create',

        'users.index',
        'users.create',

        'roles.index',
        'roles.create',

        'newsletter.index',
        'newsletter.create',
        // Website content
        'newsletter.index','newsletter.create',
        'publications.index','publications.create',
        'events.index','events.create',
        'team.index','team.create',

        'publications.index',
        'publications.create',

        'events.index',
        'events.create',

        'team.index',
        'team.create',

        'workpackages.index',
        'workpackages.create',

        'search',

    ];



    foreach ($placeholders as $name) {

        $routeName = 'admin.' . $name;

        $uri = '/admin/' . str_replace('.', '/', $name);


        Route::get($uri,function() use($name){

            $title = ucwords(
                str_replace(
                    ['.','-'],
                    [' ',' '],
                    $name
                )
            );


            return view('admin.placeholder',[
                'title'=>$title,
                'subtitle'=>'Placeholder for '.$title
            ]);

        })->name($routeName);

    }


});