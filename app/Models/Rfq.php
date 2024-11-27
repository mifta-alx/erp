<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rfq extends Model
{
    use HasFactory;
    protected $table = "rfqs";
    protected $primaryKey = 'rfq_id';
    protected $fillable = [
        'reference',
        'vendor_id',
        'vendor_reference',
        'order_date',
        'state',
        'taxes',
        'total',
        'confirmation_date',
        'invoice_status'
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id', 'vendor_id');
    }

    public function rfqComponent()
    {
        return $this->hasMany(RfqComponent::class, 'rfq_id', 'rfq_id');
    }
    public function receipts()
    {
        return $this->hasMany(Receipt::class, 'rfq_id', 'rfq_id');
    }
    // public function invoices()
    // {
    //     return $this->hasMany(Invoice::class, 'rfq_id', 'rfq_id');
    // }
}
