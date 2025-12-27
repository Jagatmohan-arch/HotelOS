<?php
/**
 * HotelOS - Tenant Context Manager
 * 
 * Manages the current tenant context for multi-tenancy isolation
 * Every database query automatically filters by tenant_id
 */

declare(strict_types=1);

namespace HotelOS\Core;

class TenantContext
{
    private static ?int $tenantId = null;
    private static ?array $tenant = null;

    /**
     * Set the current tenant context
     */
    public static function set(int $tenantId, ?array $tenantData = null): void
    {
        self::$tenantId = $tenantId;
        self::$tenant = $tenantData;
    }

    /**
     * Get the current tenant ID
     */
    public static function getId(): ?int
    {
        return self::$tenantId;
    }

    /**
     * Get full tenant data
     */
    public static function get(): ?array
    {
        return self::$tenant;
    }

    /**
     * Get specific tenant attribute
     */
    public static function attr(string $key, mixed $default = null): mixed
    {
        return self::$tenant[$key] ?? $default;
    }

    /**
     * Check if tenant context is active
     */
    public static function isActive(): bool
    {
        return self::$tenantId !== null;
    }

    /**
     * Clear tenant context (for logout/cleanup)
     */
    public static function clear(): void
    {
        self::$tenantId = null;
        self::$tenant = null;
    }

    /**
     * Get tenant's GST state code
     */
    public static function getStateCode(): ?string
    {
        return self::$tenant['state_code'] ?? null;
    }

    /**
     * Get tenant's GSTIN
     */
    public static function getGstNumber(): ?string
    {
        return self::$tenant['gst_number'] ?? null;
    }

    /**
     * Calculate GST type based on guest's state vs hotel's state
     * 
     * @param string $guestStateCode Guest's state code
     * @return string 'intra' for CGST+SGST, 'inter' for IGST
     */
    public static function getGstType(string $guestStateCode): string
    {
        $hotelState = self::getStateCode();
        
        if ($hotelState === null) {
            return 'intra'; // Default fallback
        }

        return ($hotelState === $guestStateCode) ? 'intra' : 'inter';
    }

    /**
     * Calculate GST components for a given amount
     * 
     * @param float $amount Base amount
     * @param float $gstRate GST rate percentage (12 or 18)
     * @param string $guestStateCode Guest's state code for CGST/SGST vs IGST
     * @return array GST breakdown
     */
    public static function calculateGst(float $amount, float $gstRate, string $guestStateCode): array
    {
        $gstType = self::getGstType($guestStateCode);
        $gstAmount = $amount * ($gstRate / 100);

        if ($gstType === 'intra') {
            // Intra-state: Split into CGST + SGST
            $halfGst = $gstAmount / 2;
            return [
                'type'       => 'intra',
                'cgst_rate'  => $gstRate / 2,
                'cgst_amount'=> round($halfGst, 2),
                'sgst_rate'  => $gstRate / 2,
                'sgst_amount'=> round($halfGst, 2),
                'igst_rate'  => 0,
                'igst_amount'=> 0,
                'total_gst'  => round($gstAmount, 2),
                'grand_total'=> round($amount + $gstAmount, 2),
            ];
        } else {
            // Inter-state: Full IGST
            return [
                'type'       => 'inter',
                'cgst_rate'  => 0,
                'cgst_amount'=> 0,
                'sgst_rate'  => 0,
                'sgst_amount'=> 0,
                'igst_rate'  => $gstRate,
                'igst_amount'=> round($gstAmount, 2),
                'total_gst'  => round($gstAmount, 2),
                'grand_total'=> round($amount + $gstAmount, 2),
            ];
        }
    }

    /**
     * Load tenant from database by ID
     */
    public static function loadById(int|string $tenantId): bool
    {
        $tenantId = (int) $tenantId;  // Ensure int for database query
        $db = Database::getInstance();
        
        // Query without tenant filter since we're loading the tenant itself
        $tenant = $db->queryOne(
            "SELECT * FROM tenants WHERE id = :id AND status = 'active'",
            ['id' => $tenantId],
            enforceTenant: false // Important: Skip tenant filter
        );

        if ($tenant) {
            self::set($tenant['id'], $tenant);
            return true;
        }

        return false;
    }

    /**
     * Load tenant from database by slug
     */
    public static function loadBySlug(string $slug): bool
    {
        $db = Database::getInstance();
        
        $tenant = $db->queryOne(
            "SELECT * FROM tenants WHERE slug = :slug AND status = 'active'",
            ['slug' => $slug],
            enforceTenant: false
        );

        if ($tenant) {
            self::set($tenant['id'], $tenant);
            return true;
        }

        return false;
    }
}
