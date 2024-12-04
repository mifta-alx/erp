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
        'quantity',
        'taxes',
        'total',
        'order_date',
        'expiration',
        'invoice_status',
        'state',
        'payment_trem',
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
}
