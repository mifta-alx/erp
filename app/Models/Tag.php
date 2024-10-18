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
        return $this->belongsToMany(Product::class, 'pivottags', 'tag_id', 'product_id')->withTimestamps();
    } 
}
