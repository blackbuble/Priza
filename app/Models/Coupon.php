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

    public function isWinner()
    {
        return $this->winner()->whereIn('status', ['pending', 'confirmed'])->exists();
    }

    /**
     * Scope for coupons available for drawing
     */
    public function scopeAvailableForDraw($query)
    {
        return $query->whereDoesntHave('winner');
    }

    /**
     * Scope for coupons that have won
     */
    public function scopeHasWon($query)
    {
        return $query->whereHas('winner');
    }

    /**
     * Check if coupon is available for drawing
     */
    public function isAvailableForDraw(): bool
    {
        return !$this->winner()->exists();
    }
}
