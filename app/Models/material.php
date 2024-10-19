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
        'notes',
        'image_url',
        'image_uuid',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }

    public function tag()
    {
        return $this->belongsToMany(Tag::class, 'pivot_material_tags', 'material_id', 'tag_id')->withTimestamps();
    }
}
