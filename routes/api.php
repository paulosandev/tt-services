<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
Route::middleware('auth:sanctum')->get('/user', [UserController::class, 'show']);
Route::middleware('auth:sanctum')->put('/user', [UserController::class, 'update']);

Route::middleware(['auth:sanctum', 'role:dev,gerente'])->group(function () {
    Route::apiResource('articles', ArticleController::class);
    Route::apiResource('areas', AreaController::class);
    Route::apiResource('brands', BrandController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('suppliers', SupplierController::class);
});

Route::middleware(['auth:sanctum', 'role:dev,gerente,colaborador'])->group(function () {
    Route::put('articles/{article}', [ArticleController::class, 'update']);
    Route::get('articles', [ArticleController::class, 'index']);
    Route::get('areas', [AreaController::class, 'index']);
    Route::get('brands', [BrandController::class, 'index']);
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('suppliers', [SupplierController::class, 'index']);
});
