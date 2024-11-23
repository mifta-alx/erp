<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RfqComponent extends Model
{
    use HasFactory;
    protected $table = "rfq_components";
    protected $primaryKey = 'rfq_component_id';
    protected $fillable = [
        'rfq_id',
        'display_type',
        'material_id',
        'description',
        'qty',
        'unit_price',
        'tax',
        'subtotal',
        'qty_received',
        'qty_to_invoice',
        'qty_invoiced',
    ];

    public function material(){
        return $this->belongsTo(Material::class, 'material_id', 'material_id');
    }

    public function rfq(){
        return $this->belongsTo(Rfq::class, 'rfq_id', 'rfq_id');
    }
}
