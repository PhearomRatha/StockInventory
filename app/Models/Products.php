<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Products extends Model
{

    use HasFactory;
    protected $table      = 'products'; // default is 'products'
    protected $primaryKey = 'id';
    protected $fillable = [
        'category_id',
        'supplier_id',
        'name',
        'sku',
        'barcode',
        'image',
        'description',
        'cost',
        'price',
        'reorder_level',
        'status',
    ];

    public function category()
    {
        return $this->belongsTo(Categories::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Suppliers::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class, 'product_id');
    }

    public function warehouseProducts()
    {
        return $this->hasMany(WarehouseProduct::class, 'product_id');
    }

    public function stockTransactions()
    {
        return $this->hasMany(StockTransaction::class, 'product_id');
    }

    /**
     * Total stock across all warehouses (computed)
     */
    public function getTotalStockAttribute(): float
    {
        return (float) $this->warehouseProducts()->sum('quantity');
    }
}
