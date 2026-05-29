<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sales extends Model
{
    use HasFactory;

    const PAYMENT_PAID = 'PAID';
    const PAYMENT_UNPAID = 'UNPAID';
    const PAYMENT_PARTIAL = 'PARTIAL';

    protected $fillable = [
        'customer_id',
        'warehouse_id',
        'invoice_number',
        'subtotal',
        'discount',
        'tax',
        'total',
        'payment_status',
        'payment_method',
        'notes',
        'sold_by',
        'sold_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'sold_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customers::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function soldBy()
    {
        return $this->belongsTo(User::class, 'sold_by');
    }

    public function payments()
    {
        return $this->morphMany(Payments::class, 'reference');
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class, 'sale_id');
    }

    /**
     * Scope for payment status
     */
    public function scopeUnpaid($query)
    {
        return $query->where('payment_status', self::PAYMENT_UNPAID);
    }
}
