<?php

use App\Http\Controllers\Account\OtpController;
use App\Http\Controllers\AccountController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', '/settings/account');
    Route::redirect('akun', '/settings/account');
    Route::redirect('akun/preferensi', '/settings/preference');

    Route::get('settings/account', [AccountController::class, 'edit'])->name('account.edit');
    Route::patch('settings/account/display-name', [AccountController::class, 'updateDisplayName'])->name('account.display-name.update');
    Route::patch('settings/account/username', [AccountController::class, 'updateUsername'])->name('account.username.update');
    Route::patch('settings/account/email', [AccountController::class, 'updateEmail'])->name('account.email.update');
    Route::patch('settings/account/password', [AccountController::class, 'updatePassword'])->name('account.password.update');
    Route::post('settings/account/switch-role', [AccountController::class, 'switchRole'])->name('account.switch-role');
    Route::post('settings/account/otp/send', [OtpController::class, 'send'])->name('account.otp.send');
    Route::post('settings/account/otp/verify', [OtpController::class, 'verify'])->name('account.otp.verify');

    Route::inertia('settings/preference', 'settings/preference')->name('account.preference');
});
