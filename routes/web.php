<?php

use App\Http\Controllers\Api\PriceController as ApiPriceController;
use App\Http\Controllers\Api\SubmissionController as ApiSubmissionController;
use App\Http\Controllers\PriceController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PriceController::class, 'index'])->name('prices');
Route::get('/api/prices', [ApiPriceController::class, 'index'])->name('api.prices');
Route::post('/api/submissions', [ApiSubmissionController::class, 'store'])->name('api.submissions');
