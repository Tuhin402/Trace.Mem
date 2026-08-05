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
        // We use domain-driven controllers in App\Http\Controllers\Control\Platform\Identity
        // But the URLs and Route names remain flat to match navigation.config.ts and frontend router
        Route::get('/users', [\App\Http\Controllers\Control\Platform\Identity\UserController::class, 'index'])->name('users');
        Route::get('/users/{uuid}', [\App\Http\Controllers\Control\Platform\Identity\UserController::class, 'show'])->name('users.show');

        Route::get('/tenants', [\App\Http\Controllers\Control\Platform\Identity\TenantController::class, 'index'])->name('tenants');
        Route::get('/tenants/{slug}', [\App\Http\Controllers\Control\Platform\Identity\TenantController::class, 'show'])->name('tenants.show');

        Route::get('/workspaces', [\App\Http\Controllers\Control\Platform\Identity\WorkspaceController::class, 'index'])->name('workspaces');
        Route::get('/workspaces/{tenant_slug}/{workspace_slug}', [\App\Http\Controllers\Control\Platform\Identity\WorkspaceController::class, 'show'])->name('workspaces.show');

        // Communications API
        Route::prefix('communications')->name('communications.')->group(function () {
            Route::post('/send', [\App\Http\Controllers\Control\Communications\CommunicationController::class, 'send'])->name('send');
            Route::post('/preview', [\App\Http\Controllers\Control\Communications\CommunicationController::class, 'preview'])->name('preview');
            Route::get('/history/{type}/{id}', [\App\Http\Controllers\Control\Communications\CommunicationController::class, 'history'])->name('history');
        });

        Route::prefix('billing')->name('billing.')->group(function () {
            Route::post('/users/{user}/founding-offer-override', [\App\Http\Controllers\Control\Billing\FoundingOfferOverrideController::class, 'store'])->name('override.store');

            Route::prefix('catalog')->name('catalog.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Control\Billing\CatalogController::class, 'index'])->name('index');
                Route::post('/', [\App\Http\Controllers\Control\Billing\CatalogController::class, 'store'])->name('store');
                Route::get('/impact', [\App\Http\Controllers\Control\Billing\CatalogPricingController::class, 'impact'])->name('pricing.impact');
                Route::post('/pricing', [\App\Http\Controllers\Control\Billing\CatalogPricingController::class, 'store'])->name('pricing.store');
                Route::get('/{id}', [\App\Http\Controllers\Control\Billing\CatalogController::class, 'show'])->name('show')->whereNumber('id');
                Route::put('/{id}', [\App\Http\Controllers\Control\Billing\CatalogController::class, 'update'])->name('update')->whereNumber('id');
                Route::post('/{id}/archive', [\App\Http\Controllers\Control\Billing\CatalogController::class, 'archive'])->name('archive')->whereNumber('id');
                Route::post('/{id}/restore', [\App\Http\Controllers\Control\Billing\CatalogController::class, 'restore'])->name('restore')->whereNumber('id');
                Route::delete('/{id}', [\App\Http\Controllers\Control\Billing\CatalogController::class, 'destroy'])->name('destroy')->whereNumber('id');
            });
        });

        Route::inertia('/api-keys', 'control/Scaffold')->name('api-keys');
        Route::inertia('/memory', 'control/Scaffold')->name('memory');
        Route::inertia('/subscriptions', 'control/Scaffold')->name('subscriptions');
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
