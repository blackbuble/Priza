<?php

namespace App\Imports;

use App\Models\Coupon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CouponsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsEmptyRows
{
    use SkipsFailures;

    private $importedCount = 0;
    private $skippedCount = 0;
    private $processedRows = [];

    public function model(array $row)
    {
        // Skip if already processed (duplicate in file)
        $rowKey = md5(serialize($row));
        if (in_array($rowKey, $this->processedRows)) {
            $this->skippedCount++;
            return null;
        }
        $this->processedRows[] = $rowKey;

        // Normalize column names
        $normalizedRow = $this->normalizeRow($row);
        
        // Skip if essential data is missing
        if (empty($normalizedRow['owner_name'])) {
            $this->skippedCount++;
            return null;
        }

        // Generate or use provided code
        $code = $this->generateCouponCode($normalizedRow);
        
        // Skip if code already exists in database
        if (Coupon::where('code', $code)->exists()) {
            $this->skippedCount++;
            return null;
        }

        try {
            $coupon = new Coupon([
                'code' => $code,
                'owner_name' => trim($normalizedRow['owner_name']),
                'phone' => !empty($normalizedRow['phone']) ? $this->normalizePhone(trim($normalizedRow['phone'])) : null,
                'email' => !empty($normalizedRow['email']) ? trim($normalizedRow['email']) : null,
            ]);

            $this->importedCount++;
            return $coupon;
            
        } catch (\Exception $e) {
            Log::error('Coupon import error: ' . $e->getMessage(), ['row' => $normalizedRow]);
            $this->skippedCount++;
            return null;
        }
    }

    /**
     * Normalize row data to handle different column name variations
     */
    private function normalizeRow(array $row): array
    {
        $normalized = [];
        
        // Map possible column names to standard names
        $columnMapping = [
            'code' => ['code', 'kode', 'kode_kupon', 'coupon_code'],
            'owner_name' => ['owner_name', 'nama', 'nama_pemilik', 'name', 'customer_name'],
            'phone' => ['phone', 'telepon', 'nomor_telepon', 'phone_number', 'telp'],
            'email' => ['email', 'email_address', 'alamat_email'],
        ];

        foreach ($columnMapping as $standardKey => $possibleKeys) {
            foreach ($possibleKeys as $possibleKey) {
                if (isset($row[$possibleKey]) && !empty($row[$possibleKey])) {
                    $normalized[$standardKey] = $row[$possibleKey];
                    break;
                }
            }
        }

        return $normalized;
    }

    /**
     * Generate coupon code
     */
    private function generateCouponCode(array $row): string
    {
        // Use provided code if available and not empty
        if (!empty($row['code'])) {
            $providedCode = trim($row['code']);
            // Check if the provided code already exists
            if (!Coupon::where('code', $providedCode)->exists()) {
                return $providedCode;
            }
        }

        // Generate unique code
        do {
            $code = 'KUPON-' . strtoupper(Str::random(6));
        } while (Coupon::where('code', $code)->exists());

        return $code;
    }

    /**
     * Normalize phone number format
     */
    private function normalizePhone(string $phone): string
    {
        // Remove non-numeric characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Add Indonesian country code if starts with 0
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }
        
        return $phone;
    }

    public function rules(): array
    {
        return [
            '*.code' => 'nullable|string|max:50',
            '*.owner_name' => 'required|string|max:255',
            '*.phone' => 'nullable|string|max:20',
            '*.email' => 'nullable|email|max:255',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            '*.owner_name.required' => 'Nama pemilik harus diisi',
            '*.owner_name.string' => 'Nama pemilik harus berupa teks',
            '*.email.email' => 'Format email tidak valid',
        ];
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            Log::warning('Coupon import validation failure', [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ]);
        }
    }
}