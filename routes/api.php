<?php

use App\Http\Controllers\Api\productController;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware(Authenticate::using('sanctum'));

Route::apiResource('/products', App\Http\Controllers\Api\ProductController::class);
Route::apiResource('/materials', App\Http\Controllers\Api\MaterialController::class);
Route::apiResource('/categories', App\Http\Controllers\Api\CategoryController::class);