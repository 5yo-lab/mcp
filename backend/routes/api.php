<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\NicheListController;
use App\Http\Controllers\Api\V1\SearchAdsController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', UserController::class);
});

Route::middleware('api.key:search:read')->group(function () {
    Route::get('/ads', SearchAdsController::class);
    Route::get('/niches', NicheListController::class);
});
