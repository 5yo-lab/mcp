<?php

use App\Http\Controllers\Api\V1\ApiKeyController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\McpPingController;
use App\Http\Controllers\Api\V1\NicheListController;
use App\Http\Controllers\Api\V1\SearchAdsController;
use App\Http\Controllers\Api\V1\ShowAdController;
use App\Http\Controllers\Api\V1\UsageController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::middleware(['api.key:search:read', 'api.rate', 'api.bill'])->group(function () {
    Route::get('/ads', SearchAdsController::class);
    Route::get('/ads/{id}', ShowAdController::class)->whereNumber('id');
    Route::get('/niches', NicheListController::class);
    Route::post('/mcp/ping', McpPingController::class);
});

Route::middleware(['api.key:keys:manage', 'api.rate', 'api.bill'])->group(function () {
    Route::get('/keys', [ApiKeyController::class, 'index']);
    Route::post('/keys', [ApiKeyController::class, 'store']);
    Route::delete('/keys/{apiKey}', [ApiKeyController::class, 'destroy']);
    Route::get('/usage', UsageController::class);
});
