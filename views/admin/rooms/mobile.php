<?php
/**
 * HotelOS - Mobile Room Status Management
 * 
 * Grid view for quick status toggling.
 * Optimized for housekeeping/manager on mobile.
 */
?>
<div class="mobile-rooms md:hidden pb-24" x-data="mobileRooms()">
    
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold text-white">Room Status</h1>
        
        <!-- Filter -->
        <div class="relative">
            <select x-model="filter" class="appearance-none bg-slate-800 border border-slate-700 text-white text-sm rounded-lg py-2 pl-3 pr-8 focus:outline-none focus:border-indigo-500">
                <option value="all">All Floors</option>
                <?php foreach ($floors as $floor): ?>
                <option value="<?= htmlspecialchars($floor) ?>">Floor <?= htmlspecialchars($floor) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-400">
                <i data-lucide="chevron-down" class="w-4 h-4"></i>
            </div>
        </div>
    </div>
    
    <!-- Status Legend -->
    <div class="flex gap-2 mb-6 overflow-x-auto pb-2 no-scrollbar">
        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-slate-800/50 border border-slate-700 shrink-0">
            <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
            <span class="text-xs text-slate-300">Clean</span>
        </div>
        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-slate-800/50 border border-slate-700 shrink-0">
            <div class="w-2 h-2 rounded-full bg-amber-500"></div>
            <span class="text-xs text-slate-300">Dirty</span>
        </div>
        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-slate-800/50 border border-slate-700 shrink-0">
            <div class="w-2 h-2 rounded-full bg-rose-500"></div>
            <span class="text-xs text-slate-300">Maint.</span>
        </div>
        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-slate-800/50 border border-slate-700 shrink-0">
            <div class="w-2 h-2 rounded-full bg-indigo-500"></div>
            <span class="text-xs text-slate-300">Occupied</span>
        </div>
    </div>

    <!-- Rooms Grid -->
    <div class="grid grid-cols-3 gap-3">
        <template x-for="room in filteredRooms" :key="room.id">
            <button 
                @click="openRoomAction(room)"
                class="relative aspect-square rounded-2xl p-3 flex flex-col items-center justify-center gap-2 transition-all active:scale-95"
                :class="getRoomClass(room)"
            >
                <span class="text-lg font-bold" x-text="room.room_number"></span>
                
                <!-- Status Icon -->
                <div x-show="room.status === 'occupied'">
                    <i data-lucide="user" class="w-4 h-4 opacity-75"></i>
                </div>
                <div x-show="room.status === 'available' && room.housekeeping_status === 'dirty'">
                    <i data-lucide="sparkles" class="w-4 h-4 opacity-75"></i>
                </div>
                 <div x-show="room.status === 'maintenance'">
                    <i data-lucide="wrench" class="w-4 h-4 opacity-75"></i>
                </div>
                 <div x-show="room.status === 'blocked'">
                    <i data-lucide="ban" class="w-4 h-4 opacity-75"></i>
                </div>
            </button>
        </template>
    </div>

    <!-- Action Sheet Modal -->
    <div x-show="selectedRoom" style="display: none;" class="fixed inset-0 z-50" x-cloak>
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="selectedRoom = null"></div>
        
        <div 
            class="absolute bottom-0 left-0 right-0 bg-slate-900 border-t border-slate-700 rounded-t-2xl p-6 shadow-2xl transform transition-transform duration-300"
            x-transition:enter="translate-y-full"
            x-transition:enter-end="translate-y-0"
            x-transition:leave="translate-y-full"
        >
            <div class="w-12 h-1.5 bg-slate-700 rounded-full mx-auto mb-6"></div>
            
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="text-2xl font-bold text-white">Room <span x-text="selectedRoom?.room_number"></span></h3>
                    <p class="text-slate-400 text-sm" x-text="selectedRoom?.room_type_name"></p>
                </div>
                <!-- Current Status Badge -->
                <div class="px-3 py-1 rounded-lg text-xs font-bold uppercase tracking-wider" :class="getStatusBadgeClass(selectedRoom)">
                    <span x-text="selectedRoom?.status === 'available' ? selectedRoom?.housekeeping_status : selectedRoom?.status"></span>
                </div>
            </div>

            <!-- Actions Grid -->
            <div class="grid grid-cols-2 gap-3">
                <!-- Mark Clean -->
                <button 
                    @click="updateStatus('clean')"
                    x-show="selectedRoom?.status === 'available'"
                    class="p-4 rounded-xl bg-slate-800 hover:bg-emerald-500/20 hover:border-emerald-500/50 border border-slate-700 transition-all group"
                >
                    <div class="w-10 h-10 rounded-full bg-emerald-500/20 flex items-center justify-center mb-2 group-hover:bg-emerald-500 group-hover:text-white transition-colors">
                        <i data-lucide="check" class="w-5 h-5 text-emerald-400 group-hover:text-white"></i>
                    </div>
                    <div class="text-left">
                        <div class="font-bold text-white">Mark Clean</div>
                        <div class="text-xs text-slate-400">Ready for guest</div>
                    </div>
                </button>

                <!-- Mark Dirty -->
                <button 
                    @click="updateStatus('dirty')"
                    x-show="selectedRoom?.status === 'available'"
                    class="p-4 rounded-xl bg-slate-800 hover:bg-amber-500/20 hover:border-amber-500/50 border border-slate-700 transition-all group"
                >
                    <div class="w-10 h-10 rounded-full bg-amber-500/20 flex items-center justify-center mb-2 group-hover:bg-amber-500 group-hover:text-white transition-colors">
                        <i data-lucide="sparkles" class="w-5 h-5 text-amber-400 group-hover:text-white"></i>
                    </div>
                    <div class="text-left">
                        <div class="font-bold text-white">Mark Dirty</div>
                        <div class="text-xs text-slate-400">Needs cleaning</div>
                    </div>
                </button>

                <!-- Maintenance Toggle -->
                <button 
                    @click="updateStatus('maintenance')"
                    x-show="selectedRoom?.status !== 'occupied'"
                    class="p-4 rounded-xl bg-slate-800 hover:bg-rose-500/20 hover:border-rose-500/50 border border-slate-700 transition-all group"
                >
                    <div class="w-10 h-10 rounded-full bg-rose-500/20 flex items-center justify-center mb-2 group-hover:bg-rose-500 group-hover:text-white transition-colors">
                        <i data-lucide="wrench" class="w-5 h-5 text-rose-400 group-hover:text-white"></i>
                    </div>
                    <div class="text-left">
                        <div class="font-bold text-white" x-text="selectedRoom?.status === 'maintenance' ? 'End Maintenance' : 'Maintenance'"></div>
                        <div class="text-xs text-slate-400">Toggle status</div>
                    </div>
                </button>
                
                 <!-- Block Toggle -->
                <button 
                    @click="updateStatus('blocked')"
                    x-show="selectedRoom?.status !== 'occupied'"
                    class="p-4 rounded-xl bg-slate-800 hover:bg-slate-500/20 hover:border-slate-500/50 border border-slate-700 transition-all group"
                >
                    <div class="w-10 h-10 rounded-full bg-slate-500/20 flex items-center justify-center mb-2 group-hover:bg-slate-500 group-hover:text-white transition-colors">
                        <i data-lucide="ban" class="w-5 h-5 text-slate-400 group-hover:text-white"></i>
                    </div>
                    <div class="text-left">
                        <div class="font-bold text-white" x-text="selectedRoom?.status === 'blocked' ? 'Unblock' : 'Block Room'"></div>
                        <div class="text-xs text-slate-400">Toggle block</div>
                    </div>
                </button>
            </div>
            
            <div x-show="selectedRoom?.status === 'occupied'" class="mt-4 p-4 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center gap-3">
                 <div class="w-10 h-10 rounded-full bg-indigo-500/20 flex items-center justify-center shrink-0">
                    <i data-lucide="info" class="w-5 h-5 text-indigo-400"></i>
                </div>
                <div>
                     <div class="font-bold text-white">Room grid is locked</div>
                     <div class="text-xs text-indigo-300">Cannot change status while occupied.</div>
                </div>
            </div>

            <button @click="selectedRoom = null" class="w-full mt-6 py-4 rounded-xl bg-slate-800 text-slate-400 font-medium">
                Cancel
            </button>
        </div>
    </div>
</div>

<script>
function mobileRooms() {
    return {
        rooms: <?= json_encode($rooms) ?>,
        filter: 'all',
        selectedRoom: null,
        
        get filteredRooms() {
            if (this.filter === 'all') return this.rooms;
            return this.rooms.filter(r => r.floor.toString() === this.filter);
        },
        
        getRoomClass(room) {
            // Occupied
            if (room.status === 'occupied') {
                return 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/50';
            }
            // Maintenance
            if (room.status === 'maintenance') {
                return 'bg-rose-900/40 border border-rose-500/30 text-rose-400';
            }
             // Blocked
            if (room.status === 'blocked') {
                return 'bg-slate-800 border border-slate-600/50 text-slate-500';
            }
            // Dirty
            if (room.housekeeping_status === 'dirty') {
                return 'bg-amber-500 text-slate-900 font-bold';
            }
            // Clean (Default Available)
            return 'bg-emerald-500 text-slate-900 font-bold';
        },
        
        getStatusBadgeClass(room) {
            if (!room) return '';
             if (room.status === 'occupied') return 'bg-indigo-500/20 text-indigo-400 border border-indigo-500/30';
             if (room.status === 'maintenance') return 'bg-rose-500/20 text-rose-400 border border-rose-500/30';
             if (room.status === 'blocked') return 'bg-slate-700 text-slate-400 border border-slate-600';
             if (room.housekeeping_status === 'dirty') return 'bg-amber-500/20 text-amber-400 border border-amber-500/30';
             return 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30';
        },

        openRoomAction(room) {
            this.selectedRoom = room;
            this.$nextTick(() => lucide.createIcons());
        },

        async updateStatus(action) {
            if (!confirm(`Confirm action: ${action}?`)) return;
            
            const roomId = this.selectedRoom.id;
            
            try {
                // Determine API endpoint based on action
                let endpoint = '';
                let method = 'POST';
                let body = {};
                
                if (action === 'clean' || action === 'dirty') {
                    endpoint = `/api/rooms/${roomId}/housekeeping`;
                    body = { status: action };
                } else if (action === 'maintenance') {
                    // Toggle logic handled by backend usually, but here specific endpoint
                    const newStatus = this.selectedRoom.status === 'maintenance' ? 'available' : 'maintenance';
                    endpoint = `/api/rooms/${roomId}/status`;
                    body = { status: newStatus };
                } else if (action === 'blocked') {
                     const newStatus = this.selectedRoom.status === 'blocked' ? 'available' : 'blocked';
                    endpoint = `/api/rooms/${roomId}/status`;
                     body = { status: newStatus };
                }

                const res = await fetch(endpoint, {
                    method: method,
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '<?= $csrfToken ?>'
                    },
                    body: JSON.stringify(body)
                });
                
                const data = await res.json();
                
                if (data.success) {
                    // Update local state
                    const roomIndex = this.rooms.findIndex(r => r.id === roomId);
                    if (roomIndex > -1) {
                         // Refresh page to get latest state or update manually
                         window.location.reload();
                    }
                    this.selectedRoom = null;
                } else {
                    alert('Failed: ' + (data.error || 'Unknown error'));
                }
            } catch (e) {
                console.error(e);
                alert('Connection error');
            }
        }
    }
}
</script>
