<?php
/**
 * HotelOS - Money Receipt View
 * Printable receipt for each payment
 */

$tx = $transaction ?? null;
if (!$tx) {
    echo '<div class="text-center py-8 text-red-400">Transaction not found</div>';
    return;
}

$receiptNo = 'MR-' . date('ymd', strtotime($tx['collected_at'] ?? 'now')) . '-' . str_pad((string)$tx['id'], 3, '0', STR_PAD_LEFT);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Money Receipt - <?= $receiptNo ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .receipt-container {
            max-width: 400px;
            margin: 0 auto;
            background: white;
            border: 2px solid #333;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .receipt-header {
            background: linear-gradient(135deg, #1e3a5f, #0f172a);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .receipt-header h1 {
            font-size: 24px;
            margin-bottom: 5px;
            letter-spacing: 2px;
        }
        
        .receipt-header .subtitle {
            font-size: 12px;
            opacity: 0.8;
        }
        
        .receipt-meta {
            display: flex;
            justify-content: space-between;
            padding: 15px 20px;
            background: #f8fafc;
            border-bottom: 1px dashed #ddd;
            font-size: 13px;
        }
        
        .receipt-body {
            padding: 20px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dotted #eee;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #666;
            font-size: 13px;
        }
        
        .info-value {
            font-weight: 600;
            color: #333;
            text-align: right;
        }
        
        .amount-section {
            background: #f0fdf4;
            border: 2px solid #22c55e;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            text-align: center;
        }
        
        .amount-large {
            font-size: 32px;
            font-weight: 700;
            color: #16a34a;
        }
        
        .amount-words {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            font-style: italic;
        }
        
        .payment-mode {
            display: inline-block;
            background: #e0f2fe;
            color: #0369a1;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 10px;
        }
        
        .summary-section {
            background: #fafafa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 13px;
        }
        
        .summary-row.total {
            border-top: 2px solid #333;
            margin-top: 8px;
            padding-top: 10px;
            font-weight: 700;
            font-size: 14px;
        }
        
        .summary-row.balance {
            color: #dc2626;
        }
        
        .receipt-footer {
            background: #f8fafc;
            padding: 15px 20px;
            border-top: 1px dashed #ddd;
            font-size: 11px;
            text-align: center;
            color: #666;
        }
        
        .hotel-info {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        
        .print-actions {
            text-align: center;
            padding: 20px;
            background: #f5f5f5;
        }
        
        .btn-print {
            background: #0f172a;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            margin: 0 5px;
        }
        
        .btn-print:hover {
            background: #1e293b;
        }
        
        .btn-back {
            background: #64748b;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 0 5px;
        }
        
        .btn-whatsapp {
            background: #25D366;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 0 5px;
        }
        
        .btn-whatsapp:hover {
            background: #128C7E;
        }
        
        @media print {
            body { 
                background: white; 
                padding: 0;
            }
            .print-actions { 
                display: none; 
            }
            .receipt-container {
                border: 1px solid #000;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <!-- Header -->
        <div class="receipt-header">
            <h1>MONEY RECEIPT</h1>
            <div class="subtitle">Payment Acknowledgement</div>
        </div>
        
        <!-- Meta Info -->
        <div class="receipt-meta">
            <div>
                <strong>Receipt #:</strong> <?= htmlspecialchars($receiptNo) ?>
            </div>
            <div>
                <strong>Date:</strong> <?= date('d M Y, h:i A', strtotime($tx['collected_at'] ?? 'now')) ?>
            </div>
        </div>
        
        <!-- Body -->
        <div class="receipt-body">
            <!-- Guest Info -->
            <div class="info-row">
                <span class="info-label">Guest Name</span>
                <span class="info-value"><?= htmlspecialchars(($tx['first_name'] ?? '') . ' ' . ($tx['last_name'] ?? '')) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Room</span>
                <span class="info-value"><?= htmlspecialchars($tx['room_number'] ?? 'N/A') ?> (<?= htmlspecialchars($tx['room_type'] ?? '-') ?>)</span>
            </div>
            <div class="info-row">
                <span class="info-label">Booking #</span>
                <span class="info-value"><?= htmlspecialchars($tx['booking_number'] ?? '-') ?></span>
            </div>
            
            <!-- Amount Section -->
            <div class="amount-section">
                <div class="amount-large">₹<?= number_format((float)$tx['amount']) ?></div>
                <div class="amount-words">(<?= htmlspecialchars($tx['amount_in_words'] ?? '') ?>)</div>
                <div class="payment-mode">
                    <?= strtoupper($tx['payment_mode'] ?? 'CASH') ?>
                    <?php if (!empty($tx['reference_number'])): ?>
                    <br><small>Ref: <?= htmlspecialchars($tx['reference_number']) ?></small>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Summary -->
            <div class="summary-section">
                <div class="summary-row">
                    <span>Previous Paid</span>
                    <span>₹<?= number_format((float)($tx['previous_paid'] ?? 0)) ?></span>
                </div>
                <div class="summary-row">
                    <span>This Payment</span>
                    <span>₹<?= number_format((float)$tx['amount']) ?></span>
                </div>
                <div class="summary-row total">
                    <span>Total Paid</span>
                    <span>₹<?= number_format((float)$tx['paid_amount']) ?></span>
                </div>
                <div class="summary-row balance">
                    <span>Balance Due</span>
                    <span>₹<?= number_format((float)($tx['balance_after'] ?? 0)) ?></span>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="receipt-footer">
            <div>
                <strong>Received by:</strong> 
                <?= htmlspecialchars(($tx['collector_first_name'] ?? '') . ' ' . ($tx['collector_last_name'] ?? '')) ?>
            </div>
            <div class="hotel-info">
                <strong><?= htmlspecialchars($tx['hotel_name'] ?? 'Hotel') ?></strong><br>
                <?= htmlspecialchars($tx['hotel_address'] ?? '') ?><br>
                <?php if (!empty($tx['gst_number'])): ?>
                GSTIN: <?= htmlspecialchars($tx['gst_number']) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Print Actions -->
    <div class="print-actions">
        <button class="btn-print" onclick="window.print()">🖨️ Print Receipt</button>
        <?php
        // WhatsApp share message
        $guestPhone = $tx['guest_phone'] ?? '';
        $waMessage = "🧾 *Money Receipt*\n\n";
        $waMessage .= "Receipt: {$receiptNo}\n";
        $waMessage .= "Amount: ₹" . number_format((float)$tx['amount']) . "\n";
        $waMessage .= "Mode: " . strtoupper($tx['payment_mode'] ?? 'Cash') . "\n";
        $waMessage .= "Balance: ₹" . number_format((float)($tx['balance_after'] ?? 0)) . "\n\n";
        $waMessage .= "Thank you for staying with us!\n";
        $waMessage .= "- " . ($tx['hotel_name'] ?? 'Hotel');
        $waLink = "https://wa.me/91" . preg_replace('/[^0-9]/', '', $guestPhone) . "?text=" . urlencode($waMessage);
        ?>
        <a href="<?= $waLink ?>" target="_blank" class="btn-whatsapp">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
            </svg>
            Send via WhatsApp
        </a>
        <a href="javascript:history.back()" class="btn-back">← Back</a>
    </div>
</body>
</html>
