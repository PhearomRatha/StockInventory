<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Products extends Model
{

    use HasFactory;
     protected $table = 'products'; // default is 'products'
    protected $primaryKey = 'id';
protected $fillable = [
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
    'image'
];


     public function category() {
        return $this->belongsTo(categories::class);
    }

    public function supplier() {
        return $this->belongsTo(Suppliers::class);
    }

    public function stockIns() {
        return $this->hasMany(Stock_ins::class);
    }

    public function stockOuts() {
        return $this->hasMany(Stock_outs::class);
    }
}
