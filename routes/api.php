<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MemoryController;

Route::prefix('v1')->group(function () {
    Route::get('/health', [MemoryController::class, 'health']);

    Route::middleware(['api.key.auth'])->group(function () {
        Route::post('/remember', [MemoryController::class, 'remember']);
        Route::post('/recall', [MemoryController::class, 'recall']);
        Route::post('/context/assemble', [MemoryController::class, 'assembleContext']);

        Route::prefix('debug')->group(function () {
            Route::post('/semantic-segment', [MemoryController::class, 'debugSemanticSegment']);
            Route::post('/extract', [MemoryController::class, 'debugExtract']);
            Route::post('/conflicts', [MemoryController::class, 'debugConflicts']);
        });
    });
});