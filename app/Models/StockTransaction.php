<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockTransaction extends Model
{
    use HasFactory;

    public const TYPE_PURCHASE = 'PURCHASE';
    public const TYPE_SALE = 'SALE';
    public const TYPE_ADJUSTMENT = 'ADJUSTMENT';
    public const TYPE_TRANSFER_IN = 'TRANSFER_IN';
    public const TYPE_TRANSFER_OUT = 'TRANSFER_OUT';

    protected $fillable = [
        'reference_no',
        'warehouse_id',
        'product_id',
        'type',
        'quantity',
        'unit_cost',
        'total_cost',
        'related_id',
        'related_type',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function related(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'related_type', 'related_id');
    }

    public function payments()
    {
        return $this->morphMany(Payments::class, 'reference');
    }
}
