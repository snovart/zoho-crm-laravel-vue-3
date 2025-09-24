<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DealController;
use Illuminate\Support\Facades\Route;

Route::get('/customers', [CustomerController::class, 'index']);
Route::post('deals', [DealController::class, 'store']);
