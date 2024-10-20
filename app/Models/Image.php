<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Image extends Model
{
    use HasFactory;
    protected $table = "images";
    protected $primaryKey = 'image_id';
    protected $fillable = [
        'image_uuid',
        'image',
        // 'image_url'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->image_uuid) {
                $model->image_uuid = (string) Str::uuid();
            }
        });
    }
    // public function product()
    // {
    //     return $this->hasMany(Product::class, 'image_uuid', 'image_uuid');
    // }
}
