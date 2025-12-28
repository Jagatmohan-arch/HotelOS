<?php
// views/admin/ledger/index.php

use HotelOS\Core\Database;
use HotelOS\Core\TenantContext;

$db = Database::getInstance();
$tenantId = TenantContext::getId();

// Filters
$startDate = $_GET['start'] ?? date('Y-m-01');
$endDate = $_GET['end'] ?? date('Y-m-d');

// Fetch Ledger with JOINs
$ledger = $db->query(
    "SELECT cl.*, s.shift_start_at, u.first_name, u.last_name
     FROM cash_ledger cl
     JOIN shifts s ON cl.shift_id = s.id
     JOIN users u ON s.user_id = u.id
     WHERE cl.tenant_id = :tenant_id
     AND DATE(cl.created_at) BETWEEN :start AND :end
     ORDER BY cl.created_at DESC",
    [
        'tenant_id' => $tenantId,
        'start' => $startDate,
        'end' => $endDate
    ],
    enforceTenant: false
);

$totals = [
    'credit' => 0,
    'debit' => 0,
    'balance' => 0
];

foreach ($ledger as $entry) {
    if ($entry['type'] === 'credit') {
        $totals['credit'] += $entry['amount'];
        $totals['balance'] += $entry['amount'];
    } else {
        $totals['debit'] += $entry['amount'];
        $totals['balance'] -= $entry['amount'];
    }
}
?>

<div class="space-y-6">
    <!-- Header With Filter -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Cash Ledger</h1>
            <p class="text-gray-500 text-sm mt-1">Master record of all petty cash transactions.</p>
        </div>
        
        <form class="flex gap-2">
            <input type="date" name="start" value="<?= $startDate ?>" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
            <span class="text-gray-500 self-center">to</span>
            <input type="date" name="end" value="<?= $endDate ?>" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
            <button type="submit" class="px-3 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">
                Filter
            </button>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                <i data-lucide="plus-circle" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase font-bold">Total Inflows</p>
                <p class="text-2xl font-bold text-gray-900">₹<?= number_format($totals['credit'], 2) ?></p>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 flex items-center">
            <div class="p-3 rounded-full bg-red-100 text-red-600 mr-4">
                <i data-lucide="minus-circle" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase font-bold">Total Expenses</p>
                <p class="text-2xl font-bold text-gray-900">₹<?= number_format($totals['debit'], 2) ?></p>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 flex items-center">
            <div class="p-3 rounded-full bg-gray-100 text-gray-600 mr-4">
                <i data-lucide="wallet" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase font-bold">Net Balance</p>
                <p class="text-2xl font-bold <?= $totals['balance'] >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                    ₹<?= number_format($totals['balance'], 2) ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Ledger Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Staff</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($ledger)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                            No ledger entries found for this period.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($ledger as $entry): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= date('M j, Y h:i A', strtotime($entry['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?= htmlspecialchars($entry['description']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                    <?= htmlspecialchars($entry['category']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= htmlspecialchars($entry['first_name']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold <?= $entry['type'] === 'credit' ? 'text-green-600' : 'text-red-600' ?>">
                                <?= $entry['type'] === 'credit' ? '+' : '-' ?>₹<?= number_format($entry['amount'], 2) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
