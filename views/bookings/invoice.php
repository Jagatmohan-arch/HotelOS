<?php
/**
 * HotelOS - Printable Invoice View
 * Print-friendly invoice with GST breakdown
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?= htmlspecialchars($invoice['invoice_number']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            font-size: 14px; 
            line-height: 1.5;
            color: #1e293b;
            background: #fff;
        }
        .invoice { max-width: 800px; margin: 20px auto; padding: 40px; }
        
        /* Header */
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; border-bottom: 2px solid #0ea5e9; padding-bottom: 20px; }
        .hotel-info h1 { font-size: 24px; color: #0ea5e9; margin-bottom: 5px; }
        .hotel-info p { color: #64748b; font-size: 13px; }
        .invoice-title { text-align: right; }
        .invoice-title h2 { font-size: 28px; color: #1e293b; }
        .invoice-title .invoice-number { color: #64748b; font-size: 14px; }
        
        /* Details Grid */
        .details { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px; }
        .detail-box h3 { font-size: 12px; text-transform: uppercase; color: #64748b; margin-bottom: 10px; letter-spacing: 0.5px; }
        .detail-box p { margin: 3px 0; }
        .detail-box .name { font-weight: 600; font-size: 16px; }
        
        /* Table */
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { background: #f1f5f9; padding: 12px 15px; text-align: left; font-weight: 600; font-size: 12px; text-transform: uppercase; color: #475569; }
        td { padding: 12px 15px; border-bottom: 1px solid #e2e8f0; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        /* Totals */
        .totals { display: flex; justify-content: flex-end; }
        .totals-box { width: 300px; }
        .totals-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e2e8f0; }
        .totals-row.total { font-weight: 700; font-size: 18px; background: #f1f5f9; padding: 12px; margin-top: 10px; border-radius: 4px; }
        .totals-row.balance { color: #dc2626; }
        
        /* Footer */
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; }
        .footer-section h4 { font-size: 12px; text-transform: uppercase; color: #64748b; margin-bottom: 5px; }
        .footer-section p { font-size: 12px; color: #475569; }
        
        /* Print */
        .no-print { margin-bottom: 20px; text-align: center; }
        .print-btn { background: #0ea5e9; color: white; border: none; padding: 12px 30px; border-radius: 6px; cursor: pointer; font-size: 16px; }
        .print-btn:hover { background: #0284c7; }
        
        @media print {
            .no-print { display: none; }
            .invoice { margin: 0; padding: 20px; }
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="print-btn" onclick="window.print()">🖨️ Print Invoice</button>
        <a href="/bookings" style="margin-left: 20px; color: #0ea5e9;">← Back to Front Desk</a>
    </div>
    
    <div class="invoice">
        <!-- Header -->
        <div class="header">
            <div class="hotel-info">
                <h1><?= htmlspecialchars($hotel['name'] ?? 'Hotel') ?></h1>
                <p><?= htmlspecialchars($hotel['address'] ?? '') ?></p>
                <p>Phone: <?= htmlspecialchars($hotel['phone'] ?? '') ?> | Email: <?= htmlspecialchars($hotel['email'] ?? '') ?></p>
                <?php if (!empty($hotel['gst_number'])): ?>
                    <p><strong>GSTIN:</strong> <?= htmlspecialchars($hotel['gst_number']) ?></p>
                <?php endif; ?>
            </div>
            <div class="invoice-title">
                <h2>TAX INVOICE</h2>
                <p class="invoice-number"><?= htmlspecialchars($invoice['invoice_number']) ?></p>
                <p>Date: <?= date('d M Y', strtotime($invoice['generated_at'])) ?></p>
            </div>
        </div>
        
        <!-- Guest & Stay Details -->
        <div class="details">
            <div class="detail-box">
                <h3>Bill To</h3>
                <p class="name"><?= htmlspecialchars($guest['name']) ?></p>
                <?php if ($guest['company']): ?>
                    <p><?= htmlspecialchars($guest['company']) ?></p>
                <?php endif; ?>
                <?php if ($guest['gstin']): ?>
                    <p>GSTIN: <?= htmlspecialchars($guest['gstin']) ?></p>
                <?php endif; ?>
                <p><?= htmlspecialchars($guest['address']) ?></p>
                <p>Phone: <?= htmlspecialchars($guest['phone']) ?></p>
            </div>
            <div class="detail-box">
                <h3>Stay Details</h3>
                <p><strong>Room:</strong> <?= htmlspecialchars($room['number']) ?> (<?= htmlspecialchars($room['type']) ?>)</p>
                <p><strong>Check-in:</strong> <?= date('d M Y', strtotime($stay['check_in'])) ?></p>
                <p><strong>Check-out:</strong> <?= date('d M Y', strtotime($stay['check_out'])) ?></p>
                <p><strong>Nights:</strong> <?= $stay['nights'] ?></p>
                <p><strong>Guests:</strong> <?= $stay['adults'] ?> Adult(s), <?= $stay['children'] ?> Child(ren)</p>
            </div>
        </div>
        
        <!-- Charges Table -->
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Rate</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Room Charges - <?= htmlspecialchars($room['type']) ?></td>
                    <td class="text-center"><?= $stay['nights'] ?> Night(s)</td>
                    <td class="text-right">₹<?= number_format($charges['room_rate'], 2) ?></td>
                    <td class="text-right">₹<?= number_format($charges['room_total'], 2) ?></td>
                </tr>
                <?php if ($charges['extra'] > 0): ?>
                <tr>
                    <td>Extra Charges (Minibar, Laundry, etc.)</td>
                    <td class="text-center">-</td>
                    <td class="text-right">-</td>
                    <td class="text-right">₹<?= number_format($charges['extra'], 2) ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($charges['discount'] > 0): ?>
                <tr>
                    <td>Discount</td>
                    <td class="text-center">-</td>
                    <td class="text-right">-</td>
                    <td class="text-right" style="color: #22c55e;">- ₹<?= number_format($charges['discount'], 2) ?></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Totals -->
        <div class="totals">
            <div class="totals-box">
                <div class="totals-row">
                    <span>Taxable Amount</span>
                    <span>₹<?= number_format($charges['taxable'], 2) ?></span>
                </div>
                <div class="totals-row">
                    <span>CGST @ <?= $charges['gst_rate']/2 ?>%</span>
                    <span>₹<?= number_format($charges['cgst'], 2) ?></span>
                </div>
                <div class="totals-row">
                    <span>SGST @ <?= $charges['gst_rate']/2 ?>%</span>
                    <span>₹<?= number_format($charges['sgst'], 2) ?></span>
                </div>
                <div class="totals-row total">
                    <span>Grand Total</span>
                    <span>₹<?= number_format($charges['grand_total'], 2) ?></span>
                </div>
                <div class="totals-row">
                    <span>Amount Paid</span>
                    <span>₹<?= number_format($charges['paid'], 2) ?></span>
                </div>
                <?php if ($charges['balance'] > 0): ?>
                <div class="totals-row balance">
                    <span>Balance Due</span>
                    <span>₹<?= number_format($charges['balance'], 2) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div class="footer-section">
                <h4>Payment Information</h4>
                <p>All payments to be made in Indian Rupees (₹)</p>
                <p>UPI / Bank Transfer / Cash / Card accepted</p>
            </div>
            <div class="footer-section">
                <h4>Thank You!</h4>
                <p>We hope you enjoyed your stay.</p>
                <p>Please visit us again.</p>
            </div>
        </div>
    </div>
</body>
</html>
