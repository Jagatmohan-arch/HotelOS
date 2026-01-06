<?php
/**
 * HotelOS - Auditor Export Handler
 * 
 * MODULE 2: COMPLIANCE & AUDIT LEYER
 * Generates immutable export packages for external auditors.
 */

declare(strict_types=1);

namespace HotelOS\Handlers;

use HotelOS\Core\Database;
use HotelOS\Core\TenantContext;

class AuditorExportHandler
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Generate Full Compliance Package (ZIP)
     * Contains CSVs for Invoices, Transactions, Tax Report and a Cryptographic Manifest.
     */
    public function generateCompliancePackage(string $startDate, string $endDate): string
    {
        $tenantId = TenantContext::getId();
        $zipFile = sys_get_temp_dir() . "/audit_pkg_{$tenantId}_" . time() . ".zip";
        $zip = new \ZipArchive();

        if ($zip->open($zipFile, \ZipArchive::CREATE) !== TRUE) {
            throw new \Exception("Cannot create zip file");
        }

        // 1. Generate CSVs
        $invoicesCsv = $this->generateInvoicesCSV($startDate, $endDate);
        $transactionsCsv = $this->generateTransactionsCSV($startDate, $endDate);
        $taxCsv = $this->generateTaxCSV($startDate, $endDate);

        $zip->addFromString("invoices.csv", $invoicesCsv);
        $zip->addFromString("transactions.csv", $transactionsCsv);
        $zip->addFromString("tax_report.csv", $taxCsv);

        // 2. Generate Manifest (Anti-Tamper)
        $manifest = [
            'generated_at' => date('c'),
            'tenant_id' => $tenantId,
            'period_start' => $startDate,
            'period_end' => $endDate,
            'files' => [
                'invoices.csv' => hash('sha256', $invoicesCsv),
                'transactions.csv' => hash('sha256', $transactionsCsv),
                'tax_report.csv' => hash('sha256', $taxCsv),
            ],
            'system' => 'HotelOS Enterprise v5.0',
            'signature' => hash_hmac('sha256', $invoicesCsv . $transactionsCsv, getenv('APP_KEY') ?: 'hotelos_secret')
        ];

        $zip->addFromString("manifest.json", json_encode($manifest, JSON_PRETTY_PRINT));
        
        // 3. Add Readme
        $zip->addFromString("README.txt", "HotelOS Compliance Export\nGenerated: " . date('Y-m-d H:i:s') . "\n\nThis package contains raw financial data for external audit.\nVerify integrity using manifest.json hashes.");

        $zip->close();
        
        return $zipFile;
    }

    private function generateInvoicesCSV(string $start, string $end): string
    {
        // ... (fetching logic)
        $data = $this->db->query(
            "SELECT booking_number, created_at, check_in_date, check_out_date, 
                    grand_total, tax_total, payment_status 
             FROM bookings 
             WHERE tenant_id = :tid 
               AND created_at BETWEEN :start AND :end",
             ['tid' => TenantContext::getId(), 'start' => $start, 'end' => $end . ' 23:59:59'],
             enforceTenant: false
        );
        return $this->arrayToCsv($data, ['Invoice #', 'Date', 'Check-In', 'Check-Out', 'Amount', 'Tax', 'Status']);
    }

    private function generateTransactionsCSV(string $start, string $end): string
    {
        $data = $this->db->query(
            "SELECT transaction_number, created_at, type, amount, payment_mode, category 
             FROM transactions 
             WHERE tenant_id = :tid 
               AND created_at BETWEEN :start AND :end",
             ['tid' => TenantContext::getId(), 'start' => $start, 'end' => $end . ' 23:59:59'],
             enforceTenant: false
        );
        return $this->arrayToCsv($data, ['Txn ID', 'Date', 'Type', 'Amount', 'Mode', 'Category']);
    }

    private function generateTaxCSV(string $start, string $end): string
    {
         $data = $this->db->query(
            "SELECT b.booking_number, b.created_at, b.grand_total as taxable_value, 
                    (b.grand_total * 0.12) as estimated_gst -- Simplified for Example
             FROM bookings b
             WHERE tenant_id = :tid AND b.payment_status = 'paid'
               AND created_at BETWEEN :start AND :end",
             ['tid' => TenantContext::getId(), 'start' => $start, 'end' => $end . ' 23:59:59'],
             enforceTenant: false
        );
        return $this->arrayToCsv($data, ['Invoice', 'Date', 'Taxable Value', 'GST Amount']);
    }

    private function arrayToCsv(array $data, array $headers): string
    {
        if (empty($data)) return implode(',', $headers);
        
        $fp = fopen('php://temp', 'r+');
        fputcsv($fp, $headers);
        foreach ($data as $row) {
            fputcsv($fp, $row);
        }
        rewind($fp);
        return stream_get_contents($fp);
    }
}
