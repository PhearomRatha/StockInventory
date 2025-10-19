<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Faker\Provider\ar_EG\Payment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'role_id',

    ];
    public function role() {
        return $this->belongsTo(Roles::class); // many-to-one
    }

    public function stockIns() {
        return $this->hasMany(Stock_ins::class, 'received_by');
    }

    public function stockOuts() {
        return $this->hasMany(Stock_outs::class, 'sold_by');
    }

    public function sales() {
        return $this->hasMany(Sales::class, 'sold_by');
    }

    public function payments() {
        return $this->hasMany(Payment::class, 'recorded_by');
    }

    public function activityLogs() {
        return $this->hasMany(Activity_logs::class);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    //use for hash
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}
