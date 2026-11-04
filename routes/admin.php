<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Dashboard\ProfileController;
use App\Http\Controllers\Dashboard\SearchController;
use Illuminate\Support\Facades\Route;

/*
| Super admin — prefix /admin, names admin.*, middleware auth + role:super_admin
| (applied in routes/web.php).
*/

Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard/metrics', [Admin\DashboardController::class, 'metrics'])->name('dashboard.metrics');

Route::get('/branches', [Admin\BranchController::class, 'index'])->name('branches');
Route::post('/branches', [Admin\BranchController::class, 'store'])->name('branches.store');
Route::get('/branches/{branch}', [Admin\BranchController::class, 'show'])->name('branches.show');
Route::get('/branches/{branch}/metrics', [Admin\BranchController::class, 'metrics'])->name('branches.metrics');
Route::put('/branches/{branch}', [Admin\BranchController::class, 'update'])->name('branches.update');

Route::get('/admins', [Admin\AdminController::class, 'index'])->name('admins');
Route::patch('/admins', [Admin\AdminController::class, 'update'])->name('admins.update');

Route::get('/customers', [Admin\CustomerController::class, 'index'])->name('customers');

Route::get('/orders', [Admin\OrderController::class, 'index'])->name('orders');
Route::get('/orders/data', [Admin\OrderController::class, 'data'])->name('orders.data');
Route::get('/orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');

Route::get('/products', [Admin\ProductController::class, 'index'])->name('products');
Route::patch('/products/{product}', [Admin\ProductController::class, 'update'])->name('products.update');
Route::get('/categories', [Admin\ProductController::class, 'categories'])->name('categories');

Route::get('/payments', [Admin\FinanceController::class, 'payments'])->name('payments');
Route::get('/payments/data', [Admin\FinanceController::class, 'transactions'])->name('payments.data');
Route::get('/revenue', [Admin\FinanceController::class, 'revenue'])->name('revenue');

Route::get('/reports', [Admin\ReportController::class, 'index'])->name('reports');
Route::get('/reports/export/{type}', [Admin\ReportController::class, 'export'])->name('reports.export');

Route::get('/settings', [Admin\SettingsController::class, 'edit'])->name('settings');
Route::put('/settings', [Admin\SettingsController::class, 'update'])->name('settings.update');

Route::get('/profile', ProfileController::class)->name('profile');
Route::get('/search', SearchController::class)->name('search');
