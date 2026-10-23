<?php

use App\Http\Controllers\AirQualityController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AirQualityController::class, 'index'])->name('home');
Route::get('/api/air-quality', [AirQualityController::class, 'api'])->name('api.air-quality');
Route::get('/health/data', [AirQualityController::class, 'dataHealth'])->name('health.data');
Route::get('/metrics', [AirQualityController::class, 'metrics'])->name('metrics');
