<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock_ins extends Model

{

    protected $fillable=["supplier_id","product_id","unit_cost","quantity","received_by","remarks","total_cost","received_date"];
    use HasFactory;
     public function product() {
        return $this->belongsTo(Products::class);
    }

    public function supplier() {
        return $this->belongsTo(Suppliers::class);
    }

    public function receivedBy() {
        return $this->belongsTo(User::class, 'received_by');
    }
}
