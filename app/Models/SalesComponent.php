<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesComponent extends Model
{
    use HasFactory;
    protected $table = 'sales_components';
    protected $primaryKey = 'sales_component_id';
    protected $fillable = [
        'sales_id',
        'product_id',
        'description',
        'display_type',
        'qty',
        'unit_price',
        'tax',
        'subtotal',
        'qty_received',
        'qty_to_invoice',
        'qty_invoiced',
        'state',
        'reserved',
    ];
    public function sales()
    {
        return $this->belongsTo(Sales::class, 'sales_id', 'sales_id');
    }
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
}
