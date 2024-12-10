<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sales extends Model
{
    use HasFactory;
    protected $table = 'sales';
    protected $primaryKey = 'sales_id';
    protected $fillable = [
        'customer_id',
        'taxes',
        'total',
        'expiration',
        'confirmation_date',
        'invoice_status',
        'state',
        'payment_term_id',
        'reference'
    ];
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }
    public function salesComponents()
    {
        return $this->hasMany(SalesComponent::class, 'sales_id', 'sales_id');
    }
    public function receipts()
    {
        return $this->hasMany(Receipt::class, 'sales_id', 'sales_id');
    }
    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'sales_id', 'sales_id');
    }
    public function paymentTerm()
    {
        return $this->belongsTo(PaymentTerm::class, 'payment_term_id', 'payment_term_id');
    }
}
