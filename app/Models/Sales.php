<?php

namespace App\Models;

use Faker\Provider\ar_EG\Payment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sales extends Model

{
    use HasFactory;
    protected $fillable=["sold_by","payment_method","payment_status","discount","discount","total_amount","invoice_number","customer_id"];
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
}
