<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ArticleController extends Controller
{
    public function index()
    {
        return response()->json(Article::with(['area', 'brand', 'category', 'supplier'])->get());
    }

    public function store(Request $request)
    {
        if (!Auth::user()->isDev() && !Auth::user()->isGerente()) {
            return response()->json(['message' => 'No tienes permiso para realizar esta acción'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'area_id' => 'required|exists:areas,id',
            'brand_id' => 'required|exists:brands,id',
            'category_id' => 'required|exists:categories,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'stock' => 'required|numeric',
            'min_stock' => 'required|numeric',
            'unit' => 'required|string',
            'image_url' => 'nullable|url',
            'is_ordered' => 'boolean'
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'exists' => 'El valor de :attribute no es válido.',
            'numeric' => 'El campo :attribute debe ser un número.'
        ]);

        $validated['is_ordered'] = $validated['is_ordered'] ?? false;
        if (isset($validated['stock']) && isset($validated['min_stock'])) {
            $validated['status'] = $validated['stock'] >= $validated['min_stock'] ? 'Suficiente' : 'Escaso';
        }

        $article = Article::create($validated);

        // Cargar relaciones para devolver el artículo completo
        $article->load(['area', 'brand', 'category', 'supplier']);

        return response()->json($article, 201);
    }

    public function update(Request $request, Article $article)
    {
        if (!Auth::user()->isDev() && !Auth::user()->isGerente() && !Auth::user()->isColaborador()) {
            return response()->json(['message' => 'No tienes permiso para realizar esta acción'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'area_id' => 'sometimes|exists:areas,id',
            'brand_id' => 'sometimes|exists:brands,id',
            'category_id' => 'sometimes|exists:categories,id',
            'supplier_id' => 'sometimes|exists:suppliers,id',
            'stock' => 'sometimes|numeric',
            'min_stock' => 'sometimes|numeric',
            'unit' => 'sometimes|string',
            'is_ordered' => 'sometimes|boolean',
            'image_url' => 'sometimes|nullable|url',
        ], [
            'integer' => 'El campo :attribute debe ser un número.',
            'boolean' => 'El campo :attribute debe ser verdadero o falso.',
            'exists' => 'El valor de :attribute no es válido.',
        ]);

        if (Auth::user()->isColaborador()) {
            $article->update(['stock' => $validated['stock']]);
        } else {
            $article->update($validated);
        }

        if (isset($validated['stock']) && isset($validated['min_stock'])) {
            $validated['status'] = $validated['stock'] >= $validated['min_stock'] ? 'Suficiente' : 'Escaso';
        }

        $article->update($validated);

        // Cargar relaciones nuevamente
        $article->load(['area', 'brand', 'category', 'supplier']);

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
