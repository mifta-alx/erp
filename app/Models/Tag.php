<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;
    protected $table = "tags";
    protected $primaryKey = 'tag_id';
    protected $fillable = [
        'name_tag',
    ];

    public function product()
    {
        return $this->belongsToMany(Product::class, 'pivot_product_tags', 'tag_id', 'product_id')->withTimestamps();
    }

    public function material()
    {
        return $this->belongsToMany(Product::class, 'pivot_material_tags', 'tag_id', 'material_id')->withTimestamps();
    } 
    public function customer()
    {
        return $this->belongsToMany(Customer::class, 'pivot_customer_tags', 'tag_id', 'customer_id')->withTimestamps();
    }
}
