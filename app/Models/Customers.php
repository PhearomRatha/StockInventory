<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customers extends Model
{
    use HasFactory;

    protected $table="customers";
    protected $fillable =["notes","preferences","address","phone","email","name"];
     public function stockOuts() {
        return $this->hasMany(Stock_outs::class);
    }

    public function sales() {
        return $this->hasMany(Sales::class);
    }
}
