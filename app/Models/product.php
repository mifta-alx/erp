<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class product extends Model
{
    use HasFactory;
    protected $table = "products";
    protected $primaryKey = 'id';
    protected $fillable = [
        'product_name',
        'sales_price',
        'cost',
        'barcode',
        'internal_reference',
        'product_tag',
        'company',
        'notes',
        'image',
    ];

    /**
     * image
     *
     * @return Attribute
     */
    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn ($image) => url('/storage/products/' . $image),
        );
    }
}
