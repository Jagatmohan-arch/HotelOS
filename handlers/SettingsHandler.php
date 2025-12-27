<?php
/**
 * HotelOS - Settings Handler
 * 
 * Manages hotel profile, tax settings, and configurations
 */

declare(strict_types=1);

namespace HotelOS\Handlers;

use HotelOS\Core\Database;
use HotelOS\Core\TenantContext;

class SettingsHandler
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get hotel profile (tenant info)
     */
    public function getHotelProfile(): ?array
    {
        $tenantId = TenantContext::getId();
        
        return $this->db->queryOne(
            "SELECT * FROM tenants WHERE id = :id",
            ['id' => $tenantId],
            enforceTenant: false
        );
    }
    
    /**
     * Update hotel profile
     */
    public function updateHotelProfile(array $data): bool
    {
        $tenantId = TenantContext::getId();
        
        $this->db->execute(
            "UPDATE tenants SET
             name = :name,
             legal_name = :legal_name,
             email = :email,
             phone = :phone,
             alt_phone = :alt_phone,
             website = :website,
             address_line1 = :address_line1,
             address_line2 = :address_line2,
             city = :city,
             state = :state,
             pincode = :pincode
             WHERE id = :id",
            [
                'id' => $tenantId,
                'name' => trim($data['name'] ?? ''),
                'legal_name' => trim($data['legal_name'] ?? ''),
                'email' => trim($data['email'] ?? ''),
                'phone' => trim($data['phone'] ?? ''),
                'alt_phone' => trim($data['alt_phone'] ?? ''),
                'website' => trim($data['website'] ?? ''),
                'address_line1' => trim($data['address_line1'] ?? ''),
                'address_line2' => trim($data['address_line2'] ?? ''),
                'city' => trim($data['city'] ?? ''),
                'state' => trim($data['state'] ?? ''),
                'pincode' => trim($data['pincode'] ?? '')
            ],
            enforceTenant: false
        );
        
        return true;
    }
    
    /**
     * Update tax settings
     */
    public function updateTaxSettings(array $data): bool
    {
        $tenantId = TenantContext::getId();
        
        $this->db->execute(
            "UPDATE tenants SET
             gst_number = :gst_number,
             state_code = :state_code,
             pan_number = :pan_number
             WHERE id = :id",
            [
                'id' => $tenantId,
                'gst_number' => strtoupper(trim($data['gst_number'] ?? '')),
                'state_code' => trim($data['state_code'] ?? '27'),
                'pan_number' => strtoupper(trim($data['pan_number'] ?? ''))
            ],
            enforceTenant: false
        );
        
        return true;
    }
    
    /**
     * Update check-in/out times
     */
    public function updateCheckTimes(string $checkIn, string $checkOut): bool
    {
        $tenantId = TenantContext::getId();
        
        $this->db->execute(
            "UPDATE tenants SET
             check_in_time = :check_in,
             check_out_time = :check_out
             WHERE id = :id",
            [
                'id' => $tenantId,
                'check_in' => $checkIn,
                'check_out' => $checkOut
            ],
            enforceTenant: false
        );
        
        return true;
    }
    
    /**
     * Get JSON settings
     */
    public function getSettings(): array
    {
        $profile = $this->getHotelProfile();
        $settings = $profile['settings'] ?? null;
        
        if ($settings && is_string($settings)) {
            return json_decode($settings, true) ?? [];
        }
        
        return [];
    }
    
    /**
     * Update a specific setting
     */
    public function updateSetting(string $key, mixed $value): bool
    {
        $tenantId = TenantContext::getId();
        $settings = $this->getSettings();
        $settings[$key] = $value;
        
        $this->db->execute(
            "UPDATE tenants SET settings = :settings WHERE id = :id",
            [
                'id' => $tenantId,
                'settings' => json_encode($settings)
            ],
            enforceTenant: false
        );
        
        return true;
    }
    
    /**
     * Get list of Indian states with GST codes
     */
    public static function getStatesList(): array
    {
        return [
            '01' => 'Jammu & Kashmir',
            '02' => 'Himachal Pradesh',
            '03' => 'Punjab',
            '04' => 'Chandigarh',
            '05' => 'Uttarakhand',
            '06' => 'Haryana',
            '07' => 'Delhi',
            '08' => 'Rajasthan',
            '09' => 'Uttar Pradesh',
            '10' => 'Bihar',
            '11' => 'Sikkim',
            '12' => 'Arunachal Pradesh',
            '13' => 'Nagaland',
            '14' => 'Manipur',
            '15' => 'Mizoram',
            '16' => 'Tripura',
            '17' => 'Meghalaya',
            '18' => 'Assam',
            '19' => 'West Bengal',
            '20' => 'Jharkhand',
            '21' => 'Odisha',
            '22' => 'Chhattisgarh',
            '23' => 'Madhya Pradesh',
            '24' => 'Gujarat',
            '26' => 'Dadra & Nagar Haveli',
            '27' => 'Maharashtra',
            '29' => 'Karnataka',
            '30' => 'Goa',
            '31' => 'Lakshadweep',
            '32' => 'Kerala',
            '33' => 'Tamil Nadu',
            '34' => 'Puducherry',
            '35' => 'Andaman & Nicobar',
            '36' => 'Telangana',
            '37' => 'Andhra Pradesh'
        ];
    }
}
