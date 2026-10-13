<?php

use App\Http\Controllers\Api\V0\AdController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api.key:search:read', 'api.rate', 'api.bill'])->group(function () {
    Route::get('/ads', AdController::class);
});
