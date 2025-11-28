<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Winner extends Model
{
    protected $fillable = [
        'coupon_id', 'position', 'status', 
        'drawn_at', 'confirmed_at', 'cancelled_at', 'cancellation_reason'
    ];

    protected $casts = [
        'drawn_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }
}
