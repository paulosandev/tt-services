<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ArticlesImport;

class ArticleController extends Controller
{
    public function index()
    {
        // 1. Seleccionar solo las columnas necesarias para evitar sobrecarga
        // 2. Eager loading con campos mínimos en las relaciones
        $articles = Article::select(
                'id',
                'name',
                'area_id',
                'brand_id',
                'category_id',
                'supplier_id',
                'stock',
                'min_stock',
                'unit',
                'is_ordered',
                'image_url',
                'status'
            )
            ->with([
                'area:id,name',
                'brand:id,name',
                'category:id,name',
                'supplier:id,name',
            ])
            ->get();

        return response()->json($articles);
    }

    public function store(Request $request)
    {
        if (!Auth::user()->isDev() && !Auth::user()->isGerente() && !Auth::user()->isColaborador()) {
            return response()->json(['message' => 'No tienes permiso para realizar esta acción'], 403);
        }

        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'area_id'      => 'required|exists:areas,id',
            'brand_id'     => 'required|exists:brands,id',
            'category_id'  => 'required|exists:categories,id',
            'supplier_id'  => 'required|exists:suppliers,id',
            'stock'        => 'required|numeric',
            'min_stock'    => 'required|numeric',
            'unit'         => 'required|string',
            'image_url'    => 'nullable|url',
            'is_ordered'   => 'boolean'
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'exists'   => 'El valor de :attribute no es válido.',
            'numeric'  => 'El campo :attribute debe ser un número.'
        ]);

        // Aseguramos que is_ordered tenga un valor
        $validated['is_ordered'] = $validated['is_ordered'] ?? false;

        // Cálculo de estatus según las reglas del frontend:
        if ($validated['is_ordered']) {
            $validated['status'] = 'Pedido';
        } else {
            $stock = $validated['stock'];
            $min   = $validated['min_stock'];

            if ($stock <= $min) {
                $validated['status'] = 'Para pedir';
            } elseif ($stock < $min * 1.2) {
                $validated['status'] = 'Escaso';
            } else {
                $validated['status'] = 'Suficiente';
            }
        }

        $article = Article::create($validated);

        // Eager load con campos mínimos
        $article->load([
            'area:id,name',
            'brand:id,name',
            'category:id,name',
            'supplier:id,name'
        ]);

        return response()->json($article, 201);
    }

    public function update(Request $request, Article $article)
    {
        if (!Auth::user()->isDev() && !Auth::user()->isGerente() && !Auth::user()->isColaborador()) {
            return response()->json(['message' => 'No tienes permiso para realizar esta acción'], 403);
        }

        // Validar los campos (cualquiera puede enviarse)
        $validated = $request->validate([
            'name'         => 'sometimes|string|max:255',
            'area_id'      => 'sometimes|exists:areas,id',
            'brand_id'     => 'sometimes|exists:brands,id',
            'category_id'  => 'sometimes|exists:categories,id',
            'supplier_id'  => 'sometimes|exists:suppliers,id',
            'stock'        => 'sometimes|numeric',
            'min_stock'    => 'sometimes|numeric',
            'unit'         => 'sometimes|string',
            'is_ordered'   => 'sometimes|boolean',
            'image_url'    => 'sometimes|nullable|url',
        ], [
            'integer' => 'El campo :attribute debe ser un número.',
            'boolean' => 'El campo :attribute debe ser verdadero o falso.',
            'exists'  => 'El valor de :attribute no es válido.',
        ]);

        // Actualizar el artículo
        $article->update($validated);

        // Recalcular status si se envían stock y min_stock o is_ordered
        if (isset($validated['is_ordered']) || (isset($validated['stock']) && isset($validated['min_stock']))) {
            if ($article->is_ordered) {
                $article->status = 'Pedido';
            } else {
                $stock = $validated['stock'] ?? $article->stock;
                $min   = $validated['min_stock'] ?? $article->min_stock;

                if ($stock <= $min) {
                    $article->status = 'Para pedir';
                } elseif ($stock < $min * 1.2) {
                    $article->status = 'Escaso';
                } else {
                    $article->status = 'Suficiente';
                }
            }
            $article->save();
        }

        // Eager load con campos mínimos para la respuesta
        $article->load([
            'area:id,name',
            'brand:id,name',
            'category:id,name',
            'supplier:id,name'
        ]);

        return response()->json($article);
    }

    public function destroy(Article $article)
    {
        if (!Auth::user()->isDev() && !Auth::user()->isGerente()) {
            return response()->json(['message' => 'No tienes permiso para realizar esta acción'], 403);
        }

        $article->delete();

        return response()->json(['message' => 'Artículo eliminado']);
    }

    public function import(Request $request)
    {
        // Verificar permisos
        if (!Auth::user()->isDev() && !Auth::user()->isGerente() && !Auth::user()->isColaborador()) {
            return response()->json(['message' => 'No tienes permiso para realizar esta acción'], 403);
        }

        // Validar el archivo
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048',
        ], [
            'file.required' => 'El archivo es obligatorio.',
            'file.mimes' => 'El archivo debe ser un tipo de archivo: xlsx, xls, csv.',
            'file.max' => 'El archivo no debe exceder los 2MB.',
        ]);

        try {
            $import = new ArticlesImport;
            Excel::import($import, $request->file('file'));

            // Manejar errores de validación
            if ($import->failures()->isNotEmpty()) {
                return response()->json([
                    'message' => 'Algunos registros no se pudieron importar.',
                    'failures' => $import->failures(),
                ], 422);
            }

            return response()->json(['message' => 'Artículos importados exitosamente.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al importar el archivo.', 'error' => $e->getMessage()], 500);
        }
    }
}
