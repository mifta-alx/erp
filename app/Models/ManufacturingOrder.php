<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManufacturingOrder extends Model
{
    use HasFactory;
    protected $table = "manufacturing_orders";
    protected $primaryKey = 'mo_id';
    protected $fillable = [
        'reference',
        'product_id',
        'qty',
        'bom_id',
        'state',
        'status'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function bom()
    {
        return $this->belongsTo(Bom::class, 'bom_id', 'bom_id');
    }

    public function mo()
    {
        return $this->hasMany(MoComponent::class,'mo_id','mo_id');
    }
}
