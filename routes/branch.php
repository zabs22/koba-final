<?php

use App\Http\Controllers\Branch;
use App\Http\Controllers\Dashboard\ProfileController;
use App\Http\Controllers\Dashboard\SearchController;
use Illuminate\Support\Facades\Route;

/*
| Branch admin — prefix /branch, names branch.*, middleware auth + role:branch_admin
| (applied in routes/web.php). Every controller reads the branch from the
| signed-in admin, never from the URL.
*/

Route::get('/dashboard', [Branch\DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard/metrics', [Branch\DashboardController::class, 'metrics'])->name('dashboard.metrics');
Route::patch('/status', [Branch\DashboardController::class, 'status'])->name('status');

Route::get('/live-orders', [Branch\LiveOrderController::class, 'index'])->name('live-orders');
Route::get('/live-orders/feed', [Branch\LiveOrderController::class, 'feed'])->name('live-orders.feed');
Route::post('/live-orders/simulate', [Branch\LiveOrderController::class, 'simulate'])->name('live-orders.simulate');

Route::get('/pickup-verification', [Branch\PickupVerificationController::class, 'index'])->name('verify');
Route::get('/pickup-verification/feed', [Branch\PickupVerificationController::class, 'feed'])->name('verify.feed');
Route::get('/pickup-verification/lookup', [Branch\PickupVerificationController::class, 'lookup'])->middleware('throttle:40,1')->name('verify.lookup');
Route::post('/pickup-verification/{order}/confirm', [Branch\PickupVerificationController::class, 'confirm'])->name('verify.confirm');
Route::post('/pickup-verification/{order}/undo', [Branch\PickupVerificationController::class, 'undo'])->name('verify.undo');
Route::post('/pickup-verification/demo-reset', [Branch\PickupVerificationController::class, 'reset'])->name('verify.reset');

Route::get('/orders', [Branch\OrderController::class, 'index'])->name('orders');
Route::get('/orders/data', [Branch\OrderController::class, 'data'])->name('orders.data');
Route::get('/orders/{order}', [Branch\OrderController::class, 'show'])->name('orders.show');
Route::patch('/orders/{order}/status', [Branch\OrderController::class, 'status'])->name('orders.status');
Route::get('/orders/{order}/receipt', [Branch\OrderController::class, 'receipt'])->name('orders.receipt');

Route::get('/products', [Branch\MenuController::class, 'products'])->name('products');
Route::patch('/products/{product}', [Branch\MenuController::class, 'availability'])->name('products.availability');
Route::get('/categories', [Branch\MenuController::class, 'categories'])->name('categories');
Route::put('/categories/order', [Branch\MenuController::class, 'reorder'])->name('categories.order');

Route::get('/pickups', [Branch\PickupController::class, 'index'])->name('pickups');
Route::get('/customers', [Branch\CustomerController::class, 'index'])->name('customers');
Route::get('/payments', [Branch\PaymentController::class, 'index'])->name('payments');

Route::get('/reports', [Branch\ReportController::class, 'index'])->name('reports');
Route::get('/reports/export/{type}', [Branch\ReportController::class, 'export'])->name('reports.export');

Route::get('/settings', [Branch\SettingsController::class, 'edit'])->name('settings');
Route::put('/settings', [Branch\SettingsController::class, 'update'])->name('settings.update');

Route::get('/profile', ProfileController::class)->name('profile');
Route::get('/search', SearchController::class)->name('search');
