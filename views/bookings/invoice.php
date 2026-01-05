<?php
/**
 * HotelOS - B&W Print-Optimized Invoice
 * 
 * Supports:
 * - Thermal printers (80mm)
 * - A4/Letter printers
 * - Pure black & white design
 * - Amount in words (Indian format)
 */

$invoice = $invoice ?? null;

if (!$invoice) {
    echo '<h1>Invoice not found</h1>';
    return;
}

$hotel = $invoice['hotel'];
$guest = $invoice['guest'];
$room = $invoice['room'];
$stay = $invoice['stay'];
$charges = $invoice['charges'];
$booking = $invoice['booking'];

// Branding assets (logo, stamp, signature)
$branding = $branding ?? [];

// Print mode: 'a4' or 'thermal'
$printMode = $_GET['mode'] ?? 'a4';

// GST Toggle: on or off (off = Estimate Slip)
$showGST = ($_GET['gst'] ?? 'on') !== 'off';
$documentTitle = $showGST ? 'TAX INVOICE' : 'ESTIMATE SLIP';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?= htmlspecialchars($invoice['invoice_number']) ?></title>
    <link rel="stylesheet" href="/assets/css/invoice-print.css">
    <script src="/assets/js/number-to-words.js"></script>
</head>
<body>
    <div class="invoice-wrapper">
        <!-- Action Buttons (hidden on print) -->
        <div class="invoice-actions no-print">
            <button class="btn-print" onclick="window.print()">
                🖨️ Print <?= $showGST ? 'Invoice' : 'Slip' ?>
            </button>
            <button class="btn-print btn-secondary" onclick="toggleMode()">
                📄 <?= $printMode === 'thermal' ? 'A4 Mode' : 'Thermal Mode' ?>
            </button>
            <button class="btn-print <?= $showGST ? 'btn-secondary' : 'btn-gst-on' ?>" onclick="toggleGST()">
                🧾 <?= $showGST ? 'Without GST' : 'With GST' ?>
            </button>
            <a href="/bookings" class="btn-print btn-secondary">
                ← Back to Front Desk
            </a>
        </div>
        
        <!-- Invoice Type Help (hidden on print) -->
        <div class="no-print" style="max-width: 800px; margin: 0 auto 15px; padding: 10px; background: #334155; border-radius: 8px; font-size: 12px; color: #94a3b8;">
            <strong style="color: #e2e8f0;">📋 Document Types:</strong> 
            <strong>Tax Invoice</strong> = Official GST document for accounting | 
            <strong>Estimate Slip</strong> = Pre-payment receipt without tax
        </div>
        
        <!-- Invoice Container -->
        <div class="invoice-container <?= $printMode === 'thermal' ? 'thermal' : '' ?>">
            <div class="invoice-box">
                
                <!-- Header - Hotel Info -->
                <div class="invoice-header">
                    <div class="header-content">
                        <?php if (!empty($branding['logo'])): ?>
                        <img src="<?= htmlspecialchars($branding['logo']) ?>" alt="Logo" class="hotel-logo">
                        <?php endif; ?>
                        <div class="hotel-info">
                            <h1><?= strtoupper(htmlspecialchars($hotel['name'] ?? 'HOTEL')) ?></h1>
                            <?php if (!empty($hotel['address'])): ?>
                            <p class="hotel-address"><?= htmlspecialchars($hotel['address']) ?></p>
                            <?php endif; ?>
                            <p class="hotel-contact">
                                <?php if (!empty($hotel['phone'])): ?>
                                Tel: <?= htmlspecialchars($hotel['phone']) ?>
                                <?php endif; ?>
                                <?php if (!empty($hotel['email'])): ?>
                                | Email: <?= htmlspecialchars($hotel['email']) ?>
                                <?php endif; ?>
                            </p>
                            <?php if (!empty($hotel['gst_number'])): ?>
                            <p class="hotel-gst">GSTIN: <?= htmlspecialchars($hotel['gst_number']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Invoice Title & Number -->
                <div class="invoice-title-bar">
                    <span class="invoice-title"><?= $documentTitle ?></span>
                    <?php if (!$showGST): ?>
                    <span class="estimate-badge">Non-GST Document</span>
                    <?php endif; ?>
                    <div class="invoice-meta">
                        <strong><?= htmlspecialchars($invoice['invoice_number']) ?></strong>
                        Date: <?= date('d/m/Y', strtotime($invoice['generated_at'])) ?>
                    </div>
                </div>
                
                <!-- Guest & Stay Details -->
                <div class="invoice-details">
                    <div class="invoice-details-row">
                        <div class="invoice-details-cell">
                            <h4>Bill To</h4>
                            <p class="name"><?= htmlspecialchars($guest['name']) ?></p>
                            <?php if (!empty($guest['company'])): ?>
                            <p><?= htmlspecialchars($guest['company']) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($guest['gstin'])): ?>
                            <p><strong>GSTIN:</strong> <?= htmlspecialchars($guest['gstin']) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($guest['address'])): ?>
                            <p><?= htmlspecialchars($guest['address']) ?></p>
                            <?php endif; ?>
                            <p>Phone: <?= htmlspecialchars($guest['phone']) ?></p>
                        </div>
                        <div class="invoice-details-cell">
                            <h4>Stay Details</h4>
                            <p><strong>Room:</strong> <?= htmlspecialchars($room['number']) ?> (<?= htmlspecialchars($room['type']) ?>)</p>
                            <p><strong>Check-in:</strong> <?= date('d/m/Y', strtotime($stay['check_in'])) ?></p>
                            <p><strong>Check-out:</strong> <?= date('d/m/Y', strtotime($stay['check_out'])) ?></p>
                            <p><strong>Nights:</strong> <?= $stay['nights'] ?></p>
                            <p><strong>Guests:</strong> <?= $stay['adults'] ?> Adult(s)<?= $stay['children'] > 0 ? ', ' . $stay['children'] . ' Child(ren)' : '' ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Charges Table -->
                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th class="text-center">HSN/SAC</th>
                            <th class="text-center">Qty</th>
                            <th class="text-right">Rate (₹)</th>
                            <th class="text-right">Amount (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                Room Charges - <?= htmlspecialchars($room['type']) ?>
                            </td>
                            <td class="text-center">996311</td>
                            <td class="text-center"><?= $stay['nights'] ?></td>
                            <td class="text-right"><?= number_format($charges['room_rate'], 2) ?></td>
                            <td class="text-right"><?= number_format($charges['room_total'], 2) ?></td>
                        </tr>
                        <?php if ($charges['extra'] > 0): ?>
                        <tr>
                            <td>Extra Charges (Minibar, Laundry, Services)</td>
                            <td class="text-center">996311</td>
                            <td class="text-center">-</td>
                            <td class="text-right">-</td>
                            <td class="text-right"><?= number_format($charges['extra'], 2) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (isset($charges['discount']) && $charges['discount'] > 0): ?>
                        <tr>
                            <td>Discount</td>
                            <td class="text-center">-</td>
                            <td class="text-center">-</td>
                            <td class="text-right">-</td>
                            <td class="text-right">(-) <?= number_format($charges['discount'], 2) ?></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <!-- Totals Section -->
                <div class="invoice-totals">
                    <div class="invoice-totals-row">
                        <div class="invoice-totals-spacer"></div>
                        <div class="invoice-totals-content">
                            <div class="totals-line subtotal">
                                <span>Taxable Amount:</span>
                                <span>₹<?= number_format($charges['taxable'], 2) ?></span>
                            </div>
                            <?php if ($showGST): ?>
                                <?php if (isset($charges['cgst']) && $charges['cgst'] > 0): ?>
                                <div class="totals-line">
                                    <span>CGST @ <?= $charges['gst_rate']/2 ?>%:</span>
                                    <span>₹<?= number_format($charges['cgst'], 2) ?></span>
                                </div>
                                <div class="totals-line">
                                    <span>SGST @ <?= $charges['gst_rate']/2 ?>%:</span>
                                    <span>₹<?= number_format($charges['sgst'], 2) ?></span>
                                </div>
                                <?php elseif (isset($charges['igst']) && $charges['igst'] > 0): ?>
                                <div class="totals-line">
                                    <span>IGST @ <?= $charges['gst_rate'] ?>%:</span>
                                    <span>₹<?= number_format($charges['igst'], 2) ?></span>
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            <div class="totals-line grand-total">
                                <span>GRAND TOTAL:</span>
                                <span>₹<?= number_format($charges['grand_total'], 2) ?></span>
                            </div>
                            <div class="totals-line paid">
                                <span>Amount Paid:</span>
                                <span>₹<?= number_format($charges['paid'], 2) ?></span>
                            </div>
                            <?php if ($charges['balance'] > 0): ?>
                            <div class="totals-line balance">
                                <span>Balance Due:</span>
                                <span>₹<?= number_format($charges['balance'], 2) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Amount in Words -->
                <div class="amount-words">
                    <strong>Amount in Words:</strong>
                    <span id="amountInWords"></span>
                </div>
                
                <!-- Payment Info -->
                <div class="payment-info">
                    <div class="payment-info-cell">
                        <h4>Payment Details</h4>
                        <p><strong>Mode:</strong> <?= htmlspecialchars($booking['payment_mode'] ?? 'Cash') ?></p>
                        <?php if (!empty($booking['payment_reference'])): ?>
                        <p><strong>Ref:</strong> <?= htmlspecialchars($booking['payment_reference']) ?></p>
                        <?php endif; ?>
                        <p>UPI / Card / Cash / Bank Transfer</p>
                    </div>
                    <div class="payment-info-cell">
                        <h4>Bank Details</h4>
                        <p>Account: <?= htmlspecialchars($hotel['bank_account'] ?? 'On Request') ?></p>
                        <p>IFSC: <?= htmlspecialchars($hotel['bank_ifsc'] ?? 'On Request') ?></p>
                        <p>Bank: <?= htmlspecialchars($hotel['bank_name'] ?? 'On Request') ?></p>
                    </div>
                </div>
                
                <!-- Signature Section -->
                <div class="signature-section">
                    <div class="signature-box">
                        <div class="signature-line">Guest Signature</div>
                    </div>
                    <div class="signature-box">
                        <?php if (!empty($branding['signature'])): ?>
                        <img src="<?= htmlspecialchars($branding['signature']) ?>" alt="Signature" class="signature-image">
                        <?php endif; ?>
                        <?php if (!empty($branding['stamp'])): ?>
                        <img src="<?= htmlspecialchars($branding['stamp']) ?>" alt="Stamp" class="stamp-image">
                        <?php endif; ?>
                        <div class="signature-line">For <?= htmlspecialchars($hotel['name'] ?? 'Hotel') ?></div>
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="invoice-footer">
                    <p class="thank-you">Thank You for Staying With Us!</p>
                    <p class="legal">
                        E&OE | Subject to <?= htmlspecialchars($hotel['city'] ?? 'Local') ?> Jurisdiction | 
                        This is a computer-generated invoice
                    </p>
                </div>
                
            </div>
        </div>
    </div>
    
    <script>
        // Convert amount to words on load
        document.addEventListener('DOMContentLoaded', function() {
            const grandTotal = <?= json_encode($charges['grand_total']) ?>;
            document.getElementById('amountInWords').textContent = numberToWords(grandTotal);
        });
        
        // Toggle between A4 and Thermal mode
        function toggleMode() {
            const currentUrl = new URL(window.location.href);
            const currentMode = currentUrl.searchParams.get('mode') || 'a4';
            currentUrl.searchParams.set('mode', currentMode === 'thermal' ? 'a4' : 'thermal');
            window.location.href = currentUrl.toString();
        }
        
        // Toggle GST on/off (Tax Invoice vs Estimate Slip)
        function toggleGST() {
            const currentUrl = new URL(window.location.href);
            const currentGST = currentUrl.searchParams.get('gst') || 'on';
            currentUrl.searchParams.set('gst', currentGST === 'off' ? 'on' : 'off');
            window.location.href = currentUrl.toString();
        }
        
        // Keyboard shortcut: Ctrl+P for print
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
        });
    </script>
</body>
</html>
