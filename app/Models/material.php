<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Material extends Model
{
    use HasFactory;
    protected $table = "materials";
    protected $primaryKey = 'material_id';
    protected $fillable = [
        'material_name',
        'category_id',
        'sales_price',
        'cost',
        'barcode',
        'internal_reference',
        'material_tag',
        'company',
        'notes',
        'image',
    ];

    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id', 'category_id');
    }

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn ($image) => url('/storage/materials/' . $image),
        );
    }
}
