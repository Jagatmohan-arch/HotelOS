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
        <a href="javascript:history.back()" class="btn-back">← Back</a>
    </div>
</body>
</html>
