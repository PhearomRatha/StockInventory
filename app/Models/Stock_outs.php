<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock_outs extends Model
{
    use HasFactory;
    public function product() {
        return $this->belongsTo(Products::class);
    }

    public function customer() {
        return $this->belongsTo(Customers::class);
    }

    public function soldBy() {
        return $this->belongsTo(User::class, 'sold_by');
    }

    public function sale() {
        return $this->belongsTo(Sales::class);
    }
}
