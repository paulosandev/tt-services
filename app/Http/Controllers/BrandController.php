<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index()
    {
        return response()->json(Brand::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:brands,name',
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'unique' => 'La marca ya existe.',
        ]);

        $brand = Brand::create($validated);

        return response()->json($brand, 201);
    }

    public function show(Brand $brand)
    {
        return response()->json($brand);
    }

    public function update(Request $request, Brand $brand)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:brands,name,' . $brand->id,
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'unique' => 'La marca ya existe.',
        ]);

        $brand->update($validated);

        return response()->json($brand);
    }

    public function destroy(Brand $brand)
    {
        $brand->delete();

        return response()->json(['message' => 'Marca eliminada']);
    }
}
