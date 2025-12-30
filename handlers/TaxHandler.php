<?php
/**
 * HotelOS - Tax Handler
 * 
 * Centralizes GST/Tax calculation logic.
 * Reads rules from config/app.php.
 */

declare(strict_types=1);

namespace HotelOS\Handlers;

class TaxHandler
{
    private array $config;

    public function __construct()
    {
        // Optimized: Load config once per instance, not every calculation
        $this->config = require __DIR__ . '/../config/app.php';
    }

    /**
     * Calculate Tax based on Declared Tariff (Base Rate)
     * 
     * @param float $taxableAmount The amount to tax (Rate * Nights + Extras - Discount)
     * @param float $baseRate The Declared Tariff (Rate per Night) per GST rules
     * @param bool $isExempt Whether the guest/booking is tax exempt
     * @return array ['total_tax' => float, 'cgst' => float, 'sgst' => float, 'rate' => float]
     */
    public function calculate(float $taxableAmount, float $baseRate, bool $isExempt = false): array
    {
        // 1. Check Exemption
        if ($isExempt) {
            return [
                'total_tax' => 0.00,
                'cgst' => 0.00,
                'sgst' => 0.00,
                'rate' => 0.00
            ];
        }

        // 2. Get Rules
        $threshold = (float)($this->config['gst']['threshold'] ?? 7500.00);
        $lowRate = (float)($this->config['gst']['low_slab_rate'] ?? 12.00);
        $highRate = (float)($this->config['gst']['high_slab_rate'] ?? 18.00);

        // 3. Determine Rate (Slab based on Base Rate / Declared Tariff)
        $gstRate = ($baseRate < $threshold) ? $lowRate : $highRate;

        // 4. Calculate
        $halfGst = $gstRate / 2;
        $cgst = round($taxableAmount * ($halfGst / 100), 2);
        $sgst = $cgst; // Assuming intra-state for simplicity (HotelOS V1 assumption)
        $totalTax = $cgst + $sgst;

        return [
            'total_tax' => $totalTax,
            'cgst' => $cgst,
            'sgst' => $sgst,
            'rate' => $gstRate
        ];
    }
}
