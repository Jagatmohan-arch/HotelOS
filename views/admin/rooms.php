<?php
/**
 * HotelOS - Rooms Management View
 * 
 * Grid/Table view of all rooms with status indicators
 * 
 * Variables:
 * - $rooms: Array of rooms
 * - $roomTypes: Array of room types for dropdown
 * - $statusCounts: Room status summary
 * - $error: Error message
 * - $success: Success message
 */

$rooms = $rooms ?? [];
$roomTypes = $roomTypes ?? [];
$statusCounts = $statusCounts ?? [];
$error = $error ?? null;
$success = $success ?? null;

$statusConfig = [
    'available' => ['label' => 'Available', 'color' => 'green', 'bg' => 'bg-emerald-500/15', 'text' => 'text-emerald-400', 'border' => 'border-emerald-500/30'],
    'occupied' => ['label' => 'Occupied', 'color' => 'red', 'bg' => 'bg-red-500/15', 'text' => 'text-red-400', 'border' => 'border-red-500/30'],
    'reserved' => ['label' => 'Reserved', 'color' => 'yellow', 'bg' => 'bg-amber-500/15', 'text' => 'text-amber-400', 'border' => 'border-amber-500/30'],
    'maintenance' => ['label' => 'Maintenance', 'color' => 'blue', 'bg' => 'bg-blue-500/15', 'text' => 'text-blue-400', 'border' => 'border-blue-500/30'],
    'blocked' => ['label' => 'Blocked', 'color' => 'gray', 'bg' => 'bg-slate-500/15', 'text' => 'text-slate-400', 'border' => 'border-slate-500/30'],
];
?>

<div class="rooms-page animate-fadeIn" x-data="roomsPage()">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white">Rooms</h1>
            <p class="text-slate-400 text-sm mt-1">Manage your room inventory</p>
        </div>
        <div class="flex gap-3">
            <a href="/room-types" class="btn btn--secondary">
                <i data-lucide="layers" class="w-4 h-4"></i>
                <span class="hidden sm:inline">Room Types</span>
            </a>
            <button @click="openModal()" class="btn btn--primary">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Add Room
            </button>
        </div>
    </div>
    
    <!-- Status Summary Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
        <?php foreach ($statusConfig as $status => $config): ?>
        <div class="<?= $config['bg'] ?> border <?= $config['border'] ?> rounded-lg p-3 text-center">
            <div class="text-2xl font-bold <?= $config['text'] ?>">
                <?= $statusCounts[$status] ?? 0 ?>
            </div>
            <div class="text-xs text-slate-400 mt-1"><?= $config['label'] ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Messages -->
    <?php if ($error): ?>
    <div class="mb-4 p-4 rounded-lg bg-red-500/10 border border-red-500/30 text-red-300 text-sm">
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
    <div class="mb-4 p-4 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm">
        <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>
    
    <!-- View Toggle -->
    <div class="flex items-center gap-4 mb-4">
        <span class="text-sm text-slate-400">View:</span>
        <div class="flex bg-slate-800 rounded-lg p-1">
            <button 
                @click="viewMode = 'grid'"
                :class="viewMode === 'grid' ? 'bg-slate-700 text-white' : 'text-slate-400'"
                class="px-3 py-1.5 rounded text-sm transition-colors"
            >
                <i data-lucide="grid-3x3" class="w-4 h-4"></i>
            </button>
            <button 
                @click="viewMode = 'table'"
                :class="viewMode === 'table' ? 'bg-slate-700 text-white' : 'text-slate-400'"
                class="px-3 py-1.5 rounded text-sm transition-colors"
            >
                <i data-lucide="list" class="w-4 h-4"></i>
            </button>
        </div>
        
        <!-- Filter by Room Type -->
        <select 
            x-model="filterType"
            class="form-input py-1.5 text-sm w-auto"
        >
            <option value="">All Types</option>
            <?php foreach ($roomTypes as $type): ?>
            <option value="<?= $type['id'] ?>"><?= htmlspecialchars($type['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <?php if (empty($rooms)): ?>
        <!-- Empty State -->
        <div class="glass-card text-center py-16">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-slate-700/50 flex items-center justify-center">
                <i data-lucide="bed-double" class="w-8 h-8 text-slate-500"></i>
            </div>
            <h3 class="text-white font-medium mb-1">No Rooms Yet</h3>
            <p class="text-slate-400 text-sm mb-4">
                <?php if (empty($roomTypes)): ?>
                    First, create a room type, then add rooms
                <?php else: ?>
                    Start adding rooms to your inventory
                <?php endif; ?>
            </p>
            <?php if (empty($roomTypes)): ?>
                <a href="/room-types" class="btn btn--primary">
                    <i data-lucide="layers" class="w-4 h-4"></i>
                    Add Room Type First
                </a>
            <?php else: ?>
                <button @click="openModal()" class="btn btn--primary">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    Add Your First Room
                </button>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- Grid View -->
        <div x-show="viewMode === 'grid'" class="glass-card p-4">
            <div class="room-grid">
                <?php foreach ($rooms as $room): ?>
                    <?php $config = $statusConfig[$room['status']] ?? $statusConfig['available']; ?>
                    <div 
                        class="room-box <?= $config['bg'] ?> border-2 <?= $config['border'] ?> <?= $config['text'] ?>"
                        @click='openModal(<?= json_encode($room) ?>)'
                        x-show="!filterType || filterType == '<?= $room['room_type_id'] ?>'"
                    >
                        <span class="text-lg font-bold"><?= htmlspecialchars($room['room_number']) ?></span>
                        <span class="text-[10px] opacity-70"><?= htmlspecialchars($room['room_type_code'] ?? '') ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Table View -->
        <div x-show="viewMode === 'table'" class="glass-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Room</th>
                            <th>Type</th>
                            <th class="hidden md:table-cell">Floor</th>
                            <th>Status</th>
                            <th class="hidden md:table-cell">Housekeeping</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rooms as $room): ?>
                            <?php $config = $statusConfig[$room['status']] ?? $statusConfig['available']; ?>
                            <tr x-show="!filterType || filterType == '<?= $room['room_type_id'] ?>'">
                                <td>
                                    <span class="text-white font-semibold"><?= htmlspecialchars($room['room_number']) ?></span>
                                </td>
                                <td>
                                    <span class="text-slate-300"><?= htmlspecialchars($room['room_type_name']) ?></span>
                                    <span class="text-xs text-slate-500 ml-1">(<?= htmlspecialchars($room['room_type_code']) ?>)</span>
                                </td>
                                <td class="hidden md:table-cell text-slate-400">
                                    <?= htmlspecialchars($room['floor'] ?: '-') ?>
                                </td>
                                <td>
                                    <span class="badge badge--<?= $config['color'] ?>">
                                        <?= $config['label'] ?>
                                    </span>
                                </td>
                                <td class="hidden md:table-cell">
                                    <?php 
                                    $hkStatus = $room['housekeeping_status'] ?? 'clean';
                                    $hkColors = [
                                        'clean' => 'badge--green',
                                        'dirty' => 'badge--red',
                                        'inspected' => 'badge--blue',
                                        'out_of_order' => 'badge--gray'
                                    ];
                                    ?>
                                    <span class="badge <?= $hkColors[$hkStatus] ?? 'badge--gray' ?>">
                                        <?= ucfirst($hkStatus) ?>
                                    </span>
                                </td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button 
                                            @click='openModal(<?= json_encode($room) ?>)'
                                            class="btn btn--ghost p-2"
                                        >
                                            <i data-lucide="edit" class="w-4 h-4"></i>
                                        </button>
                                        <div class="relative" x-data="{ open: false }">
                                            <button @click="open = !open" class="btn btn--ghost p-2">
                                                <i data-lucide="more-vertical" class="w-4 h-4"></i>
                                            </button>
                                            <div 
                                                x-show="open" 
                                                @click.outside="open = false"
                                                class="absolute right-0 mt-1 w-40 bg-slate-800 rounded-lg border border-slate-700 shadow-xl z-10"
                                            >
                                                <form method="POST" action="/rooms/status">
                                                    <input type="hidden" name="id" value="<?= $room['id'] ?>">
                                                    <input type="hidden" name="_csrf" value="<?= $csrfToken ?? '' ?>">
                                                    <button type="submit" name="status" value="available" class="w-full text-left px-3 py-2 text-sm text-emerald-400 hover:bg-slate-700/50">Set Available</button>
                                                    <button type="submit" name="status" value="maintenance" class="w-full text-left px-3 py-2 text-sm text-blue-400 hover:bg-slate-700/50">Set Maintenance</button>
                                                    <button type="submit" name="status" value="blocked" class="w-full text-left px-3 py-2 text-sm text-slate-400 hover:bg-slate-700/50">Block Room</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Add/Edit Room Modal -->
    <div 
        x-show="showModal"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        @click.self="closeModal()"
    >
        <div 
            x-show="showModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            class="glass-card w-full max-w-md"
        >
            <div class="flex items-center justify-between p-4 border-b border-slate-700/50">
                <h2 class="text-lg font-semibold text-white" x-text="editId ? 'Edit Room' : 'Add Room'"></h2>
                <button @click="closeModal()" class="text-slate-400 hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            
            <form method="POST" :action="editId ? '/rooms/update' : '/rooms/create'" class="p-4 space-y-4">
                <input type="hidden" name="_csrf" value="<?= $csrfToken ?? '' ?>">
                <input type="hidden" name="id" x-model="editId">
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Room Number *</label>
                        <input 
                            type="text" 
                            name="room_number" 
                            x-model="form.room_number"
                            class="form-input"
                            placeholder="101"
                            required
                        >
                    </div>
                    <div>
                        <label class="form-label">Floor</label>
                        <input 
                            type="text" 
                            name="floor" 
                            x-model="form.floor"
                            class="form-input"
                            placeholder="1st Floor"
                        >
                    </div>
                </div>
                
                <div>
                    <label class="form-label">Room Type *</label>
                    <select name="room_type_id" x-model="form.room_type_id" class="form-input" required>
                        <option value="">Select type...</option>
                        <?php foreach ($roomTypes as $type): ?>
                        <option value="<?= $type['id'] ?>"><?= htmlspecialchars($type['name']) ?> (₹<?= number_format($type['base_rate']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="form-label">Notes</label>
                    <textarea 
                        name="notes" 
                        x-model="form.notes"
                        class="form-input"
                        rows="2"
                        placeholder="Internal notes..."
                    ></textarea>
                </div>
                
                <div class="flex gap-3 pt-4 border-t border-slate-700/50">
                    <button type="button" @click="closeModal()" class="btn btn--secondary flex-1">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn--primary flex-1">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span x-text="editId ? 'Update' : 'Create'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function roomsPage() {
    return {
        showModal: false,
        editId: null,
        viewMode: 'grid',
        filterType: '',
        form: {
            room_number: '',
            floor: '',
            room_type_id: '',
            notes: ''
        },
        
        openModal(room = null) {
            if (room) {
                this.editId = room.id;
                this.form = {
                    room_number: room.room_number || '',
                    floor: room.floor || '',
                    room_type_id: room.room_type_id || '',
                    notes: room.notes || ''
                };
            } else {
                this.editId = null;
                this.form = {
                    room_number: '',
                    floor: '',
                    room_type_id: '',
                    notes: ''
                };
            }
            this.showModal = true;
            this.$nextTick(() => lucide.createIcons());
        },
        
        closeModal() {
            this.showModal = false;
            this.editId = null;
        }
    }
}
</script>
