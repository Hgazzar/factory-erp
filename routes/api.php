<?php

use App\Http\Controllers\Api\V1\AttendanceSyncController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['throttle:120,1', 'attendance.api'])->group(function () {
    Route::post('attendance/sync', [AttendanceSyncController::class, 'store']);
});
