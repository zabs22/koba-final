<?php

use App\Http\Controllers\Customer;
use App\Http\Controllers\Dashboard\ProfileController;
use App\Http\Controllers\Dashboard\SearchController;
use Illuminate\Support\Facades\Route;

/*
| Customer — prefix /customer, names customer.*, middleware auth + role:customer
| (applied in routes/web.php).
*/

Route::get('/dashboard', [Customer\DashboardController::class, 'index'])->name('dashboard');

Route::get('/orders', [Customer\OrderController::class, 'index'])->name('orders');
Route::get('/orders/active', [Customer\OrderController::class, 'active'])->name('orders.active');
Route::post('/orders/demo', [Customer\OrderController::class, 'demo'])->name('orders.demo');
Route::get('/orders/{order}', [Customer\OrderController::class, 'show'])->name('orders.show');

Route::get('/pickups', [Customer\PickupController::class, 'index'])->name('pickups');

Route::get('/favorites', [Customer\FavoriteController::class, 'index'])->name('favorites');
Route::post('/favorites', [Customer\FavoriteController::class, 'toggle'])->name('favorites.toggle');

Route::get('/notifications', [Customer\NotificationController::class, 'index'])->name('notifications');
Route::post('/notifications/read', [Customer\NotificationController::class, 'read'])->name('notifications.read');

Route::get('/profile', ProfileController::class)->name('profile');
Route::get('/search', SearchController::class)->name('search');
