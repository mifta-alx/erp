<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTerm extends Model
{
    use HasFactory;
    protected $table = "payment_terms";
    protected $primaryKey = 'payment_term_id';
    protected $fillable = [
        'name',
        'value',
    ];
}
