<?php

namespace App\Imports;

use App\Models\Article;
use App\Models\Area;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Supplier;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\WithValidation;

class ArticlesImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use Importable, SkipsFailures;

    public function model(array $row)
    {
        // Obtener o crear las relaciones según los nombres proporcionados
        $area = Area::firstOrCreate(
            ['name' => $row['area']],
            ['id' => $row['area_id']]
        );

        $brand = Brand::firstOrCreate(
            ['name' => $row['marca']],
            ['id' => $row['marca_id']]
        );

        $category = Category::firstOrCreate(
            ['name' => $row['categoria']],
            ['id' => $row['categoria_id']]
        );

        $supplier = Supplier::firstOrCreate(
            ['name' => $row['provedor']],
            ['id' => $row['provedor_id']]
        );

        // Calcular el estatus según las reglas
        $stock = $row['stock'];
        $min_stock = $row['stock_minimo'];

        if ($stock >= $min_stock) {
            $status = 'Suficiente';
        } elseif ($stock >= 0.5 * $min_stock) {
            $status = 'Escaso';
        } else {
            $status = 'Agotado';
        }

        return new Article([
            'name'         => $row['articulo'],
            'area_id'      => $area->id,
            'brand_id'     => $brand->id,
            'category_id'  => $category->id,
            'supplier_id'  => $supplier->id,
            'stock'        => $stock,
            'min_stock'    => $min_stock,
            'unit'         => $row['unidad_de_medida'],
            'is_ordered'   => filter_var($row['is_ordered'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'image_url'    => $row['imagen_url'],
            'status'       => $status,
        ]);
    }

    public function rules(): array
    {
        return [
            '*.articulo' => 'required|string|max:255',
            '*.categoria' => 'required|string|max:255',
            '*.categoria_id' => 'required|exists:categories,id',
            '*.provedor' => 'required|string|max:255',
            '*.provedor_id' => 'required|exists:suppliers,id',
            '*.marca' => 'required|string|max:255',
            '*.marca_id' => 'required|exists:brands,id',
            '*.area' => 'required|string|max:255',
            '*.area_id' => 'required|exists:areas,id',
            '*.unidad_de_medida' => 'required|string|max:50',
            '*.stock' => 'required|numeric',
            '*.stock_minimo' => 'required|numeric',
            '*.imagen_url' => 'nullable|url',
            '*.estatus' => 'nullable|string|max:50',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'exists' => 'El valor de :attribute no es válido.',
            'numeric' => 'El campo :attribute debe ser un número.',
            'url' => 'El campo :attribute debe ser una URL válida.',
        ];
    }
}
