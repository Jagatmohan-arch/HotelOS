<?php
/**
 * HotelOS - Reports Page
 * Revenue, Occupancy, and GST reports
 */

$activeTab = $activeTab ?? 'revenue';
$startDate = $startDate ?? date('Y-m-01');
$endDate = $endDate ?? date('Y-m-d');
$reportData = $reportData ?? [];
$summary = $summary ?? [];
?>

<div class="reports-page animate-fadeIn">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white">Reports</h1>
            <p class="text-slate-400 text-sm mt-1">Revenue, Occupancy & GST Analytics</p>
        </div>
        
        <!-- Date Range Filter -->
        <form method="GET" class="flex gap-2 items-center">
            <input type="hidden" name="tab" value="<?= $activeTab ?>">
            <input type="date" name="start" value="<?= $startDate ?>" 
                   class="form-input py-1.5 text-sm w-36">
            <span class="text-slate-500">to</span>
            <input type="date" name="end" value="<?= $endDate ?>" 
                   class="form-input py-1.5 text-sm w-36">
            <button type="submit" class="btn btn--primary py-1.5">
                <i data-lucide="filter" class="w-4 h-4"></i>
                Apply
            </button>
        </form>
    </div>
    
    <!-- Tabs -->
    <div class="flex gap-2 mb-6 overflow-x-auto pb-2">
        <a href="/reports?tab=revenue&start=<?= $startDate ?>&end=<?= $endDate ?>" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition-colors whitespace-nowrap <?= $activeTab === 'revenue' ? 'bg-cyan-500/20 text-cyan-400 border border-cyan-500/30' : 'bg-slate-800/50 text-slate-400 hover:text-white' ?>">
            💰 Revenue
        </a>
        <a href="/reports?tab=occupancy&start=<?= $startDate ?>&end=<?= $endDate ?>" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition-colors whitespace-nowrap <?= $activeTab === 'occupancy' ? 'bg-cyan-500/20 text-cyan-400 border border-cyan-500/30' : 'bg-slate-800/50 text-slate-400 hover:text-white' ?>">
            🛏️ Occupancy
        </a>
        <!-- Phase F: Police Report Link -->
        <a href="/reports?tab=police" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition-colors whitespace-nowrap <?= $activeTab === 'police' ? 'bg-cyan-500/20 text-cyan-400 border border-cyan-500/30' : 'bg-slate-800/50 text-slate-400 hover:text-white' ?>">
            🚓 Police Report
        </a>
        <a href="/reports?tab=gst&start=<?= $startDate ?>&end=<?= $endDate ?>" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition-colors whitespace-nowrap <?= $activeTab === 'gst' ? 'bg-cyan-500/20 text-cyan-400 border border-cyan-500/30' : 'bg-slate-800/50 text-slate-400 hover:text-white' ?>">
            📋 GST Summary
        </a>
        <a href="/reports?tab=rooms&start=<?= $startDate ?>&end=<?= $endDate ?>" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition-colors whitespace-nowrap <?= $activeTab === 'rooms' ? 'bg-cyan-500/20 text-cyan-400 border border-cyan-500/30' : 'bg-slate-800/50 text-slate-400 hover:text-white' ?>">
            🚪 Room-wise
        </a>
        <a href="/reports?tab=credit-notes&start=<?= $startDate ?>&end=<?= $endDate ?>" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition-colors whitespace-nowrap <?= $activeTab === 'credit-notes' ? 'bg-cyan-500/20 text-cyan-400 border border-cyan-500/30' : 'bg-slate-800/50 text-slate-400 hover:text-white' ?>">
            📄 Credit Notes
        </a>
    </div>
    
    <?php if ($activeTab === 'revenue'): ?>
    <!-- Revenue Report -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="stats-card stats-card--cyan">
            <div class="stats-value">₹<?= number_format((float)($summary['total_revenue'] ?? 0)) ?></div>
            <div class="stats-label">Total Revenue</div>
        </div>
        <div class="stats-card stats-card--green">
            <div class="stats-value">₹<?= number_format((float)($summary['cash_total'] ?? 0)) ?></div>
            <div class="stats-label">Cash</div>
        </div>
        <div class="stats-card stats-card--purple">
            <div class="stats-value">₹<?= number_format((float)($summary['upi_total'] ?? 0)) ?></div>
            <div class="stats-label">UPI</div>
        </div>
        <div class="stats-card stats-card--gold">
            <div class="stats-value">₹<?= number_format((float)($summary['card_total'] ?? 0)) ?></div>
            <div class="stats-label">Card</div>
        </div>
    </div>
    
    <div class="glass-card overflow-hidden">
        <div class="p-4 border-b border-slate-700/50">
            <h3 class="font-semibold text-white">Daily Revenue</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th class="text-right">Transactions</th>
                        <th class="text-right">Cash</th>
                        <th class="text-right">UPI</th>
                        <th class="text-right">Card</th>
                        <th class="text-right">Other</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reportData)): ?>
                    <tr><td colspan="7" class="text-center text-slate-500 py-8">No transactions found</td></tr>
                    <?php else: ?>
                    <?php foreach ($reportData as $row): ?>
                    <tr>
                        <td class="text-white"><?= date('d M Y', strtotime($row['date'])) ?></td>
                        <td class="text-right"><?= $row['transaction_count'] ?></td>
                        <td class="text-right text-emerald-400">₹<?= number_format((float)$row['cash']) ?></td>
                        <td class="text-right text-purple-400">₹<?= number_format((float)$row['upi']) ?></td>
                        <td class="text-right text-amber-400">₹<?= number_format((float)$row['card']) ?></td>
                        <td class="text-right text-slate-400">₹<?= number_format((float)$row['other']) ?></td>
                        <td class="text-right text-cyan-400 font-semibold">₹<?= number_format((float)$row['revenue']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php elseif ($activeTab === 'occupancy'): ?>
    <!-- Occupancy Report -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="stats-card stats-card--cyan">
            <div class="stats-value"><?= $summary['avg_occupancy_rate'] ?? 0 ?>%</div>
            <div class="stats-label">Avg Occupancy</div>
        </div>
        <div class="stats-card stats-card--green">
            <div class="stats-value"><?= $summary['total_bookings'] ?? 0 ?></div>
            <div class="stats-label">Total Bookings</div>
        </div>
        <div class="stats-card stats-card--purple">
            <div class="stats-value"><?= $summary['total_room_nights'] ?? 0 ?></div>
            <div class="stats-label">Room Nights</div>
        </div>
        <div class="stats-card stats-card--gold">
            <div class="stats-value">₹<?= number_format((float)($summary['avg_booking_value'] ?? 0)) ?></div>
            <div class="stats-label">Avg Booking Value</div>
        </div>
    </div>
    
    <div class="glass-card overflow-hidden">
        <div class="p-4 border-b border-slate-700/50">
            <h3 class="font-semibold text-white">Daily Occupancy</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th class="text-right">Rooms Occupied</th>
                        <th class="text-right">Occupancy %</th>
                        <th class="text-right">Bookings</th>
                        <th class="text-right">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reportData['daily'] ?? [])): ?>
                    <tr><td colspan="5" class="text-center text-slate-500 py-8">No data found</td></tr>
                    <?php else: ?>
                    <?php foreach ($reportData['daily'] as $row): ?>
                    <tr>
                        <td class="text-white"><?= date('d M Y', strtotime($row['date'])) ?></td>
                        <td class="text-right"><?= $row['rooms_occupied'] ?> / <?= $reportData['total_rooms'] ?></td>
                        <td class="text-right">
                            <span class="px-2 py-0.5 rounded text-xs <?= $row['occupancy_rate'] >= 70 ? 'bg-emerald-500/20 text-emerald-400' : ($row['occupancy_rate'] >= 40 ? 'bg-amber-500/20 text-amber-400' : 'bg-red-500/20 text-red-400') ?>">
                                <?= $row['occupancy_rate'] ?>%
                            </span>
                        </td>
                        <td class="text-right"><?= $row['bookings'] ?></td>
                        <td class="text-right text-cyan-400">₹<?= number_format((float)$row['revenue']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php elseif ($activeTab === 'gst'): ?>
    <!-- GST Summary -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="stats-card stats-card--cyan">
            <div class="stats-value"><?= $summary['invoice_count'] ?? 0 ?></div>
            <div class="stats-label">Invoices</div>
        </div>
        <div class="stats-card stats-card--green">
            <div class="stats-value">₹<?= number_format((float)($summary['taxable_amount'] ?? 0)) ?></div>
            <div class="stats-label">Taxable Amount</div>
        </div>
        <div class="stats-card stats-card--purple">
            <div class="stats-value">₹<?= number_format((float)($summary['cgst'] ?? 0)) ?></div>
            <div class="stats-label">CGST</div>
        </div>
        <div class="stats-card stats-card--gold">
            <div class="stats-value">₹<?= number_format((float)($summary['sgst'] ?? 0)) ?></div>
            <div class="stats-label">SGST</div>
        </div>
        <div class="stats-card" style="--card-accent: #f87171;">
            <div class="stats-value">₹<?= number_format((float)($summary['total_gst'] ?? 0)) ?></div>
            <div class="stats-label">Total GST</div>
        </div>
    </div>
    
    <div class="glass-card overflow-hidden">
        <div class="p-4 border-b border-slate-700/50 flex justify-between items-center">
            <h3 class="font-semibold text-white">GST Invoice Details</h3>
            <button onclick="window.print()" class="btn btn--secondary text-sm py-1">
                <i data-lucide="printer" class="w-4 h-4"></i>
                Print
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Invoice #</th>
                        <th>Guest</th>
                        <th>Room</th>
                        <th class="text-right">Taxable</th>
                        <th class="text-right">CGST</th>
                        <th class="text-right">SGST</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reportData['bookings'] ?? [])): ?>
                    <tr><td colspan="8" class="text-center text-slate-500 py-8">No invoices found</td></tr>
                    <?php else: ?>
                    <?php foreach ($reportData['bookings'] as $row): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($row['invoice_date'])) ?></td>
                        <td class="text-white"><?= htmlspecialchars($row['booking_number']) ?></td>
                        <td><?= htmlspecialchars($row['guest_name']) ?></td>
                        <td><?= htmlspecialchars($row['room_number']) ?></td>
                        <td class="text-right">₹<?= number_format((float)$row['taxable_amount']) ?></td>
                        <td class="text-right text-slate-400">₹<?= number_format((float)$row['cgst_amount']) ?></td>
                        <td class="text-right text-slate-400">₹<?= number_format((float)$row['sgst_amount']) ?></td>
                        <td class="text-right text-cyan-400 font-semibold">₹<?= number_format((float)$row['total_amount']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot class="bg-slate-800/50">
                    <tr class="font-semibold">
                        <td colspan="4" class="text-right">Total:</td>
                        <td class="text-right text-white">₹<?= number_format((float)($summary['taxable_amount'] ?? 0)) ?></td>
                        <td class="text-right text-white">₹<?= number_format((float)($summary['cgst'] ?? 0)) ?></td>
                        <td class="text-right text-white">₹<?= number_format((float)($summary['sgst'] ?? 0)) ?></td>
                        <td class="text-right text-cyan-400">₹<?= number_format((float)($summary['total_amount'] ?? 0)) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    
    <?php elseif ($activeTab === 'rooms'): ?>
    <!-- Room-wise Revenue -->
    <div class="glass-card overflow-hidden">
        <div class="p-4 border-b border-slate-700/50">
            <h3 class="font-semibold text-white">Room-wise Revenue</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Room</th>
                        <th>Type</th>
                        <th class="text-right">Bookings</th>
                        <th class="text-right">Avg Revenue</th>
                        <th class="text-right">Total Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reportData)): ?>
                    <tr><td colspan="5" class="text-center text-slate-500 py-8">No data found</td></tr>
                    <?php else: ?>
                    <?php foreach ($reportData as $row): ?>
                    <tr>
                        <td class="text-white font-semibold"><?= htmlspecialchars($row['room_number']) ?></td>
                        <td class="text-slate-400"><?= htmlspecialchars($row['room_type'] ?? '-') ?></td>
                        <td class="text-right"><?= (int)$row['booking_count'] ?></td>
                        <td class="text-right text-slate-400">₹<?= number_format((float)($row['avg_revenue'] ?? 0)) ?></td>
                        <td class="text-right text-cyan-400 font-semibold">₹<?= number_format((float)($row['total_revenue'] ?? 0)) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    
    <?php elseif ($activeTab === 'police'): ?>
        <?php include __DIR__ . '/police-report.php'; ?>
    
    <?php elseif ($activeTab === 'credit-notes'): ?>
    <!-- Credit Notes Report -->
    <?php
    // Fetch credit notes (approved refunds)
    $refundHandler = new \HotelOS\Handlers\RefundHandler();
    $creditNotes = $refundHandler->getAllRefunds('approved', $startDate, $endDate);
    
    // Calculate totals
    $totalCreditNotes = count($creditNotes);
    $totalRefundAmount = array_sum(array_column($creditNotes, 'refund_amount'));
    ?>
    
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
        <div class="stats-card stats-card--cyan">
            <div class="stats-value"><?= $totalCreditNotes ?></div>
            <div class="stats-label">Credit Notes Issued</div>
        </div>
        <div class="stats-card stats-card--gold">
            <div class="stats-value">₹<?= number_format((float)$totalRefundAmount) ?></div>
            <div class="stats-label">Total Refunded</div>
        </div>
        <div class="stats-card stats-card--green">
            <div class="stats-value">₹<?= $totalCreditNotes > 0 ? number_format($totalRefundAmount / $totalCreditNotes) : 0 ?></div>
            <div class="stats-label">Avg Refund Amount</div>
        </div>
    </div>
    
    <div class="glass-card overflow-hidden">
        <div class="p-4 border-b border-slate-700/50 flex justify-between items-center">
            <h3 class="font-semibold text-white">Credit Notes Register</h3>
            <button onclick="window.print()" class="btn btn--secondary text-sm py-1">
                <i data-lucide="printer" class="w-4 h-4"></i>
                Print
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Credit Note #</th>
                        <th>Date</th>
                        <th>Booking</th>
                        <th>Guest</th>
                        <th>Reason</th>
                        <th class="text-right">Amount</th>
                        <th>Approved By</th>
                        <th>Mode</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($creditNotes)): ?>
                    <tr><td colspan="8" class="text-center text-slate-500 py-8">No credit notes found for this period</td></tr>
                    <?php else: ?>
                    <?php foreach ($creditNotes as $cn): ?>
                    <tr>
                        <td class="text-cyan-400 font-mono"><?= htmlspecialchars($cn['credit_note_number'] ?? '-') ?></td>
                        <td><?= date('d/m/Y', strtotime($cn['approved_at'] ?? $cn['created_at'])) ?></td>
                        <td>
                            <a href="/bookings/<?= $cn['booking_id'] ?>" class="text-white hover:text-cyan-400">
                                <?= htmlspecialchars($cn['booking_number'] ?? '#'.$cn['booking_id']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars(trim(($cn['first_name'] ?? '') . ' ' . ($cn['last_name'] ?? ''))) ?: '-' ?></td>
                        <td class="text-slate-400 text-sm max-w-[150px] truncate" title="<?= htmlspecialchars($cn['reason_text'] ?? $cn['reason_code']) ?>">
                            <?= htmlspecialchars($cn['reason_code'] ?? '-') ?>
                        </td>
                        <td class="text-right text-amber-400 font-semibold">₹<?= number_format((float)$cn['refund_amount']) ?></td>
                        <td class="text-slate-400"><?= htmlspecialchars(trim(($cn['approver_first_name'] ?? '') . ' ' . ($cn['approver_last_name'] ?? ''))) ?: 'System' ?></td>
                        <td>
                            <span class="px-2 py-0.5 rounded text-xs bg-slate-700 text-slate-300">
                                <?= ucfirst(htmlspecialchars($cn['refund_mode'] ?? 'cash')) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($creditNotes)): ?>
                <tfoot class="bg-slate-800/50">
                    <tr class="font-semibold">
                        <td colspan="5" class="text-right">Total Refunded:</td>
                        <td class="text-right text-amber-400">₹<?= number_format((float)$totalRefundAmount) ?></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
    
    <?php endif; ?>
</div>

<style>
@media print {
    body * { visibility: hidden; }
    .glass-card, .glass-card * { visibility: visible; }
    .glass-card { position: absolute; left: 0; top: 0; width: 100%; background: white !important; }
    .data-table { color: black !important; }
    .btn { display: none !important; }
}
</style>
