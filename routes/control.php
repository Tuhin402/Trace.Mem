<?php

use Illuminate\Support\Facades\Route;

// This route file is strictly bound to the control.* subdomain.
// All routes inside here must enforce the 'control.admin' middleware
// unless they are explicitly meant for guest admins (like login).

Route::middleware('guest')->group(function () {
    Route::inertia('/login', 'control/auth/Login')->name('control.login');
    Route::post('/login', [\App\Http\Controllers\Control\AuthController::class, 'login'])->name('control.login.store');
    
    Route::inertia('/register', 'control/auth/Register')->name('control.register');
    Route::post('/register', [\App\Http\Controllers\Control\AuthController::class, 'register'])->name('control.register.store');

    Route::inertia('/verify-otp', 'control/auth/VerifyOtp')->name('control.verify-otp');
    Route::post('/verify-otp', [\App\Http\Controllers\Control\AuthController::class, 'verifyOtp'])->name('control.verify-otp.store');
});

Route::middleware(['web', 'control.admin'])->group(function () {
    Route::get('/', function () {
        return redirect()->route('control.dashboard');
    });
    
    Route::inertia('/dashboard', 'control/Dashboard')->name('control.dashboard');
    
    // Global Search API
    Route::get('/api/search', [\App\Http\Controllers\Control\GlobalSearchController::class, 'search'])->name('control.search');
});
