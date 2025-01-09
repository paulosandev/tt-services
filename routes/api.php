<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Aquí se registran las rutas para tu API.
|
*/

// Rutas de autenticación
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Ruta para subir imágenes a Cloudinary

// Rutas protegidas por Sanctum
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
Route::middleware('auth:sanctum')->get('/user', [UserController::class, 'show']);
Route::middleware('auth:sanctum')->put('/user', [UserController::class, 'update']);

// Rutas para usuarios dev y gerente (crear/borrar artículos, etc.)
Route::middleware(['auth:sanctum', 'role:dev,gerente'])->group(function () {
    Route::apiResource('areas', AreaController::class);
    Route::apiResource('brands', BrandController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('suppliers', SupplierController::class);
});

// Rutas para dev, gerente y colaborador (actualizar stock, leer artículos, etc.)
Route::middleware(['auth:sanctum', 'role:dev,gerente,colaborador'])->group(function () {
    Route::apiResource('articles', ArticleController::class);
    Route::post('/upload', [UploadController::class, 'uploadImage']);
    Route::get('areas', [AreaController::class, 'index']);
    Route::get('brands', [BrandController::class, 'index']);
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('suppliers', [SupplierController::class, 'index']);

    // Ruta para importar artículos desde Excel
    Route::post('/articles/import', [ArticleController::class, 'import']);
});
