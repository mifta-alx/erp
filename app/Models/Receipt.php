<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    use HasFactory;
    protected $table = "receipts";
    protected $primaryKey = 'receipt_id';
    protected $fillable = [
        'transaction_type',
        'reference',
        'rfq_id',
        'sales_id',
        'customer_id',
        'vendor_id',
        'state',
        'source_document',
        'scheduled_date',
    ];

    public function vendor(){
        return $this->belongsTo(Vendor::class, 'vendor_id', 'vendor_id');
    }
    public function customer(){
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }
    public function rfq(){
        return $this->belongsTo(Rfq::class, 'rfq_id', 'rfq_id');
    }
    public function sales(){
        return $this->belongsTo(Sales::class, 'sales_id', 'sales_id');
    }
}
