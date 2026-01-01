<!-- LEDGER ENTRY MODAL (Missing Component Fix) -->
<div x-show="showLedgerModal" style="display: none;" 
     class="fixed inset-0 z-50 overflow-y-auto" 
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">
     
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true" @click="showLedgerModal = false">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
            <form action="/shifts/ledger/add" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="shift_id" value="<?= $currentShift['id'] ?? '' ?>">
                
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i data-lucide="wallet" class="h-6 w-6 text-indigo-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Add Cash Entry
                            </h3>
                            <div class="mt-2 text-sm text-gray-500">
                                <p class="mb-4">Record disjointed cash movement (e.g., petty cash expenses or drawer additions).</p>
                                
                                <div class="space-y-4">
                                    <!-- Type Selection -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Entry Type</label>
                                        <div class="mt-1 flex gap-4">
                                            <label class="inline-flex items-center">
                                                <input type="radio" name="type" value="expense" checked class="form-radio h-4 w-4 text-indigo-600 border-gray-300">
                                                <span class="ml-2 text-gray-700">Expense (Out)</span>
                                            </label>
                                            <label class="inline-flex items-center">
                                                <input type="radio" name="type" value="addition" class="form-radio h-4 w-4 text-green-600 border-gray-300">
                                                <span class="ml-2 text-gray-700">Addition (In)</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Amount -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Amount (₹)</label>
                                        <input type="number" step="0.01" name="amount" required 
                                               class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" 
                                               placeholder="0.00">
                                    </div>
                                    
                                    <!-- Category -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Category</label>
                                        <select name="category" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                            <option value="Maintenance">Maintenance</option>
                                            <option value="Supplies">Supplies</option>
                                            <option value="Refund">Refund (Manual)</option>
                                            <option value="Salary Advance">Salary Advance</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>

                                    <!-- Description -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Description / Notes</label>
                                        <textarea name="description" rows="2" required
                                                  class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 mt-1 block w-full sm:text-sm border border-gray-300 rounded-md" 
                                                  placeholder="E.g., Bought bulb for Room 101"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Save Entry
                    </button>
                    <button type="button" @click="showLedgerModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
