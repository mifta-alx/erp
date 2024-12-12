<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;
    protected $table = "products";
    protected $primaryKey = 'product_id';
    protected $fillable = [
        'product_name',
        'category_id',
        'sales_price',
        'cost',
        'barcode',
        'internal_reference',
        'notes',
        'image_url',
        'image_uuid',
        'stock'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }   

    public function tag()
    {
        return $this->belongsToMany(Tag::class, 'pivot_product_tags', 'product_id', 'tag_id')->withTimestamps();
    }

    public function ManufacturingOrder()
    {
        return $this->hasMany(ManufacturingOrder::class, 'product_id', 'product_id');
    }

    public function salesComponents(){
        return $this->hasMany(Sales::class, 'product_id', 'product_id');
    }
}
