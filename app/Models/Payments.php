<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payments extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'type',
        'amount',
        'payment_method',
        'reference_no',
        'notes',
        'payment_date',
        'paid_to_from',
        'status',
        'recorded_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function sale()
    {
        return $this->belongsTo(Sales::class, 'sale_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getPaymentTypeAttribute()
    {
        return $this->type;
    }

    public function setPaymentTypeAttribute($value)
    {
        $this->attributes['type'] = $value;
    }

    public function getReferenceTypeAttribute()
    {
        return $this->sale_id ? 'sale' : 'purchase';
    }

    public function getReferenceIdAttribute()
    {
        return $this->sale_id;
    }
}


