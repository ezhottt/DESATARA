<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\TenantContextController;
use App\Http\Controllers\TenantSwitchController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->middleware('throttle:5,1')->name('password.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

Route::get('/', fn () => Inertia::render('Foundation', ['product' => 'DESATARA']))
    ->middleware('auth')
    ->name('home');

Route::middleware('auth')->group(function () {
    Route::post('/tenant/switch/{tenant:uuid}', TenantSwitchController::class)->name('tenant.switch');

    Route::middleware('tenant.context')->group(function () {
        Route::get('/tenant/context', TenantContextController::class)->name('tenant.context');
    });
});
