<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Control\AuthController;
use App\Http\Controllers\Control\SettingsController;
use App\Http\Controllers\Control\GlobalSearchController;
use App\Http\Controllers\Control\OverviewController;

// This route file is strictly bound to the control.* subdomain.
// All routes inside here must enforce the 'control.admin' middleware
// unless they are explicitly meant for guest admins (like login).

Route::middleware('control.guest')->group(function () {
    Route::inertia('/login', 'control/auth/Login')->name('control.login');
    Route::post('/login', [AuthController::class, 'login'])->name('control.login.store');
    
    Route::inertia('/register', 'control/auth/Register')->name('control.register');
    Route::post('/register', [AuthController::class, 'register'])->name('control.register.store');

    Route::inertia('/verify-otp', 'control/auth/VerifyOtp')->name('control.verify-otp');
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->name('control.verify-otp.store');
});

Route::middleware(['web', 'control.admin'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('control.logout');

    Route::get('/', function () {
        return redirect()->route('control.overview');
    });
    
    // Overview (Rate limited per requirements)
    Route::get('/overview', [OverviewController::class, 'index'])
        ->middleware('throttle:60,1')
        ->name('control.overview');

    // Platform
    Route::prefix('platform')->name('control.platform.')->group(function () {
        Route::inertia('/users', 'control/Scaffold')->name('users');
        Route::inertia('/tenants', 'control/Scaffold')->name('tenants');
        Route::inertia('/workspaces', 'control/Scaffold')->name('workspaces');
        Route::inertia('/api-keys', 'control/Scaffold')->name('api-keys');
        Route::inertia('/memory', 'control/Scaffold')->name('memory');
        Route::inertia('/subscriptions', 'control/Scaffold')->name('subscriptions');
        Route::inertia('/billing', 'control/Scaffold')->name('billing');
    });

    // Operations
    Route::prefix('operations')->name('control.operations.')->group(function () {
        Route::inertia('/notifications', 'control/Scaffold')->name('notifications');
        Route::inertia('/jobs', 'control/Scaffold')->name('jobs');
        Route::inertia('/audit-logs', 'control/Scaffold')->name('audit-logs');
        Route::inertia('/analytics', 'control/Scaffold')->name('analytics');
        Route::inertia('/activity', 'control/Scaffold')->name('activity');
    });

    // Support
    Route::prefix('support')->name('control.support.')->group(function () {
        Route::inertia('/tickets', 'control/Scaffold')->name('tickets');
        Route::inertia('/communications', 'control/Scaffold')->name('communications');
    });

    // Configuration
    Route::prefix('configuration')->name('control.configuration.')->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::inertia('/feature-flags', 'control/Scaffold')->name('feature-flags');
        Route::inertia('/system', 'control/Scaffold')->name('system');
    });

    // Security
    Route::prefix('security')->name('control.security.')->group(function () {
        Route::inertia('/admins', 'control/Scaffold')->name('admins');
        Route::inertia('/permissions', 'control/Scaffold')->name('permissions');
        Route::inertia('/sessions', 'control/Scaffold')->name('sessions');
        Route::inertia('/events', 'control/Scaffold')->name('events');
    });

    // Developer / System
    Route::prefix('developer')->name('control.developer.')->group(function () {
        Route::inertia('/logs', 'control/Scaffold')->name('logs');
        Route::inertia('/queues', 'control/Scaffold')->name('queues');
        Route::inertia('/background-tasks', 'control/Scaffold')->name('background-tasks');
    });
    
    // Global Search API
    Route::get('/api/search', [GlobalSearchController::class, 'search'])->name('control.search');
});
