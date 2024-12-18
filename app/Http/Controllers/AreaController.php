<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    public function index()
    {
        return response()->json(Area::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:areas',
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'unique' => 'El valor de :attribute ya existe.',
        ]);

        $area = Area::create($validated);

        return response()->json($area, 201);
    }

    public function update(Request $request, Area $area)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:areas,name,' . $area->id,
        ]);

        $area->update($validated);

        return response()->json($area);
    }

    public function destroy(Area $area)
    {
        $area->delete();

        return response()->json(['message' => 'Área eliminada']);
    }
}
