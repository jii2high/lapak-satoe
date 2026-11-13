<?php

use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Marketplace\ExploreController;
use App\Http\Controllers\Marketplace\HomeController;
use App\Http\Controllers\Marketplace\ProductController;
use App\Http\Controllers\Marketplace\SearchController;
use App\Http\Controllers\Marketplace\StoreController;
use App\Http\Controllers\Marketplace\WishlistController;
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
});

Route::middleware(['auth', 'role:seller'])->prefix('seller')->name('seller.')->group(function () {
    Route::redirect('/', '/seller/dashboard')->name('root');
    Route::inertia('dashboard', 'seller/dashboard')->name('dashboard');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/dashboard')->name('root');
    Route::inertia('dashboard', 'admin/dashboard')->name('dashboard');
    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::patch('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    Route::get('applications', [ApplicationController::class, 'index'])->name('applications.index');
    Route::get('applications/{application}/card', [ApplicationController::class, 'card'])->name('applications.card');
    Route::post('applications/{application}/approve', [ApplicationController::class, 'approve'])->name('applications.approve');
    Route::post('applications/{application}/reject', [ApplicationController::class, 'reject'])->name('applications.reject');
});

require __DIR__.'/settings.php';
