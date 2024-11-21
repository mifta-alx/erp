<?php

use App\Http\Controllers\Api\ImageController;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware(Authenticate::using('sanctum'));

Route::apiResource('/products', App\Http\Controllers\Api\ProductController::class);
Route::apiResource('/materials', App\Http\Controllers\Api\MaterialController::class);
Route::apiResource('/categories', App\Http\Controllers\Api\CategoryController::class);
Route::apiResource('/boms', App\Http\Controllers\Api\BomController::class);
Route::apiResource('/upload-images', App\Http\Controllers\Api\ImageController::class);
Route::get('/upload-images/{uuid}', [ImageController::class, 'show']);
Route::put('/upload-images/{uuid}', [ImageController::class, 'update']);
Route::delete('/upload-images/{uuid}', [ImageController::class, 'destroy']);
Route::apiResource('/tags', App\Http\Controllers\Api\TagController::class);
Route::apiResource('/manufacturing-orders', App\Http\Controllers\Api\MoController::class);
Route::apiResource('/all-data', App\Http\Controllers\Api\DashboardController::class);
Route::apiResource('/vendors', App\Http\Controllers\Api\VendorController::class);
Route::apiResource('/init', App\Http\Controllers\Api\GetDataController::class);
Route::apiResource('/rfqs', App\Http\Controllers\Api\RfqController::class);
