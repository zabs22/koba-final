<?php

use App\Http\Controllers\HomeRedirectController;
use App\Http\Controllers\Site\EnquiryController;
use App\Http\Controllers\Site\LocatorController;
use App\Http\Controllers\Site\OrderConfirmationController;
use App\Http\Controllers\Site\SitemapController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public website
|--------------------------------------------------------------------------
*/

Route::view('/', 'site.home')->name('home');
Route::view('/our-story', 'site.our-story')->name('our-story');
Route::view('/menu', 'site.menu')->name('menu');
Route::view('/cakes', 'site.cakes')->name('cakes');
Route::view('/order-a-cake', 'site.order')->name('order');
Route::view('/experience', 'site.experience')->name('experience');
Route::view('/locations', 'site.locations')->name('locations');
Route::view('/contact', 'site.contact')->name('contact');

Route::post('/order-a-cake', [EnquiryController::class, 'cakeOrder'])
    ->middleware('throttle:6,1')
    ->name('order.store');

// Find the nearest café: locate the visitor and rank the cafés by driving time.
// POST, so coordinates never appear in URLs or server access logs.
Route::get('/find-a-cafe', [LocatorController::class, 'show'])->name('locator');
Route::post('/find-a-cafe/nearest', [LocatorController::class, 'nearest'])->middleware('throttle:30,1')->name('locator.nearest');
Route::post('/find-a-cafe/route', [LocatorController::class, 'route'])->middleware('throttle:60,1')->name('locator.route');

// Order confirmed: pickup code and QR (signed link, so guests can reopen it).
Route::middleware('signed')->where(['order' => 'ORD-\d{4}-\d{6}'])->group(function () {
    Route::get('/order-confirmed/{order}', [OrderConfirmationController::class, 'show'])->name('order.confirmed');
    Route::get('/order-confirmed/{order}/status', [OrderConfirmationController::class, 'status'])
        ->middleware('throttle:60,1')
        ->name('order.confirmed.status');
});
Route::post('/contact', [EnquiryController::class, 'contact'])
    ->middleware('throttle:6,1')
    ->name('contact.store');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

/*
|--------------------------------------------------------------------------
| Jetstream application: role dashboards
|--------------------------------------------------------------------------
|
| Login (Fortify) lands on /dashboard, which sends each role to its own
| dashboard. Each area is guarded by the role middleware.
|
*/

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', HomeRedirectController::class)->name('dashboard');

    Route::pattern('order', 'ORD-\d{4}-\d{6}');

    Route::prefix('admin')->name('admin.')->middleware('role:super_admin')->group(base_path('routes/admin.php'));
    Route::prefix('branch')->name('branch.')->middleware('role:branch_admin')->group(base_path('routes/branch.php'));
    Route::prefix('customer')->name('customer.')->middleware('role:customer')->group(base_path('routes/customer.php'));
});
