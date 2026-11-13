<?php

use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Marketplace\CartController;
use App\Http\Controllers\Marketplace\CheckoutController;
use App\Http\Controllers\Marketplace\ExploreController;
use App\Http\Controllers\Marketplace\HomeController;
use App\Http\Controllers\Marketplace\OrderController as BuyerOrderController;
use App\Http\Controllers\Marketplace\ProductController;
use App\Http\Controllers\Marketplace\SearchController;
use App\Http\Controllers\Marketplace\StoreController;
use App\Http\Controllers\Marketplace\WishlistController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Seller\DashboardController as SellerDashboardController;
use App\Http\Controllers\Seller\OrderController as SellerOrderController;
use App\Http\Controllers\Seller\ProductController as SellerProductController;
use App\Http\Controllers\Seller\StoreController as SellerStoreController;
use App\Http\Controllers\SellerApplicationController;
use App\Http\Controllers\TermsController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('jelajah', [ExploreController::class, 'index'])->name('explore');
Route::get('produk/{product}', [ProductController::class, 'show'])->name('products.show');
Route::get('lapak/{store:slug}', [StoreController::class, 'show'])->name('stores.show');
Route::get('search/suggest', [SearchController::class, 'suggest'])->name('search.suggest');
Route::get('syarat-ketentuan', TermsController::class)->name('terms');

Route::middleware('auth')->group(function () {
    Route::get('wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('wishlist/produk/{product}', [WishlistController::class, 'toggleProduct'])->name('wishlist.products.toggle');
    Route::post('wishlist/lapak/{store}', [WishlistController::class, 'toggleStore'])->name('wishlist.stores.toggle');

    Route::get('daftar-seller', [SellerApplicationController::class, 'create'])->name('seller-application.create');
    Route::post('daftar-seller', [SellerApplicationController::class, 'store'])
        ->middleware('email.verified')
        ->name('seller-application.store');

    Route::get('keranjang', [CartController::class, 'index'])->name('cart.index');
    Route::post('keranjang/merge', [CartController::class, 'merge'])->name('cart.merge');
    Route::patch('keranjang/items/{item}', [CartController::class, 'update'])->name('cart.items.update');
    Route::delete('keranjang/items/{item}', [CartController::class, 'destroy'])->name('cart.items.destroy');
    Route::post('checkout/{store:slug}', [CheckoutController::class, 'store'])
        ->middleware('throttle:checkout')
        ->name('checkout.store');

    Route::get('pesanan-saya', [BuyerOrderController::class, 'index'])->name('orders.index');
    Route::post('orders/{order}/cancel', [BuyerOrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('orders/{order}/confirm', [BuyerOrderController::class, 'confirm'])->name('orders.confirm');

    Route::post('report', [ReportController::class, 'store'])
        ->middleware('throttle:reports')
        ->name('reports.store');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read', [NotificationController::class, 'read'])->name('notifications.read');
});

Route::middleware(['auth', 'role:seller'])->prefix('seller')->name('seller.')->group(function () {
    Route::redirect('/', '/seller/dashboard')->name('root');
    Route::get('dashboard', [SellerDashboardController::class, 'index'])->name('dashboard');
    Route::get('data/dashboard', [SellerDashboardController::class, 'data'])->name('data.dashboard');
    Route::get('data/orders', [SellerOrderController::class, 'data'])->name('data.orders');
    Route::get('data/products', [SellerProductController::class, 'data'])->name('data.products');

    Route::get('products', [SellerProductController::class, 'index'])->name('products.index');
    Route::get('products/create', [SellerProductController::class, 'create'])->name('products.create');
    Route::post('products', [SellerProductController::class, 'store'])->name('products.store');
    Route::post('products/bulk', [SellerProductController::class, 'bulk'])->name('products.bulk');
    Route::get('products/{product}/edit', [SellerProductController::class, 'edit'])->name('products.edit');
    Route::patch('products/{product}', [SellerProductController::class, 'update'])->name('products.update');
    Route::delete('products/{product}', [SellerProductController::class, 'destroy'])->name('products.destroy');

    Route::get('store', [SellerStoreController::class, 'edit'])->name('store.edit');
    Route::patch('store', [SellerStoreController::class, 'update'])->name('store.update');
    Route::post('store/toggle', [SellerStoreController::class, 'toggle'])->name('store.toggle');

    Route::get('orders', [SellerOrderController::class, 'index'])->name('orders.index');
    Route::post('orders/{order}/accept', [SellerOrderController::class, 'accept'])->name('orders.accept');
    Route::post('orders/{order}/reject', [SellerOrderController::class, 'reject'])->name('orders.reject');
    Route::post('orders/{order}/cancel', [SellerOrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('orders/{order}/complete', [SellerOrderController::class, 'complete'])->name('orders.complete');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/dashboard')->name('root');
    Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('data/dashboard', [AdminDashboardController::class, 'data'])->name('data.dashboard');
    Route::get('data/users', [UserController::class, 'data'])->name('data.users');
    Route::get('data/categories', [CategoryController::class, 'data'])->name('data.categories');
    Route::get('data/applications', [ApplicationController::class, 'data'])->name('data.applications');
    Route::get('data/reports', [AdminReportController::class, 'data'])->name('data.reports');

    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::patch('users/{user}/email', [UserController::class, 'updateEmail'])->name('users.email.update');
    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset');
    Route::post('users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::patch('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    Route::get('applications', [ApplicationController::class, 'index'])->name('applications.index');
    Route::get('applications/{application}/card', [ApplicationController::class, 'card'])->name('applications.card');
    Route::post('applications/{application}/approve', [ApplicationController::class, 'approve'])->name('applications.approve');
    Route::post('applications/{application}/reject', [ApplicationController::class, 'reject'])->name('applications.reject');

    Route::get('reports', [AdminReportController::class, 'index'])->name('reports.index');
    Route::get('reports/{report}/pdf', [AdminReportController::class, 'pdf'])->name('reports.pdf');
    Route::post('reports/{report}/process', [AdminReportController::class, 'process'])->name('reports.process');
    Route::post('reports/{report}/reject', [AdminReportController::class, 'reject'])->name('reports.reject');
    Route::post('reports/{report}/restore', [AdminReportController::class, 'restore'])->name('reports.restore');
});

require __DIR__.'/settings.php';
