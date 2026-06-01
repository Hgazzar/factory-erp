<?php

use App\Http\Controllers\Api\V1\AttendanceSyncController;
use App\Http\Controllers\Api\V1\PersonalAccessTokenController;
use App\Http\Controllers\ItemWebController;
use App\Http\Controllers\StockInController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/token', [PersonalAccessTokenController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('api.v1.auth.token');

    Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
        Route::get('auth/tokens', [PersonalAccessTokenController::class, 'index'])
            ->name('api.v1.auth.tokens.index');
        Route::delete('auth/tokens/{tokenId}', [PersonalAccessTokenController::class, 'destroy'])
            ->whereNumber('tokenId')
            ->name('api.v1.auth.tokens.destroy');

        Route::middleware(['role:admin', 'module:inventory'])->prefix('inventory')->name('api.v1.inventory.')->group(function () {
            Route::get('items', [ItemWebController::class, 'index'])->name('items.index');
            Route::post('items', [ItemWebController::class, 'store'])->name('items.store');
            Route::get('items/{item}', [ItemWebController::class, 'show'])->whereNumber('item')->name('items.show');
            Route::put('items/{item}', [ItemWebController::class, 'update'])->whereNumber('item')->name('items.update');
            Route::delete('items/{item}', [ItemWebController::class, 'destroy'])->whereNumber('item')->name('items.destroy');

            Route::post('stock-receipts', [StockInController::class, 'store'])->name('stock-receipts.store');
            Route::get('stock-receipts/{stockIn}', [StockInController::class, 'show'])->whereNumber('stockIn')->name('stock-receipts.show');
        });
    });

    Route::middleware(['throttle:120,1', 'attendance.api'])->group(function () {
        Route::post('attendance/sync', [AttendanceSyncController::class, 'store']);
    });
});
