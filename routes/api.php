<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BootstrapController;
use App\Http\Controllers\Api\V1\CldController;
use App\Http\Controllers\Api\V1\GpsPositionController;
use App\Http\Controllers\Api\V1\MediaBatchController;
use App\Http\Controllers\Api\V1\MissionController;
use App\Http\Controllers\Api\V1\VillageController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('bootstrap', BootstrapController::class);

        Route::prefix('me')->group(function () {
            Route::get('clds', [CldController::class, 'index']);
            Route::get('clds/{cld}/villages', [VillageController::class, 'index']);
            Route::get('missions', [MissionController::class, 'index']);
        });

        Route::post('media-batches', [MediaBatchController::class, 'store']);
        Route::post('gps-positions', [GpsPositionController::class, 'store']);
    });
});
