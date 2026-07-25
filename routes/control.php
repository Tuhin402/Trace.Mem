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
    
    // Future Pages Scaffolding
    Route::inertia('/users', 'control/Scaffold', ['title' => 'User Management', 'description' => 'Manage system administrators and users.', 'icon' => 'users'])->name('control.users');
    Route::inertia('/tenants', 'control/Scaffold', ['title' => 'Tenant Workspaces', 'description' => 'Oversee all company workspaces.', 'icon' => 'database'])->name('control.tenants');
    Route::inertia('/billing', 'control/Scaffold', ['title' => 'Billing & Revenue', 'description' => 'Track subscriptions, trials, and invoices.', 'icon' => 'credit-card'])->name('control.billing');
    Route::inertia('/platform', 'control/Scaffold', ['title' => 'Platform Jobs', 'description' => 'Monitor background queues and system health.', 'icon' => 'shield'])->name('control.platform');
    
    // Settings
    Route::get('/settings', [\App\Http\Controllers\Control\SettingsController::class, 'index'])->name('control.settings');
    Route::put('/settings', [\App\Http\Controllers\Control\SettingsController::class, 'update'])->name('control.settings.update');
    
    // Global Search API
    Route::get('/api/search', [\App\Http\Controllers\Control\GlobalSearchController::class, 'search'])->name('control.search');
});
