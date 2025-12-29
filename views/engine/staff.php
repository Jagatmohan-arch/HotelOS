<?php
/**
 * HotelOS - Staff Engine View
 * Owner-Only Staff Management
 */

$staffList = $staffList ?? [];
?>

<div class="engine-page animate-fadeIn" x-data="staffEngine()">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
        <a href="/engine" class="text-slate-400 hover:text-white">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
        </a>
        <div class="w-10 h-10 rounded-lg bg-emerald-500/20 flex items-center justify-center">
            <i data-lucide="users-cog" class="w-5 h-5 text-emerald-400"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-white">Staff Engine</h1>
            <p class="text-slate-400 text-sm">PIN Management, Block, Force Logout</p>
        </div>
    </div>
    
    <!-- Staff List -->
    <div class="glass-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Staff</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>PIN</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($staffList as $staff): ?>
                    <tr>
                        <td>
                            <div class="text-white font-medium"><?= htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']) ?></div>
                            <div class="text-slate-400 text-xs"><?= htmlspecialchars($staff['email']) ?></div>
                        </td>
                        <td>
                            <span class="badge badge--<?= $staff['role'] === 'manager' ? 'blue' : 'gray' ?>">
                                <?= ucfirst($staff['role']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($staff['is_active']): ?>
                                <span class="badge badge--green">Active</span>
                            <?php else: ?>
                                <span class="badge badge--red">Blocked</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($staff['has_pin']): ?>
                                <span class="text-emerald-400">✓ Set</span>
                            <?php else: ?>
                                <span class="text-slate-500">Not Set</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-slate-400 text-sm">
                            <?= $staff['last_login_at'] ? date('d M, h:i A', strtotime($staff['last_login_at'])) : 'Never' ?>
                        </td>
                        <td>
                            <div class="flex gap-2">
                                <button 
                                    @click="generatePin(<?= $staff['id'] ?>, '<?= htmlspecialchars($staff['first_name']) ?>')"
                                    class="btn btn--sm btn--secondary"
                                    title="Generate PIN"
                                >
                                    <i data-lucide="key" class="w-4 h-4"></i>
                                </button>
                                
                                <?php if ($staff['is_active']): ?>
                                <button 
                                    @click="blockStaff(<?= $staff['id'] ?>, '<?= htmlspecialchars($staff['first_name']) ?>')"
                                    class="btn btn--sm btn--danger"
                                    title="Block"
                                >
                                    <i data-lucide="user-x" class="w-4 h-4"></i>
                                </button>
                                <?php else: ?>
                                <button 
                                    @click="unblockStaff(<?= $staff['id'] ?>, '<?= htmlspecialchars($staff['first_name']) ?>')"
                                    class="btn btn--sm btn--primary"
                                    title="Unblock"
                                >
                                    <i data-lucide="user-check" class="w-4 h-4"></i>
                                </button>
                                <?php endif; ?>
                                
                                <button 
                                    @click="forceLogout(<?= $staff['id'] ?>, '<?= htmlspecialchars($staff['first_name']) ?>')"
                                    class="btn btn--sm btn--secondary"
                                    title="Force Logout"
                                >
                                    <i data-lucide="log-out" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- PIN Modal -->
    <div 
        x-show="pinModal.show"
        x-transition
        class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        @click.self="pinModal.show = false"
    >
        <div class="glass-card w-full max-w-sm text-center p-6">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-emerald-500/20 flex items-center justify-center">
                <i data-lucide="key" class="w-8 h-8 text-emerald-400"></i>
            </div>
            <h2 class="text-xl font-bold text-white mb-2">PIN Generated</h2>
            <p class="text-slate-400 mb-4">New PIN for <span x-text="pinModal.name" class="text-white"></span></p>
            <div class="text-4xl font-mono font-bold text-emerald-400 mb-4 tracking-widest" x-text="pinModal.pin"></div>
            <p class="text-red-400 text-sm mb-4">⚠️ This PIN will only be shown once!</p>
            <button @click="pinModal.show = false" class="btn btn--primary w-full">Got it</button>
        </div>
    </div>
</div>

<script>
function staffEngine() {
    return {
        processing: false,
        pinModal: { show: false, name: '', pin: '' },
        
        async generatePin(id, name) {
            const reason = prompt('Reason for generating new PIN:');
            if (!reason) return;
            
            this.processing = true;
            try {
                const res = await fetch(`/api/engine/staff/${id}/pin`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ reason })
                });
                const data = await res.json();
                
                if (data.success) {
                    this.pinModal = { show: true, name: name, pin: data.pin };
                    this.$nextTick(() => lucide.createIcons());
                } else {
                    alert('Error: ' + (data.error || 'Failed'));
                }
            } catch (e) {
                alert('Network error');
            }
            this.processing = false;
        },
        
        async blockStaff(id, name) {
            const reason = prompt(`Reason for blocking ${name}:`);
            if (!reason) return;
            
            const res = await fetch(`/api/engine/staff/${id}/block`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ active: false, reason })
            });
            const data = await res.json();
            
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Failed'));
            }
        },
        
        async unblockStaff(id, name) {
            const reason = prompt(`Reason for unblocking ${name}:`);
            if (!reason) return;
            
            const res = await fetch(`/api/engine/staff/${id}/block`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ active: true, reason })
            });
            const data = await res.json();
            
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Failed'));
            }
        },
        
        async forceLogout(id, name) {
            if (!confirm(`Force logout ${name} from all devices?`)) return;
            
            const res = await fetch(`/api/engine/staff/${id}/logout`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ reason: 'Manual force logout by owner' })
            });
            const data = await res.json();
            
            if (data.success) {
                alert(`${name} has been logged out from all devices.`);
            } else {
                alert('Error: ' + (data.error || 'Failed'));
            }
        }
    };
}
</script>

<style>
.btn--sm {
    padding: 0.375rem 0.5rem;
    font-size: 0.75rem;
}

.btn--danger {
    background: rgba(239, 68, 68, 0.2);
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.btn--danger:hover {
    background: rgba(239, 68, 68, 0.3);
}

.badge--blue {
    background: rgba(59, 130, 246, 0.15);
    color: #60a5fa;
    border: 1px solid rgba(59, 130, 246, 0.3);
}
</style>
