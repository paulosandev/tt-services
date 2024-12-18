<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $fillable = [
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
    ];

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
