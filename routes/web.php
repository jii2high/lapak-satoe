<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Marketplace\ExploreController;
use App\Http\Controllers\Marketplace\HomeController;
use App\Http\Controllers\Marketplace\ProductController;
use App\Http\Controllers\Marketplace\SearchController;
use App\Http\Controllers\Marketplace\StoreController;
use App\Http\Controllers\Marketplace\WishlistController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('jelajah', [ExploreController::class, 'index'])->name('explore');
Route::get('produk/{product}', [ProductController::class, 'show'])->name('products.show');
Route::get('lapak/{store:slug}', [StoreController::class, 'show'])->name('stores.show');
Route::get('search/suggest', [SearchController::class, 'suggest'])->name('search.suggest');

Route::middleware('auth')->group(function () {
    Route::get('wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('wishlist/produk/{product}', [WishlistController::class, 'toggleProduct'])->name('wishlist.products.toggle');
    Route::post('wishlist/lapak/{store}', [WishlistController::class, 'toggleStore'])->name('wishlist.stores.toggle');

    Route::get('akun', [AccountController::class, 'edit'])->name('account.edit');
    Route::patch('akun/nama-tampilan', [AccountController::class, 'updateDisplayName'])->name('account.display-name.update');
    Route::patch('akun/username', [AccountController::class, 'updateUsername'])->name('account.username.update');
    Route::patch('akun/email', [AccountController::class, 'updateEmail'])->name('account.email.update');
    Route::patch('akun/kata-sandi', [AccountController::class, 'updatePassword'])->name('account.password.update');
    Route::post('akun/ganti-mode', [AccountController::class, 'switchRole'])->name('account.switch-role');
    Route::inertia('akun/preferensi', 'account/preference')->name('account.preference');
});

Route::middleware(['auth', 'role:seller'])->prefix('seller')->name('seller.')->group(function () {
    Route::inertia('dashboard', 'seller/dashboard')->name('dashboard');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::inertia('dashboard', 'admin/dashboard')->name('dashboard');
    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::patch('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
});

require __DIR__.'/settings.php';
