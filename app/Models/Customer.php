<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;
    protected $table = "customers";
    protected $primaryKey = 'customer_id';
    protected $fillable = [
        'company',
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
    public function tag()
    {
        return $this->belongsToMany(Tag::class, 'pivot_customer_tags', 'customer_id', 'tag_id')->withTimestamps();
    }
}
