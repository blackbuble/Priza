<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Winner extends Model
{
    /**
     * Statuses that represent an "active" winner slot
     * (not yet cancelled). Used as the single source of truth
     * for filtering across the whole codebase.
     */
    const ACTIVE_STATUSES = ['pending', 'confirmed'];

    protected $fillable = [
        'coupon_id',
        'position',
        'status',
        'drawn_at',
        'confirmed_at',
        'cancelled_at',
        'cancellation_reason'
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

    /**
     * Translated label map used in forms, filters, and table columns.
     */
    public static function statusOptions(): array
    {
        return [
            'pending' => 'Pending',
            'confirmed' => 'Dikonfirmasi',
            'cancelled' => 'Dibatalkan',
        ];
    }

    /**
     * Filament badge colour map used in table columns.
     */
    public static function statusColors(): array
    {
        return [
            'pending' => 'warning',
            'confirmed' => 'success',
            'cancelled' => 'danger',
        ];
    }
}
