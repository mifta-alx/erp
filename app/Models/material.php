<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class material extends Model
{
    use HasFactory;
    protected $table = "materials";
    protected $primaryKey = 'id';
    protected $fillable = [
        'material_name',
        'sales_price',
        'cost',
        'barcode',
        'internal_reference',
        'material_tag',
        'company',
        'notes',
        'image',
    ];
}
