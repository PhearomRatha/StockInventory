<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payments extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_type',
        'reference_id',
        'amount',
        'payment_type',
        'payment_method',
        'paid_to_from',
        'payment_date',
        'bill_number',
        'recorded_by',
        'status'
    ];
      public function reference() {
        return $this->morphTo(); // can belong to Sale or StockIn
    }

    public function recordedBy() {
        return $this->belongsTo(User::class, 'recorded_by');
    }
    public function sale() {
    return $this->belongsTo(Sales::class, 'reference_id')->where('reference_type','sale');
}

public function stockIn() {
    return $this->belongsTo(Stock_ins::class, 'reference_id')->where('reference_type','purchase');
}

}


