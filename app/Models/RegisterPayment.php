<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegisterPayment extends Model
{
    use HasFactory;
    protected $table = "register_payments";
    protected $primaryKey = 'payment_id';
    protected $fillable = [
        'reference',
        'invoice_id',
        'vendor_id',
        'customer_id',
        'journal',
        'amount',
        'payment_date',
        'memo',
        'payment_type',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'invoice_id');
    }
    public function vendor()
    {
        return $this->hasMany(Vendor::class, 'vendor_id', 'vendor_id');
    }
    public function customer()
    {
        return $this->hasMany(Customer::class, 'customer_id', 'customer_id');
    }
}
