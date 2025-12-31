<?php
// views/shifts/index.php

use HotelOS\Core\Auth;
use HotelOS\Handlers\ShiftHandler;

$auth = Auth::getInstance();
$user = $auth->user();
$shiftHandler = new ShiftHandler();

$currentShift = $shiftHandler->getCurrentShift($user['id']);
$recentShifts = $shiftHandler->getRecentShifts(10);
$staffList = \HotelOS\Core\Database::getInstance()->query("SELECT id, first_name, last_name, role FROM users WHERE tenant_id = :tid AND is_active = 1 AND id != :uid", ['tid' => $user['tenant_id'], 'uid' => $user['id']], enforceTenant: false);

// Calculate real-time expected cash if shift is open
$liveExpected = 0.00;
$ledgerEntries = [];
if ($currentShift) {
    $liveExpected = $shiftHandler->getExpectedCash($user['id'], $currentShift['id'], $currentShift['shift_start_at']);
    $ledgerEntries = $shiftHandler->getShiftLedger($currentShift['id']);
}
?>

<div class="space-y-6" x-data="{ showEndShiftModal: false, showLedgerModal: false }">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Shift Management</h1>
            <p class="text-gray-500 text-sm mt-1">Track your cash drawer and handover responsibility.</p>
        </div>
        
        <?php if ($currentShift): ?>
            <div class="flex items-center gap-2">
                <button @click="showLedgerModal = true" class="flex items-center px-3 py-1 rounded-md text-sm font-medium bg-indigo-100 text-indigo-700 hover:bg-indigo-200 transition-colors">
                    <i data-lucide="plus-circle" class="w-4 h-4 mr-1"></i> Cash Entry
                </button>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 animate-pulse">
                    <span class="w-2 h-2 rounded-full bg-green-500 mr-2"></span>
                    Shift Active
                </span>
            </div>
        <?php else: ?>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                Shift Inactive
            </span>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <!-- MAIN ACTION CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 relative overflow-hidden">
            <?php if (!$currentShift): ?>
                <!-- START SHIFT STATE -->
                <div class="text-center md:text-left">
                    <h2 class="text-lg font-bold text-gray-900 mb-2">Start Your Shift</h2>
                    <p class="text-gray-500 text-sm mb-6">Please verify the cash in the drawer before starting.</p>
                    
                    <form action="/shifts/start" method="POST" class="max-w-xs">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="mb-4">
                            <label class="block text-xs font-medium text-gray-700 uppercase mb-1">Opening Cash Amount (₹)</label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">₹</span>
                                </div>
                                <input type="number" step="0.01" name="opening_cash" required 
                                       class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-7 pr-12 sm:text-sm border-gray-300 rounded-md py-3 text-lg font-bold" 
                                       placeholder="0.00">
                            </div>
                        </div>
                        <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Start Shift
                        </button>
                        <p class="text-xs text-gray-400 mt-3 text-center">
                            By clicking Start, you accept responsibility for this cash amount.
                        </p>
                    </form>
                </div>
            <?php else: ?>
                <!-- ACTIVE SHIFT STATE -->
                <div class="flex flex-col h-full justify-between">
                    <div>
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <h2 class="text-lg font-bold text-gray-900">Current Shift Details</h2>
                                <p class="text-sm text-gray-500">Started at <?= date('h:i A', strtotime($currentShift['shift_start_at'])) ?></p>
                            </div>
                            <div class="bg-indigo-50 p-3 rounded-lg text-center min-w-[100px]">
                                <p class="text-xs text-indigo-600 font-bold uppercase">Opening</p>
                                <p class="text-lg font-mono font-bold text-indigo-900">₹<?= number_format($currentShift['opening_cash'], 2) ?></p>
                            </div>
                        </div>

                        <!-- Live Metrics -->
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-100 mb-6">
                            <h3 class="text-xs font-bold text-gray-500 uppercase mb-3">Real-time Cash Status</h3>
                            <div class="flex justify-between items-end">
                                <div>
                                    <p class="text-sm text-gray-600">System Expected Cash</p>
                                    <p class="text-xs text-gray-400">(Opening + Cash Sales - Refunds)</p>
                                </div>
                                <p class="text-2xl font-mono font-bold text-gray-900">₹<?= number_format($liveExpected, 2) ?></p>
                            </div>
                        </div>

                        <!-- Ledger Mini List -->
                        <?php if (!empty($ledgerEntries)): ?>
                        <div class="mb-6">
                            <h3 class="text-xs font-bold text-gray-500 uppercase mb-2">Petty Cash Log</h3>
                            <div class="bg-white border border-gray-200 rounded-md overflow-hidden">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <tbody class="divide-y divide-gray-200">
                                        <?php foreach ($ledgerEntries as $entry): ?>
                                        <tr>
                                            <td class="px-3 py-2 text-xs text-gray-900">
                                                <span class="font-medium"><?= htmlspecialchars($entry['category']) ?></span>
                                                <span class="text-gray-500 block"><?= htmlspecialchars($entry['description']) ?></span>
                                            </td>
                                            <td class="px-3 py-2 text-xs text-right font-mono <?= $entry['type'] === 'expense' ? 'text-red-600' : 'text-green-600' ?>">
                                                <?= $entry['type'] === 'expense' ? '-' : '+' ?>₹<?= number_format($entry['amount'], 2) ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <button @click="showEndShiftModal = true" 
                            class="w-full py-3 px-4 border border-red-200 rounded-md shadow-sm text-sm font-medium text-red-700 bg-red-50 hover:bg-red-100 hover:border-red-300 focus:outline-none transition-colors flex items-center justify-center gap-2">
                        <i data-lucide="stop-circle" class="w-4 h-4"></i> End Shift & Handover
                    </button>
                    
                    <p class="text-xs text-center text-gray-400 mt-2">
                        <i data-lucide="lock" class="w-3 h-3 inline mr-1"></i>
                        You cannot logout while shift is active.
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <!-- HISTORY CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">Recent Handover History</h2>
            <div class="overflow-y-auto max-h-[300px] pr-2">
                <?php if (empty($recentShifts)): ?>
                    <p class="text-gray-500 text-sm text-center py-8">No shift history found.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($recentShifts as $shift): ?>
                            <div class="border-l-4 <?= $shift['status'] === 'OPEN' ? 'border-green-400 bg-green-50' : 'border-gray-300 bg-gray-50' ?> pl-4 py-2">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="text-sm font-bold text-gray-900">
                                            <?= htmlspecialchars($shift['user_name']) ?>
                                            <?php if ($shift['status'] === 'OPEN'): ?>
                                                <span class="ml-2 text-xs bg-green-200 text-green-800 px-1 rounded">ACTIVE</span>
                                            <?php endif; ?>
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            <?= date('M j, h:i A', strtotime($shift['shift_start_at'])) ?>
                                            <?php if ($shift['shift_end_at']): ?>
                                                - <?= date('h:i A', strtotime($shift['shift_end_at'])) ?>
                                            <?php endif; ?>
                                        </p>
                                        <?php if ($shift['handover_name']): ?>
                                            <p class="text-xs text-indigo-600 mt-1">
                                                To: <?= htmlspecialchars($shift['handover_name']) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-right">
                                        <?php if ($shift['status'] === 'CLOSED'): ?>
                                            <p class="text-sm font-mono font-medium text-gray-900">
                                                <?= $shift['variance_amount'] == 0 ? '✅ Balanced' : '⚠️ ' . ($shift['variance_amount'] > 0 ? '+' : '') . $shift['variance_amount'] ?>
                                            </p>
                                            <p class="text-xs text-gray-400">Var.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- END SHIFT MODAL -->
    <div x-show="showEndShiftModal" style="display: none;" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
         
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true" @click="showEndShiftModal = false">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <form action="/shifts/end" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="shift_id" value="<?= $currentShift['id'] ?? '' ?>">
                    
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                                <i data-lucide="clipboard-check" class="h-6 w-6 text-indigo-600"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    End Shift & Handover
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 mb-4">
                                        System expects <strong>₹<?= number_format($liveExpected, 2) ?></strong> in drawer.
                                    </p>
                                    
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Actual Closing Cash</label>
                                            <div class="mt-1 relative rounded-md shadow-sm">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <span class="text-gray-500 sm:text-sm">₹</span>
                                                </div>
                                                <input type="number" step="0.01" name="closing_cash" required 
                                                       class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-7 sm:text-sm border-gray-300 rounded-md py-2" 
                                                       placeholder="0.00">
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Handover To (Next Staff)</label>
                                            <select name="handover_to" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                                <option value="">-- Select Staff --</option>
                                                <?php foreach ($staffList as $staff): ?>
                                                    <option value="<?= $staff['id'] ?>">
                                                        <?= htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']) ?> (<?= ucfirst($staff['role']) ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Notes / Issues</label>
                                            <textarea name="notes" rows="2" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 mt-1 block w-full sm:text-sm border border-gray-300 rounded-md" placeholder="E.g., Short of ₹50 due to change..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Confirm Close
                        </button>
                        <button type="button" @click="showEndShiftModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
