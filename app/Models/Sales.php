<?php

namespace App\Models;

use Faker\Provider\ar_EG\Payment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sales extends Model

{
    use HasFactory;
protected $fillable = [
    'customer_id',
    'sold_by',
    'invoice_number',
    'total_amount',
    'discount',
    'payment_status',
    'payment_method',
    'status'
];

    public function stockOuts() {
        return $this->hasMany(Stock_outs::class);
    }

    public function customer() {
        return $this->belongsTo(Customers::class);
    }

    public function soldBy() {
        return $this->belongsTo(User::class, 'sold_by');
    }

    public function payments() {
        return $this->morphMany(Payment::class, 'reference');
    }

    public function saleItems() {
        return $this->hasMany(SaleItem::class, 'sale_id');
    }
}
