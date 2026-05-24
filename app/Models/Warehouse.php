<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'location',
        'status',
        'created_by',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function warehouseProducts(): HasMany
    {
        return $this->hasMany(WarehouseProduct::class);
    }

    public function stockTransactions(): HasMany
    {
        return $this->hasMany(StockTransaction::class);
    }

    public function transfersOut(): HasMany
    {
        return $this->hasMany(Transfer::class, 'from_warehouse_id');
    }

    public function transfersIn(): HasMany
    {
        return $this->hasMany(Transfer::class, 'to_warehouse_id');
    }
}
