<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

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
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }

    public function image()
    {
        return $this->belongsTo(Image::class, 'image_uuid', 'image_uuid');
    }    

    public function tag()
    {
        return $this->belongsToMany(Tag::class, 'pivot_product_tags', 'product_id', 'tag_id')->withTimestamps();
    }
}
