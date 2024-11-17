<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MoComponent extends Model
{
    use HasFactory;
    protected $table = "mo_components";
    protected $primaryKey = 'mo_component_id';
    protected $fillable = [
        'mo_id',
        'to_consume',
        'material_id',
        'consumed',
        'reserved',
    ];
    public function material(){
        return $this->belongsTo(Material::class, 'material_id', 'material_id');
    }
    
    public function mo(){
        return $this->belongsTo(ManufacturingOrder::class, 'mo_id', 'mo_id');
    }
}
