<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RfqSection extends Model
{
    use HasFactory;
    protected $table = "rfq_sections";
    protected $primaryKey = 'rfq_section_id';
    protected $fillable = [
        // 'rfq_section_id',
        'rfq_id',
        'description',
    ];

    public function rfq(){
        return $this->belongsTo(Rfq::class, 'rfq_id', 'rfq_id');
    }
}
