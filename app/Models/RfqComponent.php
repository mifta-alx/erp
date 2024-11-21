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
        'rfq_section_id',
        'material_id',
        'description',
        'qty',
        'unit_price',
        'tax',
        'subtotal',
    ];

    public function section(){
        return $this->belongsTo(RfqSection::class, 'section_id', 'section_id');
    }

    public function material(){
        return $this->belongsTo(Material::class, 'material_id', 'material_id');
    }

    public function rfq(){
        return $this->belongsTo(Rfq::class, 'rfq_id', 'rfq_id');
    }
}
