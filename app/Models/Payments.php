<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payments extends Model
{
    use HasFactory;
      public function reference() {
        return $this->morphTo(); // can belong to Sale or StockIn
    }

    public function recordedBy() {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}


