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
use App\Models\Article;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Aquí se registran las rutas para tu API.
|
*/

Route::get('/recalculate-status', function () {
    // Recorrer todos los artículos
    Article::chunk(100, function ($articles) {
        foreach ($articles as $article) {
            // Calcular el nuevo estatus
            if ($article->is_ordered) {
                $article->status = 'Pedido';
            } else {
                $stock = $article->stock;
                $minStock = $article->min_stock;

                if ($stock <= $minStock) {
                    $article->status = 'Para pedir';
                } elseif ($stock < $minStock * 1.2) {
                    $article->status = 'Escaso';
                } else {
                    $article->status = 'Suficiente';
                }
            }

            // Guardar el artículo con el nuevo estatus
            $article->save();
        }
    });

    return 'Estatus recalculados exitosamente.';
});

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

Route::post('/user-info', function (Request $request) {
    // Obtener los parámetros del cuerpo de la solicitud
    $args = $request->input('args');

    // Validar que el parámetro 'id' esté presente
    if (!isset($args['matricula'])) {
        return response()->json(['error' => 'User matricula parameter is required'], 400);
    }

    // Hardcodeamos la información de varios usuarios
    $users = [
        1 => [
            'name' => 'Paulo Sanchez',
            'email' => 'paulosanchez@example.com',
            'phone' => '2711639363 ',
        ],
        2 => [
            'name' => 'Jane Smith',
            'email' => 'jane.smith@example.com',
            'phone' => '+0987654321',
        ],
        3 => [
            'name' => 'Alice Johnson',
            'email' => 'alice.johnson@example.com',
            'phone' => '+1122334455',
        ],
    ];

    // Obtener el ID del usuario
    $userId = $args['matricula'];

    // Verificar si el usuario existe
    if (!array_key_exists($userId, $users)) {
        return response()->json(['error' => 'User not found'], 404);
    }

    // Devolver la información del usuario
    return response()->json($users[$userId]);
});
