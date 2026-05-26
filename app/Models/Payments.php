<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

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
        'recorded_by',
        'bill_number',
        'status',
        'md5',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}


