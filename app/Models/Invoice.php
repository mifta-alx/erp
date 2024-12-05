<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;
    protected $table = "invoices";
    protected $primaryKey = 'invoice_id';
    protected $fillable = [
        'transaction_type',
        'reference',
        'rfq_id',
        'sales_id',
        'vendor_id',
        'customer_id',
        'state',
        'invoice_date',
        'accounting_date',
        'payment_term_id',
        'due_date',
        'source_document'
    ];

    public function vendor(){
        return $this->belongsTo(Vendor::class, 'vendor_id', 'vendor_id');
    }
    public function rfq(){
        return $this->belongsTo(Rfq::class, 'rfq_id', 'rfq_id');
    }
    public function sales(){
        return $this->belongsTo(Sales::class, 'sales_id', 'sales_id');
    }
    public function customer(){
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function paymentTerm(){
        return $this->hasMany(PaymentTerm::class, 'payment_term_id', 'payment_term_id');
    }
}
