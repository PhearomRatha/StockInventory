<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustmentItem extends Model
{
    protected $fillable = [
        'adjustment_id',
        'product_id',
        'old_quantity',
        'new_quantity',
        'difference',
    ];

    protected $casts = [
        'old_quantity' => 'decimal:2',
        'new_quantity' => 'decimal:2',
        'difference' => 'decimal:2',
    ];

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class, 'adjustment_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'product_id');
    }
}
