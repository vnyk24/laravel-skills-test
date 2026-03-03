<?php

use App\Http\Controllers\ProductSubmissionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ProductSubmissionController::class, 'index'])->name('products.index');
Route::post('/products', [ProductSubmissionController::class, 'store'])->name('products.store');
Route::put('/products/{id}', [ProductSubmissionController::class, 'update'])->name('products.update');
