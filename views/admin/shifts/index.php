<?php
// views/admin/shifts/index.php

use HotelOS\Core\Auth;
use HotelOS\Handlers\ShiftHandler;

$auth = Auth::getInstance();
// Only Owner/Manager
if (!in_array($auth->role(), ['owner', 'manager'])) {
    header('Location: /dashboard');
    exit;
}

$handler = new ShiftHandler();
$shifts = $handler->getAllClosedShifts(50);

?>
<div class="space-y-6" x-data="{ showVerifyModal: false, selectedShiftId: null, selectedVariance: 0 }">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Shift Audit</h1>
            <p class="text-gray-500 text-sm mt-1">Verify closed shifts and flag variances.</p>
        </div>
    </div>

    <!-- Shifts List -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Staff / Time</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Opening</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">System Exp.</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Closing (Actual)</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Variance</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($shifts as $shift): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                <?= htmlspecialchars($shift['first_name'] . ' ' . $shift['last_name']) ?>
                            </div>
                            <div class="text-xs text-gray-500">
                                <?= date('M j, h:i A', strtotime($shift['shift_end_at'])) ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500">
                            ₹<?= number_format($shift['opening_cash'], 2) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500">
                            ₹<?= number_format($shift['system_expected_cash'], 2) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-gray-900">
                            ₹<?= number_format($shift['closing_cash'], 2) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                            <?php 
                                $v = $shift['variance_amount'];
                                $color = $v == 0 ? 'text-green-600' : ($v < 0 ? 'text-red-600 font-bold' : 'text-blue-600');
                                echo "<span class='{$color}'>" . ($v > 0 ? '+' : '') . number_format($v, 2) . "</span>";
                            ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($shift['verified_by']): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i data-lucide="check-circle" class="w-3 h-3 mr-1"></i> Verified
                                </span>
                                <?php if($shift['manager_note']): ?>
                                    <div class="text-xs text-gray-500 mt-1 max-w-[150px] truncate" title="<?= htmlspecialchars($shift['manager_note']) ?>">
                                        <?= htmlspecialchars($shift['manager_note']) ?>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    Pending
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <?php if (!$shift['verified_by']): ?>
                                <button @click="showVerifyModal = true; selectedShiftId = <?= $shift['id'] ?>; selectedVariance = '<?= $shift['variance_amount'] ?>'" 
                                        class="text-indigo-600 hover:text-indigo-900">
                                    Verify
                                </button>
                            <?php else: ?>
                                <span class="text-gray-400 cursor-not-allowed">Locked</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Verify Modal -->
    <div x-show="showVerifyModal" style="display: none;" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
         
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true" @click="showVerifyModal = false">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <form action="/admin/shifts/verify" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="shift_id" :value="selectedShiftId">
                    
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Verify Shift & Close Audit</h3>
                        <p class="text-sm text-gray-500 mb-4">
                            You are acknowledging the cash collected and any variance.
                        </p>
                        
                        <div x-show="selectedVariance != 0" class="mb-4 bg-red-50 p-3 rounded-md border border-red-100">
                             <p class="text-sm text-red-700 font-bold">
                                 ⚠️ Variance Detected: ₹<span x-text="selectedVariance"></span>
                             </p>
                             <p class="text-xs text-red-600 mt-1">Please add a note explaining this variance.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Manager Remarks (Optional)</label>
                            <input type="text" name="note" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="e.g. Approved shortage, deducted from salary">
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">
                            <i data-lucide="check" class="w-4 h-4 mr-2"></i> Verify & Lock
                        </button>
                        <button type="button" @click="showVerifyModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
