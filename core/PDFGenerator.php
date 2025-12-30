<?php
/**
 * HotelOS - PDF Generator Utility
 * 
 * Lightweight PDF generation using native PHP
 * No external dependencies required for basic invoices
 * Uses HTML to PDF conversion for simplicity
 */

declare(strict_types=1);

namespace HotelOS\Utils;

class PDFGenerator
{
    /**
     * Generate PDF from HTML content
     * Uses browser print CSS for simple PDF generation
     * 
     * @param string $html HTML content
     * @param string $filename Output filename
     * @param bool $download Force download (true) or display inline (false)
     */
    public static function generateFromHTML(string $html, string $filename, bool $download = true): void
    {
        // For Phase F, we'll use a simple approach:
        // Generate HTML with print-optimized CSS that renders well as PDF
        
        $printCSS = self::getPrintCSS();
        
        $fullHTML = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{$filename}</title>
    <style>
        {$printCSS}
    </style>
</head>
<body>
    {$html}
    <script>
        // Auto-print when opened (user can save as PDF)
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
HTML;

        // Set headers for PDF-like behavior
        header('Content-Type: text/html; charset=UTF-8');
        
        if ($download) {
            header('Content-Disposition: attachment; filename="' . $filename . '.html"');
        } else {
            header('Content-Disposition: inline; filename="' . $filename . '.html"');
        }
        
        echo $fullHTML;
        exit;
    }
    
    /**
     * Get print-optimized CSS for PDF rendering
     */
    private static function getPrintCSS(): string
    {
        return <<<CSS
        @page {
            size: A4;
            margin: 15mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #000;
            background: #fff;
        }
        
        .invoice-container {
            max-width: 210mm;
            margin: 0 auto;
            padding: 10mm;
            background: white;
        }
        
        .header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .hotel-name {
            font-size: 20pt;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 5px;
        }
        
        .invoice-title {
            font-size: 16pt;
            font-weight: bold;
            text-align: right;
            margin-top: -30px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        table.info-table td {
            padding: 5px;
            vertical-align: top;
        }
        
        table.items-table {
            border: 1px solid #000;
        }
        
        table.items-table th {
            background: #f0f0f0;
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
            font-weight: bold;
        }
        
        table.items-table td {
            border: 1px solid #000;
            padding: 8px;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .bold {
            font-weight: bold;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ccc;
            font-size: 9pt;
            color: #666;
        }
        
        .totals-section {
            margin-top: 20px;
            float: right;
            width: 300px;
        }
        
        .totals-section table td {
            padding: 5px 10px;
        }
        
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .no-print {
                display: none;
            }
        }
CSS;
    }
    
    /**
     * Number to words conversion for Indian Rupees
     */
    public static function numberToWords(float $number): string
    {
        $number = (int)$number;
        
        if ($number === 0) {
            return 'Zero Rupees Only';
        }
        
        $words = '';
        $crore = (int)($number / 10000000);
        if ($crore > 0) {
            $words .= self::convertBelowThousand($crore) . ' Crore ';
            $number %= 10000000;
        }
        
        $lakh = (int)($number / 100000);
        if ($lakh > 0) {
            $words .= self::convertBelowThousand($lakh) . ' Lakh ';
            $number %= 100000;
        }
        
        $thousand = (int)($number / 1000);
        if ($thousand > 0) {
            $words .= self::convertBelowThousand($thousand) . ' Thousand ';
            $number %= 1000;
        }
        
        if ($number > 0) {
            $words .= self::convertBelowThousand($number) . ' ';
        }
        
        return trim($words) . ' Rupees Only';
    }
    
    private static function convertBelowThousand(int $number): string
    {
        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
        $teens = ['Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        
        $words = '';
        
        if ($number >= 100) {
            $words .= $ones[(int)($number / 100)] . ' Hundred ';
            $number %= 100;
        }
        
        if ($number >= 20) {
            $words .= $tens[(int)($number / 10)] . ' ';
            $number %= 10;
        } elseif ($number >= 10) {
            $words .= $teens[$number - 10] . ' ';
            return trim($words);
        }
        
        if ($number > 0) {
            $words .= $ones[$number] . ' ';
        }
        
        return trim($words);
    }
}
