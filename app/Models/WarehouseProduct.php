<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseProduct extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'updated_at' => 'datetime',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'product_id');
    }
}
