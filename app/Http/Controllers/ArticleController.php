<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        // Cálculo de estatus según las reglas:
        //  - >= min_stock => Suficiente
        //  - < min_stock pero >= 50% => Escaso
        //  - Menor que 50% => Agotado
        if (isset($validated['stock']) && isset($validated['min_stock'])) {
            $stock = $validated['stock'];
            $min   = $validated['min_stock'];

            if ($stock >= $min) {
                $validated['status'] = 'Suficiente';
            } elseif ($stock >= 0.5 * $min) {
                $validated['status'] = 'Escaso';
            } else {
                $validated['status'] = 'Agotado';
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
        // Antes: if (!Auth::user()->isDev() && ...)
        // Mantenemos la verificación para saber si es dev, gerente o colaborador
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

        // Como ahora el colaborador también puede editar todo, ya no restringimos
        $article->update($validated);

        // Recalcular status si se envían stock y min_stock
        if (isset($validated['stock']) && isset($validated['min_stock'])) {
            $stock = $validated['stock'];
            $min   = $validated['min_stock'];

            if ($stock >= $min) {
                $article->status = 'Suficiente';
            } elseif ($stock >= 0.5 * $min) {
                $article->status = 'Escaso';
            } else {
                $article->status = 'Agotado';
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
}
