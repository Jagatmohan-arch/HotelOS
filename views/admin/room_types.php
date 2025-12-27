<?php
/**
 * HotelOS - Room Types Management View
 * 
 * CRUD interface for room types (Deluxe, Suite, etc.)
 * 
 * Variables:
 * - $roomTypes: Array of room types
 * - $editType: Room type being edited (null for new)
 * - $error: Error message if any
 * - $success: Success message if any
 */



$roomTypes = $roomTypes ?? [];
$editType = $editType ?? null;
$error = $error ?? null;
$success = $success ?? null;

// Amenities list
$amenitiesList = [
    'wifi' => 'WiFi',
    'ac' => 'Air Conditioning',
    'tv' => 'Television',
    'minibar' => 'Mini Bar',
    'safe' => 'In-Room Safe',
    'balcony' => 'Balcony',
    'bathtub' => 'Bathtub',
    'breakfast' => 'Breakfast Included',
];
?>

<div class="room-types-page animate-fadeIn" x-data="roomTypesPage()">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white">Room Types</h1>
            <p class="text-slate-400 text-sm mt-1">Manage your room categories and pricing</p>
        </div>
        <button 
            @click="openModal()"
            class="btn btn--primary"
        >
            <i data-lucide="plus" class="w-4 h-4"></i>
            Add Room Type
        </button>
    </div>
    
    <!-- Messages -->
    <?php if ($error): ?>
    <div class="mb-4 p-4 rounded-lg bg-red-500/10 border border-red-500/30 text-red-300 text-sm flex items-start gap-3">
        <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0"></i>
        <span><?= htmlspecialchars($error) ?></span>
    </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
    <div class="mb-4 p-4 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm flex items-start gap-3">
        <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0"></i>
        <span><?= htmlspecialchars($success) ?></span>
    </div>
    <?php endif; ?>
    
    <!-- Room Types Table -->
    <div class="glass-card overflow-hidden">
        <?php if (empty($roomTypes)): ?>
            <!-- Empty State -->
            <div class="text-center py-16">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-slate-700/50 flex items-center justify-center">
                    <i data-lucide="layers" class="w-8 h-8 text-slate-500"></i>
                </div>
                <h3 class="text-white font-medium mb-1">No Room Types Yet</h3>
                <p class="text-slate-400 text-sm mb-4">Create room categories like Deluxe, Suite, Standard</p>
                <button @click="openModal()" class="btn btn--primary">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    Add Your First Room Type
                </button>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th class="hidden md:table-cell">Base Rate</th>
                            <th class="hidden md:table-cell">GST</th>
                            <th class="hidden lg:table-cell">Capacity</th>
                            <th>Rooms</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roomTypes as $type): ?>
                            <?php 
                            $gstRate = (float)($type['base_rate'] ?? 0) < 7500 ? 12 : 18;
                            $amenities = json_decode($type['amenities'] ?? '[]', true) ?: [];
                            ?>
                            <tr>
                                <td>
                                    <span class="px-2 py-1 rounded bg-slate-700 text-cyan-400 font-mono text-xs">
                                        <?= htmlspecialchars($type['code']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div>
                                        <span class="text-white font-medium"><?= htmlspecialchars($type['name']) ?></span>
                                        <?php if (!empty($type['description'])): ?>
                                            <p class="text-xs text-slate-500 truncate max-w-xs"><?= htmlspecialchars($type['description']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="hidden md:table-cell">
                                    <span class="text-white font-semibold">₹<?= number_format((float)$type['base_rate']) ?></span>
                                    <span class="text-slate-500 text-xs">/night</span>
                                </td>
                                <td class="hidden md:table-cell">
                                    <span class="badge <?= $gstRate === 12 ? 'badge--green' : 'badge--yellow' ?>">
                                        <?= $gstRate ?>% GST
                                    </span>
                                </td>
                                <td class="hidden lg:table-cell">
                                    <span class="text-slate-400 text-sm">
                                        <?= $type['base_adults'] ?>A + <?= $type['base_children'] ?>C
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge--blue"><?= $type['room_count'] ?? 0 ?></span>
                                </td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button 
                                            @click='openModal(<?= json_encode($type) ?>)'
                                            class="btn btn--ghost p-2"
                                            title="Edit"
                                        >
                                            <i data-lucide="edit" class="w-4 h-4"></i>
                                        </button>
                                        <form method="POST" action="/room-types/delete" class="inline" 
                                              onsubmit="return confirm('Delete this room type?')">
                                            <input type="hidden" name="id" value="<?= $type['id'] ?>">
                                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                                            <button type="submit" class="btn btn--ghost p-2 text-red-400 hover:text-red-300" title="Delete">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- GST Info Card -->
    <div class="mt-6 glass-card p-4">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-lg bg-amber-500/20 flex items-center justify-center flex-shrink-0">
                <i data-lucide="info" class="w-5 h-5 text-amber-400"></i>
            </div>
            <div>
                <h3 class="text-white font-medium text-sm">GST Rate Auto-Calculation</h3>
                <p class="text-slate-400 text-xs mt-1">
                    GST is automatically calculated based on the base rate:
                    <span class="text-emerald-400">12% GST</span> for rates under ₹7,500 |
                    <span class="text-amber-400">18% GST</span> for rates ₹7,500 and above
                </p>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit Modal -->
    <div 
        x-show="showModal"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        @click.self="closeModal()"
    >
        <div 
            x-show="showModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            class="glass-card w-full max-w-lg max-h-[90vh] overflow-y-auto"
        >
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-4 border-b border-slate-700/50">
                <h2 class="text-lg font-semibold text-white" x-text="editId ? 'Edit Room Type' : 'Add Room Type'"></h2>
                <button @click="closeModal()" class="text-slate-400 hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            
            <!-- Modal Body -->
            <form method="POST" :action="editId ? '/room-types/update' : '/room-types/create'" class="p-4 space-y-4">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                <input type="hidden" name="id" x-model="editId">
                
                <div class="grid grid-cols-2 gap-4">
                    <!-- Name -->
                    <div class="col-span-2 sm:col-span-1">
                        <label class="form-label">Name *</label>
                        <input 
                            type="text" 
                            name="name" 
                            x-model="form.name"
                            class="form-input"
                            placeholder="e.g., Deluxe Room"
                            required
                        >
                    </div>
                    
                    <!-- Code -->
                    <div class="col-span-2 sm:col-span-1">
                        <label class="form-label">Code</label>
                        <input 
                            type="text" 
                            name="code" 
                            x-model="form.code"
                            class="form-input uppercase"
                            placeholder="e.g., DLX"
                            maxlength="10"
                        >
                        <p class="text-xs text-slate-500 mt-1">Auto-generated if empty</p>
                    </div>
                </div>
                
                <!-- Base Rate -->
                <div>
                    <label class="form-label">Base Rate (₹/night) *</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500">₹</span>
                        <input 
                            type="number" 
                            name="base_rate" 
                            x-model="form.base_rate"
                            @input="updateGstPreview()"
                            class="form-input pl-8"
                            placeholder="5000"
                            min="0"
                            step="100"
                            required
                        >
                    </div>
                    <p class="text-xs mt-2">
                        <span class="text-slate-400">Applicable GST:</span>
                        <span 
                            class="font-medium"
                            :class="gstPreview === 12 ? 'text-emerald-400' : 'text-amber-400'"
                            x-text="gstPreview + '% GST'"
                        ></span>
                    </p>
                </div>
                
                <!-- Description -->
                <div>
                    <label class="form-label">Description</label>
                    <textarea 
                        name="description" 
                        x-model="form.description"
                        class="form-input"
                        rows="2"
                        placeholder="Brief description of the room type..."
                    ></textarea>
                </div>
                
                <!-- Capacity -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Base Adults</label>
                        <input 
                            type="number" 
                            name="base_adults" 
                            x-model="form.base_adults"
                            class="form-input"
                            min="1"
                            max="10"
                        >
                    </div>
                    <div>
                        <label class="form-label">Max Adults</label>
                        <input 
                            type="number" 
                            name="max_adults" 
                            x-model="form.max_adults"
                            class="form-input"
                            min="1"
                            max="10"
                        >
                    </div>
                </div>
                
                <!-- Amenities -->
                <div>
                    <label class="form-label">Amenities</label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-2">
                        <?php foreach ($amenitiesList as $key => $label): ?>
                        <label class="flex items-center gap-2 p-2 rounded-lg bg-slate-800/50 cursor-pointer hover:bg-slate-700/50 transition-colors">
                            <input 
                                type="checkbox" 
                                name="amenities[]" 
                                value="<?= $key ?>"
                                x-model="form.amenities"
                                class="w-4 h-4 rounded border-slate-600 bg-slate-800 text-cyan-500"
                            >
                            <span class="text-xs text-slate-300"><?= $label ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Footer -->
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
function roomTypesPage() {
    return {
        showModal: false,
        editId: null,
        gstPreview: 12,
        form: {
            name: '',
            code: '',
            base_rate: '',
            description: '',
            base_adults: 2,
            max_adults: 3,
            amenities: []
        },
        
        openModal(type = null) {
            if (type) {
                this.editId = type.id;
                this.form = {
                    name: type.name || '',
                    code: type.code || '',
                    base_rate: type.base_rate || '',
                    description: type.description || '',
                    base_adults: type.base_adults || 2,
                    max_adults: type.max_adults || 3,
                    amenities: JSON.parse(type.amenities || '[]')
                };
            } else {
                this.editId = null;
                this.form = {
                    name: '',
                    code: '',
                    base_rate: '',
                    description: '',
                    base_adults: 2,
                    max_adults: 3,
                    amenities: []
                };
            }
            this.updateGstPreview();
            this.showModal = true;
            this.$nextTick(() => lucide.createIcons());
        },
        
        closeModal() {
            this.showModal = false;
            this.editId = null;
        },
        
        updateGstPreview() {
            const rate = parseFloat(this.form.base_rate) || 0;
            this.gstPreview = rate < 7500 ? 12 : 18;
        }
    }
}
</script>
