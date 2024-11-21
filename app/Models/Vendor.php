<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;
    protected $table = 'vendors';
    protected $primaryKey = 'vendor_id';
    protected $fillable = [
        'type',
        'name',
        'street',
        'city',
        'state',
        'zip',
        'phone',
        'mobile',
        'email',
        'image_url',
        'image_uuid',
    ];

    public function rfq(){
        return $this->hasMany(Rfq::class, 'vendor_id', 'vendor_id');
    }
}
