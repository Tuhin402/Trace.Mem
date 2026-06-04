<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LogsController;
use App\Http\Controllers\MemoryInspectorController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'public/Landing')->name('home');
Route::inertia('/docs', 'public/Docs')->name('docs');
Route::get('/pricing', [PricingController::class, 'index'])->name('pricing');
Route::inertia('/usecases', 'public/UseCases')->name('usecases');
Route::inertia('/status', 'public/Status')->name('status');

Route::inertia('/api-reference', 'public/api-reference/Overview')->name('api.reference.overview');
Route::inertia('/api-reference/quick-start', 'public/api-reference/QuickStart')->name('api.reference.quick-start');
Route::inertia('/api-reference/core-operations', 'public/api-reference/CoreOperations')->name('api.reference.core-operations');
Route::inertia('/api-reference/authentication', 'public/api-reference/Authentication')->name('api.reference.authentication');
Route::inertia('/api-reference/health', 'public/api-reference/Health')->name('api.reference.health');
Route::inertia('/api-reference/remember', 'public/api-reference/Remember')->name('api.reference.remember');
Route::inertia('/api-reference/recall', 'public/api-reference/Recall')->name('api.reference.recall');
Route::inertia('/api-reference/context-assemble', 'public/api-reference/ContextAssemble')->name('api.reference.context-assemble');

Route::middleware('guest')->group(function () {
    Route::inertia('/login', 'auth/Login')->name('login');
    Route::inertia('/register', 'auth/Register')->name('register');

    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/settings', function (\Illuminate\Http\Request $request) {
        return \Inertia\Inertia::render('app/Settings', [
            'mustVerifyEmail' => $request->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail,
            'status' => session('status'),
        ]);
    })->name('settings');

    Route::get('/api-keys', [ApiKeyController::class, 'index'])->name('api.keys');
    Route::get('/logs', [LogsController::class, 'index'])->name('logs');
    Route::get('/memory-inspector', [MemoryInspectorController::class, 'index'])->name('memory.inspector');
    Route::post('/api-keys', [ApiKeyController::class, 'store'])->name('api.keys.store');
    Route::delete('/api-keys/{apiKey}', [ApiKeyController::class, 'destroy'])->name('api.keys.destroy');
    Route::post('/api-keys/{apiKey}/rotate', [ApiKeyController::class, 'rotate'])->name('api.keys.rotate');

    Route::post('/billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::get('/billing/success', [BillingController::class, 'success'])->name('billing.success');
    Route::get('/billing/cancel', [BillingController::class, 'cancel'])->name('billing.cancel');
    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::post('/billing/cancel-subscription', [BillingController::class, 'cancelSubscription'])->name('billing.cancel-subscription');
});

Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])->name('stripe.webhook');

require __DIR__.'/settings.php';