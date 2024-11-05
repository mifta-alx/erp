<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    use HasFactory;
    protected $table = "states";
    protected $primaryKey = 'state_id';
    protected $fillable = [
        'name'
    ];

    public function ManufacturingOrder(){
        return $this->hasMany(ManufacturingOrder::class, 'state_id', 'state_id');
    }
}
