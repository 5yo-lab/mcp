<?php

use App\Http\Controllers\Api\V0\AdController;
use Illuminate\Support\Facades\Route;

Route::get('/ads', AdController::class);
