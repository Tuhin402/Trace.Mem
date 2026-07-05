<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MemoryController;
use App\Http\Controllers\ChatController;

Route::prefix('v1')->group(function () {
    Route::get('/health', [MemoryController::class, 'health']);

    Route::middleware(['api.key.auth'])->group(function () {
        Route::post('/remember', [MemoryController::class, 'remember']);
        Route::post('/recall', [MemoryController::class, 'recall']);
        Route::post('/context/assemble', [MemoryController::class, 'assembleContext']);
        Route::post('/chat', [ChatController::class, 'chat']);

        Route::prefix('debug')->group(function () {
            Route::post('/semantic-segment', [MemoryController::class, 'debugSemanticSegment']);
            Route::post('/extract', [MemoryController::class, 'debugExtract']);
            Route::post('/conflicts', [MemoryController::class, 'debugConflicts']);
        });
    });
});