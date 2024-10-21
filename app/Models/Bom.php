<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bom extends Model
{
    use HasFactory;
    protected $table = 'boms';
    protected $primaryKey = 'bom_id';
    protected $fillable = [
        'product_id',
        'bom_qty',
        'bom_reference'
    ];
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
    public function bom_components()
    {
        return $this->hasMany(BomsComponent::class, 'bom_id', 'bom_id');
    }
}
