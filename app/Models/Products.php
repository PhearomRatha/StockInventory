<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Products extends Model
{

    use HasFactory;
    protected $table      = 'products'; // default is 'products'
    protected $primaryKey = 'id';
    protected $fillable   = [
        'name',
        'category_id',
        'supplier_id',
        'sku',
        'barcode',
        'description',
        'price',
        'cost',
        'stock_quantity',
        'reorder_level',
        'image',
    ];

    public function category()
    {
        return $this->belongsTo(Categories::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Suppliers::class);
    }

    public function stockIns()
    {
        return $this->hasMany(Stock_ins::class);
    }

    public function stockOuts()
    {
        return $this->hasMany(Stock_outs::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    // Accessor for stock status
    public function getStockStatusAttribute()
    {
        $currentStock = $this->stock_quantity;
        $lowStockThreshold = 10;

        if ($currentStock == 0) {
            return 'Out-of-Stock';
        } elseif ($currentStock >= $lowStockThreshold * 2) {
            return 'In Stock';
        } elseif ($currentStock >= $lowStockThreshold) {
            return 'Low Stock';
        } else {
            return 'Very Low Stock';
        }
    }

    // Accessor for low stock flag
    public function getIsLowStockAttribute()
    {
        $lowStockThreshold = 10;
        return $this->stock_quantity < $lowStockThreshold;
    }
}
