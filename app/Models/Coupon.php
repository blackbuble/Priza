<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = ['code', 'owner_name', 'phone', 'email'];

    public function winner()
    {
        return $this->hasOne(Winner::class);
    }

    public function activeWinner()
    {
        return $this->hasOne(Winner::class)->whereIn('status', ['pending', 'confirmed']);
    }

    public function isWinner()
    {
        return $this->activeWinner()->exists();
    }

    /**
     * Scope for coupons available for drawing
     * Excludes coupons that are currently winners (pending or confirmed)
     */
    public function scopeAvailableForDraw($query)
    {
        return $query->whereDoesntHave('winner', function($q) {
            $q->whereIn('status', ['pending', 'confirmed']);
        });
    }

    /**
     * Scope for coupons that have won
     */
    public function scopeHasWon($query)
    {
        return $query->whereHas('winner', function($q) {
            $q->whereIn('status', ['pending', 'confirmed']);
        });
    }

    /**
     * Check if coupon is available for drawing
     */
    public function isAvailableForDraw(): bool
    {
        return !$this->activeWinner()->exists();
    }
}