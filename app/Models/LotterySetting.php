<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotterySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'description',
        'type',
    ];

    protected $casts = [
        'value' => 'string',
    ];

    /**
     * Get setting value by key
     */
    public static function getValue($key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set setting value
     */
    public static function setValue($key, $value, $description = null, $type = 'text')
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'description' => $description,
                'type' => $type,
            ]
        );
    }

    /**
     * Check if lottery is active
     */
    public static function isLotteryActive(): bool
    {
        return static::getValue('lottery_active', '0') === '1';
    }

    /**
     * Get maximum winners
     */
    public static function getMaxWinners(): int
    {
        return (int) static::getValue('max_winners', '1');
    }

    /**
     * Get lottery announcement
     */
    public static function getAnnouncement(): string
    {
        return static::getValue('announcement', 'Undian akan segera dimulai!');
    }
}