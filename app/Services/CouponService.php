<?php

namespace App\Services;

use App\Models\Coupon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CouponService
{
    /**
     * Create a new coupon from imported data
     */
    public function createCoupon(array $data): Coupon
    {
        return DB::transaction(function () use ($data) {
            // Ensure code is unique
            $code = $this->generateUniqueCode($data['code'] ?? null);
            
            return Coupon::create([
                'code' => $code,
                'owner_name' => $data['owner_name'],
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
            ]);
        });
    }

    /**
     * Generate unique coupon code
     */
    private function generateUniqueCode(?string $code = null): string
    {
        if ($code && !Coupon::where('code', $code)->exists()) {
            return $code;
        }

        // Generate unique code if provided code exists or is null
        do {
            $code = 'KUPON-' . strtoupper(Str::random(6));
        } while (Coupon::where('code', $code)->exists());

        return $code;
    }

    /**
     * Validate coupon data before import
     */
    public function validateCouponData(array $data): array
    {
        $errors = [];

        // Check required fields
        if (empty($data['owner_name'])) {
            $errors[] = 'Nama pemilik harus diisi';
        }

        // Validate email if provided
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format email tidak valid';
        }

        // Validate phone if provided
        if (!empty($data['phone']) && !preg_match('/^[0-9+\-\s()]{10,20}$/', $data['phone'])) {
            $errors[] = 'Format nomor telepon tidak valid';
        }

        return $errors;
    }

    /**
     * Check if coupon code already exists
     */
    public function couponExists(string $code): bool
    {
        return Coupon::where('code', $code)->exists();
    }
}