<?php
/**
 * HotelOS - Police Report View
 * 
 * Auto-generated police report for daily guest check-ins
 * Indian Government Format
 */

$reportDate = $_GET['date'] ?? date('Y-m-d');
$handler = new \HotelOS\Handlers\PoliceReportHandler();
$reportData = $handler->getReportByDate($reportDate);
$pendingReports = $handler->getPendingReports();

// Get tenant/hotel info
$tenantInfo = $tenantInfo ?? [
    'name' => 'Grand Palace Hotel',
    'address' => '123 Main Street',
    'city' => 'Mumbai',
    'state' => 'Maharashtra',
    'phone' => '9876543210',
    'gstin' => '27AABCU9603R1ZM'
];
?>

<div class="page-header">
    <div class="page-header__left">
        <h1 class="page-title">🚓 Police Report</h1>
        <p class="page-subtitle">Daily guest check-in report for police verification</p>
    </div>
    <div class="page-header__right">
        <input type="date" id="report-date" value="<?= htmlspecialchars($reportDate) ?>" 
               class="form-input" style="width: 160px;" onchange="changeDate(this.value)">
    </div>
</div>

<!-- Pending Reports Alert -->
<?php if (count($pendingReports) > 0): ?>
<div class="alert alert--warning">
    <i data-lucide="alert-triangle" class="w-5 h-5"></i>
    <span><strong><?= count($pendingReports) ?></strong> pending police reports need submission</span>
    <a href="#pending" class="btn btn--sm btn--warning ml-auto">View Pending</a>
</div>
<?php endif; ?>

<!-- Report Container -->
<div class="report-container" id="police-report">
    <!-- Report Actions (screen only) -->
    <div class="report-actions no-print">
        <button onclick="PrintEngine.print('police-report', {title: 'Police Report - <?= $reportDate ?>'})" 
                class="btn btn-print">
            <i data-lucide="printer" class="w-4 h-4"></i>
            Print
        </button>
        <button onclick="PrintEngine.pdf('police-report')" class="btn btn-pdf">
            <i data-lucide="file-text" class="w-4 h-4"></i>
            PDF
        </button>
        <button onclick="shareWhatsApp()" class="btn btn-whatsapp">
            <i data-lucide="share-2" class="w-4 h-4"></i>
            WhatsApp
        </button>
        <button onclick="markAsSubmitted('<?= $reportDate ?>')" class="btn btn--success ml-auto">
            <i data-lucide="check-circle" class="w-4 h-4"></i>
            Mark as Submitted
        </button>
    </div>
    
    <!-- Report Header -->
    <div class="report-header police-report-header">
        <h1 class="hotel-name"><?= htmlspecialchars($tenantInfo['name']) ?></h1>
        <p class="hotel-address">
            <?= htmlspecialchars($tenantInfo['address']) ?>, 
            <?= htmlspecialchars($tenantInfo['city']) ?> - 
            <?= htmlspecialchars($tenantInfo['state']) ?>
        </p>
        <p class="hotel-gstin">GSTIN: <?= htmlspecialchars($tenantInfo['gstin']) ?> | Ph: <?= htmlspecialchars($tenantInfo['phone']) ?></p>
    </div>
    
    <!-- Report Title -->
    <div class="report-title-bar">
        <h2>POLICE VERIFICATION REPORT (FORM C)</h2>
        <p class="date-range">Date: <?= date('d-M-Y', strtotime($reportDate)) ?></p>
    </div>
    
    <!-- Summary Stats -->
    <div class="report-summary-grid">
        <div class="summary-stat summary-stat--highlight">
            <div class="value"><?= count($reportData['guests']) ?></div>
            <div class="label">Total Check-ins</div>
        </div>
        <div class="summary-stat">
            <div class="value">
                <?= count(array_filter($reportData['guests'], fn($g) => ($g['nationality'] ?? 'Indian') !== 'Indian')) ?>
            </div>
            <div class="label">Foreign Nationals</div>
        </div>
        <div class="summary-stat">
            <div class="value">
                <?= count(array_filter($reportData['guests'], fn($g) => !empty($g['id_number']))) ?>
            </div>
            <div class="label">ID Verified</div>
        </div>
    </div>
    
    <!-- Guest Table -->
    <?php if (count($reportData['guests']) > 0): ?>
    <table class="report-table police-report-table">
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="18%">Guest Name</th>
                <th width="12%">Mobile</th>
                <th width="12%">ID Type</th>
                <th width="15%">ID Number</th>
                <th width="8%">Room</th>
                <th width="15%">Address</th>
                <th width="10%">Check-in</th>
                <th width="5%">Nat.</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reportData['guests'] as $index => $guest): ?>
            <tr>
                <td class="text-center"><?= $index + 1 ?></td>
                <td>
                    <strong><?= htmlspecialchars($guest['first_name'] . ' ' . ($guest['last_name'] ?? '')) ?></strong>
                    <?php if (!empty($guest['gender'])): ?>
                    <br><small class="text-slate-400"><?= ucfirst($guest['gender']) ?></small>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($guest['phone'] ?? 'N/A') ?></td>
                <td><?= strtoupper(htmlspecialchars($guest['id_type'] ?? 'N/A')) ?></td>
                <td><code><?= htmlspecialchars($guest['id_number'] ?? 'N/A') ?></code></td>
                <td class="text-center"><strong><?= htmlspecialchars($guest['room_number']) ?></strong></td>
                <td>
                    <?= htmlspecialchars($guest['city'] ?? '') ?>
                    <?php if (!empty($guest['state'])): ?>
                    , <?= htmlspecialchars($guest['state']) ?>
                    <?php endif; ?>
                </td>
                <td><?= date('H:i', strtotime($guest['actual_checkin'] ?? $guest['check_in_date'])) ?></td>
                <td class="text-center">
                    <?php if (($guest['nationality'] ?? 'Indian') !== 'Indian'): ?>
                    <span class="badge badge--warning" title="<?= htmlspecialchars($guest['nationality']) ?>">F</span>
                    <?php else: ?>
                    <span class="badge badge--cyan">IN</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">
        <i data-lucide="users" class="w-12 h-12 text-slate-500 mx-auto"></i>
        <h3 class="text-white mt-4">No Check-ins</h3>
        <p class="text-slate-400">No guests checked in on <?= date('d-M-Y', strtotime($reportDate)) ?></p>
    </div>
    <?php endif; ?>
    
    <!-- Report Footer -->
    <div class="report-footer">
        <div class="signature-block">
            <div class="signature-line"></div>
            <div class="signature-label">Manager Signature</div>
        </div>
        <div class="report-timestamp">
            Generated: <?= date('d-M-Y H:i:s') ?>
        </div>
        <div class="signature-block">
            <div class="signature-line"></div>
            <div class="signature-label">Police Verification</div>
        </div>
    </div>
</div>

<!-- Pending Reports Section -->
<?php if (count($pendingReports) > 0): ?>
<div class="glass-card p-4 mt-6" id="pending">
    <h3 class="text-white font-semibold mb-4">
        <i data-lucide="clock" class="w-5 h-5 inline text-amber-400"></i>
        Pending Reports
    </h3>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <?php foreach ($pendingReports as $report): ?>
        <a href="?date=<?= $report['report_date'] ?>" 
           class="p-3 rounded-lg bg-slate-700/50 hover:bg-slate-700 transition-colors text-center">
            <div class="text-white font-medium"><?= date('d M', strtotime($report['report_date'])) ?></div>
            <div class="text-amber-400 text-sm"><?= $report['guest_count'] ?> guests</div>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<link rel="stylesheet" href="/assets/css/print-reports.css">
<script src="/assets/js/print-engine.js"></script>

<style>
.alert {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
}

.alert--warning {
    background: rgba(245, 158, 11, 0.15);
    border: 1px solid rgba(245, 158, 11, 0.3);
    color: #fbbf24;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
}

code {
    background: rgba(0, 0, 0, 0.3);
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    font-size: 0.8rem;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.page-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #f1f5f9;
}

.page-subtitle {
    color: #94a3b8;
    font-size: 0.875rem;
}
</style>

<script>
function changeDate(date) {
    window.location.href = '?date=' + date;
}

function shareWhatsApp() {
    fetch('/api/reports/police-text?date=<?= $reportDate ?>')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                PrintEngine.shareWhatsApp(data.text);
            }
        })
        .catch(() => {
            // Fallback: Generate text locally
            const text = `🏨 POLICE REPORT\nDate: <?= date('d-M-Y', strtotime($reportDate)) ?>\nGuests: <?= count($reportData['guests']) ?>\n\n<?php 
                foreach ($reportData['guests'] as $i => $g) {
                    echo ($i + 1) . '. ' . $g['first_name'] . ' ' . ($g['last_name'] ?? '') . ' - ' . $g['room_number'] . '\\n';
                }
            ?>`;
            PrintEngine.shareWhatsApp(text);
        });
}

function markAsSubmitted(date) {
    if (!confirm('Mark this report as submitted to police?')) return;
    
    fetch('/api/reports/police-submit', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ date: date })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('✅ Report marked as submitted!');
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
        }
    });
}
</script>
