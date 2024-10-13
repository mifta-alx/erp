<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BomsComponent extends Model
{
    use HasFactory;
    protected $table = 'bom_components';
    protected $fillable = [
        'bom_id',
        'material_id',
        'material_qty',
    ];
    public function bom()
    {
        return $this->belongsTo(Bom::class, 'bom_id', 'bom_id');
    }
    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id', 'material_id');
    }
    
}
