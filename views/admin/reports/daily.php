<?php
// views/admin/reports/daily.php

use HotelOS\Core\Auth;
use HotelOS\Handlers\ReportHandler;

$auth = Auth::getInstance();
// Only Owner/Manager
if (!in_array($auth->role(), ['owner', 'manager'])) {
    header('Location: /dashboard');
    exit;
}

$date = $_GET['date'] ?? date('Y-m-d');
$handler = new ReportHandler();
$report = $handler->getDailySummary($date);
?>

<div class="space-y-6 print:space-y-4">
    <!-- Header with Date Picker -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 print:hidden">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Daily Report</h1>
            <p class="text-gray-500 text-sm mt-1">Financial summary for <?= date('F j, Y', strtotime($date)) ?></p>
        </div>
        
        <form method="GET" class="flex gap-2">
            <input type="date" name="date" value="<?= $date ?>" 
                   class="border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                   onchange="this.form.submit()">
            <button type="button" onclick="window.print()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 text-sm flex items-center gap-2">
                <i data-lucide="printer" class="w-4 h-4"></i> Print
            </button>
        </form>
    </div>

    <!-- Print Header -->
    <div class="hidden print:block text-center mb-6">
        <h1 class="text-xl font-bold">Daily Closing Report</h1>
        <p><?= date('F j, Y', strtotime($date)) ?></p>
    </div>

    <!-- Top Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Revenue -->
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
            <p class="text-xs font-bold text-gray-500 uppercase">Total Revenue</p>
            <p class="text-2xl font-bold text-green-600">₹<?= number_format($report['revenue']['total'], 2) ?></p>
        </div>
        
        <!-- Cash Collected (Mode: Cash) -->
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
            <p class="text-xs font-bold text-gray-500 uppercase">Cash Collected</p>
            <p class="text-2xl font-bold text-gray-900">₹<?= number_format($report['revenue']['breakdown']['cash'] ?? 0, 2) ?></p>
        </div>

        <!-- Petty Cash Expenses -->
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
            <p class="text-xs font-bold text-gray-500 uppercase">Petty Cash Exp.</p>
            <p class="text-2xl font-bold text-red-600">₹<?= number_format($report['petty_cash']['expense'], 2) ?></p>
        </div>

        <!-- Variance -->
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
            <p class="text-xs font-bold text-gray-500 uppercase">Shift Variance</p>
            <?php 
                $v = $report['shifts']['total_variance'];
                $color = $v == 0 ? 'text-gray-900' : ($v < 0 ? 'text-red-600' : 'text-blue-600');
            ?>
            <p class="text-2xl font-bold <?= $color ?>">
                <?= ($v > 0 ? '+' : '') . number_format($v ?? 0, 2) ?>
            </p>
        </div>
    </div>

    <!-- Revenue Breakdown Tables -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Payment Modes</h3>
            <div class="space-y-3">
                <?php foreach ($report['revenue']['breakdown'] as $mode => $amount): ?>
                    <div class="flex justify-between items-center py-2 border-b border-gray-100 last:border-0">
                        <span class="capitalize text-gray-600"><?= $mode ?></span>
                        <span class="font-mono font-medium">₹<?= number_format($amount, 2) ?></span>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($report['revenue']['breakdown'])): ?>
                    <p class="text-sm text-gray-400">No transactions recorded.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Shift Summary</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="text-gray-600">Shifts Closed</span>
                    <span class="font-mono font-medium"><?= (int)$report['shifts']['total_shifts'] ?></span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="text-gray-600">Total Opening Cash</span>
                    <span class="font-mono font-medium">₹<?= number_format($report['shifts']['total_opening'] ?? 0, 2) ?></span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="text-gray-600">Total Closing Cash</span>
                    <span class="font-mono font-medium">₹<?= number_format($report['shifts']['total_closing'] ?? 0, 2) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>
