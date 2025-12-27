<?php
/**
 * HotelOS - POS & Inventory Page
 * Manage items and add charges to guest rooms
 */



$items = $items ?? [];
$inHouseGuests = $inHouseGuests ?? [];
$categoryCounts = $categoryCounts ?? [];
$selectedCategory = $selectedCategory ?? '';
$success = $success ?? null;
$error = $error ?? null;

$categories = [
    'minibar' => ['label' => 'Minibar', 'icon' => '🍫', 'color' => 'amber'],
    'laundry' => ['label' => 'Laundry', 'icon' => '👔', 'color' => 'blue'],
    'room_service' => ['label' => 'Room Service', 'icon' => '🛎️', 'color' => 'purple'],
    'other' => ['label' => 'Other', 'icon' => '📦', 'color' => 'slate']
];
?>

<div class="pos-page animate-fadeIn">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white">POS & Extras</h1>
            <p class="text-slate-400 text-sm mt-1">Minibar, Laundry & Room Charges</p>
        </div>
        <div class="flex gap-3">
            <button onclick="openChargeModal()" class="btn btn--primary">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                Add Charge
            </button>
            <button onclick="openItemModal()" class="btn btn--secondary">
                <i data-lucide="package-plus" class="w-4 h-4"></i>
                New Item
            </button>
        </div>
    </div>
    
    <!-- Messages -->
    <?php if ($success): ?>
    <div class="mb-4 p-4 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm">
        <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="mb-4 p-4 rounded-lg bg-red-500/10 border border-red-500/30 text-red-300 text-sm">
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>
    
    <!-- Category Tabs -->
    <div class="flex gap-2 mb-6 overflow-x-auto pb-2">
        <a href="/pos" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition-colors whitespace-nowrap <?= empty($selectedCategory) ? 'bg-cyan-500/20 text-cyan-400 border border-cyan-500/30' : 'bg-slate-800/50 text-slate-400 hover:text-white' ?>">
            All Items
        </a>
        <?php foreach ($categories as $key => $cat): ?>
        <a href="/pos?category=<?= $key ?>" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition-colors whitespace-nowrap <?= $selectedCategory === $key ? 'bg-cyan-500/20 text-cyan-400 border border-cyan-500/30' : 'bg-slate-800/50 text-slate-400 hover:text-white' ?>">
            <?= $cat['icon'] ?> <?= $cat['label'] ?>
            <span class="ml-1 text-xs opacity-60">(<?= $categoryCounts[$key] ?? 0 ?>)</span>
        </a>
        <?php endforeach; ?>
    </div>
    
    <!-- Items Grid -->
    <div class="glass-card p-4">
        <?php if (empty($items)): ?>
        <div class="text-center py-12">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-slate-700/50 flex items-center justify-center">
                <i data-lucide="package" class="w-8 h-8 text-slate-500"></i>
            </div>
            <h3 class="text-white font-medium mb-1">No Items Found</h3>
            <p class="text-slate-400 text-sm mb-4">Run the migration to add sample items</p>
            <button onclick="openItemModal()" class="btn btn--primary">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Add First Item
            </button>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <?php foreach ($items as $item): ?>
            <?php $cat = $categories[$item['category']] ?? $categories['other']; ?>
            <div class="p-4 bg-slate-800/50 rounded-lg border border-slate-700/50 hover:border-<?= $cat['color'] ?>-500/30 transition-colors">
                <div class="flex items-start justify-between mb-2">
                    <span class="text-2xl"><?= $cat['icon'] ?></span>
                    <span class="text-xs px-2 py-0.5 rounded bg-<?= $cat['color'] ?>-500/20 text-<?= $cat['color'] ?>-400">
                        <?= $cat['label'] ?>
                    </span>
                </div>
                <h3 class="text-white font-medium"><?= htmlspecialchars($item['name']) ?></h3>
                <p class="text-slate-500 text-xs mb-2"><?= htmlspecialchars($item['code'] ?: '-') ?></p>
                <div class="text-xl font-bold text-cyan-400">₹<?= number_format((float)$item['price'], 0) ?></div>
                <p class="text-xs text-slate-500">+ <?= number_format((float)$item['gst_rate'], 0) ?>% GST</p>
                
                <div class="flex gap-2 mt-3">
                    <button onclick="quickCharge(<?= $item['id'] ?>, '<?= htmlspecialchars($item['name'], ENT_QUOTES) ?>', <?= $item['price'] ?>)" 
                            class="flex-1 btn btn--primary text-xs py-1.5">
                        Charge
                    </button>
                    <button onclick="editItem(<?= htmlspecialchars(json_encode($item)) ?>)" 
                            class="btn btn--ghost p-1.5">
                        <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Charge Modal -->
<div id="chargeModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="glass-card w-full max-w-md">
        <div class="flex items-center justify-between p-4 border-b border-slate-700/50">
            <h2 class="text-lg font-semibold text-white">Add Room Charge</h2>
            <button onclick="closeChargeModal()" class="text-slate-400 hover:text-white">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        
        <form method="POST" action="/pos/charge" class="p-4 space-y-4">
            <input type="hidden" name="_csrf" value="<?= $csrfToken ?? '' ?>">
            <input type="hidden" name="item_id" id="chargeItemId">
            
            <div>
                <label class="form-label">Room / Guest *</label>
                <select name="booking_id" class="form-input" required>
                    <option value="">Select in-house guest...</option>
                    <?php foreach ($inHouseGuests as $guest): ?>
                    <option value="<?= $guest['booking_id'] ?>">
                        <?= htmlspecialchars($guest['room_number']) ?> - <?= htmlspecialchars($guest['guest_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="form-label">Item</label>
                <input type="text" id="chargeItemName" class="form-input bg-slate-700/50" readonly>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Quantity</label>
                    <input type="number" name="quantity" value="1" min="1" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Unit Price</label>
                    <input type="text" id="chargePrice" class="form-input bg-slate-700/50" readonly>
                </div>
            </div>
            
            <div>
                <label class="form-label">Notes (Optional)</label>
                <input type="text" name="notes" class="form-input" placeholder="Any notes...">
            </div>
            
            <div class="flex gap-3 pt-4 border-t border-slate-700/50">
                <button type="button" onclick="closeChargeModal()" class="btn btn--secondary flex-1">Cancel</button>
                <button type="submit" class="btn btn--primary flex-1">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    Add Charge
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Add/Edit Item Modal -->
<div id="itemModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="glass-card w-full max-w-md">
        <div class="flex items-center justify-between p-4 border-b border-slate-700/50">
            <h2 class="text-lg font-semibold text-white" id="itemModalTitle">Add Item</h2>
            <button onclick="closeItemModal()" class="text-slate-400 hover:text-white">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        
        <form method="POST" action="/pos/item" id="itemForm" class="p-4 space-y-4">
            <input type="hidden" name="_csrf" value="<?= $csrfToken ?? '' ?>">
            <input type="hidden" name="id" id="itemId">
            
            <div>
                <label class="form-label">Category *</label>
                <select name="category" id="itemCategory" class="form-input" required>
                    <?php foreach ($categories as $key => $cat): ?>
                    <option value="<?= $key ?>"><?= $cat['icon'] ?> <?= $cat['label'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Name *</label>
                    <input type="text" name="name" id="itemName" class="form-input" required placeholder="Item name">
                </div>
                <div>
                    <label class="form-label">Code</label>
                    <input type="text" name="code" id="itemCode" class="form-input" placeholder="SKU" maxlength="20">
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Price (₹) *</label>
                    <input type="number" name="price" id="itemPrice" class="form-input" required min="0" step="0.01" placeholder="0.00">
                </div>
                <div>
                    <label class="form-label">GST Rate (%)</label>
                    <select name="gst_rate" id="itemGst" class="form-input">
                        <option value="0">0%</option>
                        <option value="5">5%</option>
                        <option value="12">12%</option>
                        <option value="18" selected>18%</option>
                        <option value="28">28%</option>
                    </select>
                </div>
            </div>
            
            <div>
                <label class="form-label">Description</label>
                <input type="text" name="description" id="itemDesc" class="form-input" placeholder="Optional description">
            </div>
            
            <div class="flex gap-3 pt-4 border-t border-slate-700/50">
                <button type="button" onclick="closeItemModal()" class="btn btn--secondary flex-1">Cancel</button>
                <button type="submit" class="btn btn--primary flex-1">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span id="itemSubmitText">Add Item</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openChargeModal() {
    document.getElementById('chargeModal').classList.remove('hidden');
    document.getElementById('chargeModal').classList.add('flex');
    lucide.createIcons();
}

function closeChargeModal() {
    document.getElementById('chargeModal').classList.add('hidden');
    document.getElementById('chargeModal').classList.remove('flex');
}

function quickCharge(itemId, itemName, price) {
    document.getElementById('chargeItemId').value = itemId;
    document.getElementById('chargeItemName').value = itemName;
    document.getElementById('chargePrice').value = '₹' + price;
    openChargeModal();
}

function openItemModal() {
    document.getElementById('itemId').value = '';
    document.getElementById('itemForm').action = '/pos/item/create';
    document.getElementById('itemModalTitle').textContent = 'Add Item';
    document.getElementById('itemSubmitText').textContent = 'Add Item';
    document.getElementById('itemCategory').value = 'minibar';
    document.getElementById('itemName').value = '';
    document.getElementById('itemCode').value = '';
    document.getElementById('itemPrice').value = '';
    document.getElementById('itemGst').value = '18';
    document.getElementById('itemDesc').value = '';
    
    document.getElementById('itemModal').classList.remove('hidden');
    document.getElementById('itemModal').classList.add('flex');
    lucide.createIcons();
}

function closeItemModal() {
    document.getElementById('itemModal').classList.add('hidden');
    document.getElementById('itemModal').classList.remove('flex');
}

function editItem(item) {
    document.getElementById('itemId').value = item.id;
    document.getElementById('itemForm').action = '/pos/item/update';
    document.getElementById('itemModalTitle').textContent = 'Edit Item';
    document.getElementById('itemSubmitText').textContent = 'Update Item';
    document.getElementById('itemCategory').value = item.category;
    document.getElementById('itemName').value = item.name;
    document.getElementById('itemCode').value = item.code || '';
    document.getElementById('itemPrice').value = item.price;
    document.getElementById('itemGst').value = item.gst_rate;
    document.getElementById('itemDesc').value = item.description || '';
    
    document.getElementById('itemModal').classList.remove('hidden');
    document.getElementById('itemModal').classList.add('flex');
    lucide.createIcons();
}
</script>
