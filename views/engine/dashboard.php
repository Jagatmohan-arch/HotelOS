<?php
/**
 * HotelOS - Hotel Engine Dashboard
 * Owner-Only Super Control System
 * 
 * Variables:
 * - $hotelSetup: Hotel configuration data
 * - $branding: Branding assets
 * - $staffCount: Total staff
 * - $recentLogs: Recent engine actions
 */

$hotelSetup = $hotelSetup ?? [];
$branding = $branding ?? [];
$staffCount = $staffCount ?? 0;
$recentLogs = $recentLogs ?? [];
?>

<div class="engine-page animate-fadeIn" x-data="engineDashboard()">
    <!-- Engine Header -->
    <div class="engine-header mb-6">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-red-500 to-orange-500 flex items-center justify-center">
                <i data-lucide="settings-2" class="w-6 h-6 text-white"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-white">Owner Control Center</h1>
                <p class="text-red-400 text-sm flex items-center gap-1">
                    <i data-lucide="shield-alert" class="w-4 h-4"></i>
                    Owner-Only Admin Panel
                </p>
            </div>
        </div>
    </div>
    
    <!-- Warning Banner -->
    <div class="bg-red-500/10 border border-red-500/30 rounded-lg p-4 mb-6">
        <div class="flex items-start gap-3">
            <i data-lucide="alert-triangle" class="w-5 h-5 text-red-400 flex-shrink-0 mt-0.5"></i>
            <div>
                <h3 class="text-red-300 font-semibold">Danger Zone</h3>
                <p class="text-red-400/80 text-sm">Actions in Hotel Engine are powerful and audited. All changes are logged with timestamps and cannot be undone.</p>
            </div>
        </div>
    </div>
    
    <!-- Quick Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="glass-card p-4">
            <div class="text-slate-400 text-sm mb-1">Hotel</div>
            <div class="text-white font-bold truncate"><?= htmlspecialchars($hotelSetup['name'] ?? 'Not Set') ?></div>
        </div>
        <div class="glass-card p-4">
            <div class="text-slate-400 text-sm mb-1">Staff Members</div>
            <div class="text-white font-bold"><?= $staffCount ?></div>
        </div>
        <div class="glass-card p-4">
            <div class="text-slate-400 text-sm mb-1">Data Lock</div>
            <div class="text-white font-bold">
                <?= $hotelSetup['data_locked_until'] ? date('d M Y', strtotime($hotelSetup['data_locked_until'])) : 'None' ?>
            </div>
        </div>
        <div class="glass-card p-4">
            <div class="text-slate-400 text-sm mb-1">Maintenance</div>
            <div class="text-white font-bold">
                <?= ($hotelSetup['maintenance_mode'] ?? false) ? '🔴 ON' : '🟢 OFF' ?>
            </div>
        </div>
    </div>
    
    <!-- Engine Modules -->
    <h2 class="text-lg font-semibold text-white mb-4">Control Modules</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
        <!-- Hotel Setup -->
        <a href="/engine/setup" class="engine-module glass-card p-5 hover:border-cyan-500/50 transition-all group">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-lg bg-cyan-500/20 flex items-center justify-center group-hover:bg-cyan-500/30 transition-colors">
                    <i data-lucide="building-2" class="w-6 h-6 text-cyan-400"></i>
                </div>
                <div>
                    <h3 class="text-white font-semibold">Hotel Setup</h3>
                    <p class="text-slate-400 text-sm">Name, GST, Timezone, Invoice rules</p>
                </div>
            </div>
        </a>
        
        <!-- Branding -->
        <a href="/engine/branding" class="engine-module glass-card p-5 hover:border-purple-500/50 transition-all group">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-lg bg-purple-500/20 flex items-center justify-center group-hover:bg-purple-500/30 transition-colors">
                    <i data-lucide="palette" class="w-6 h-6 text-purple-400"></i>
                </div>
                <div>
                    <h3 class="text-white font-semibold">Branding</h3>
                    <p class="text-slate-400 text-sm">Logo, Stamp, Signature</p>
                </div>
            </div>
        </a>
        
        <!-- Staff Control -->
        <a href="/engine/staff" class="engine-module glass-card p-5 hover:border-emerald-500/50 transition-all group">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-lg bg-emerald-500/20 flex items-center justify-center group-hover:bg-emerald-500/30 transition-colors">
                    <i data-lucide="users-cog" class="w-6 h-6 text-emerald-400"></i>
                </div>
                <div>
                    <h3 class="text-white font-semibold">Staff Controls</h3>
                    <p class="text-slate-400 text-sm">PIN, Block, Force Logout</p>
                </div>
            </div>
        </a>
        
        <!-- Bill Modification -->
        <a href="/engine/bills" class="engine-module glass-card p-5 hover:border-red-500/50 transition-all group border-red-500/20">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-lg bg-red-500/20 flex items-center justify-center group-hover:bg-red-500/30 transition-colors">
                    <i data-lucide="file-warning" class="w-6 h-6 text-red-400"></i>
                </div>
                <div>
                    <h3 class="text-red-300 font-semibold">Bill Modification ⚠️</h3>
                    <p class="text-slate-400 text-sm">Edit, Void invoices (DANGER)</p>
                </div>
            </div>
        </a>
        
        <!-- Financial Override -->
        <a href="/engine/finance" class="engine-module glass-card p-5 hover:border-amber-500/50 transition-all group border-amber-500/20">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-lg bg-amber-500/20 flex items-center justify-center group-hover:bg-amber-500/30 transition-colors">
                    <i data-lucide="wallet" class="w-6 h-6 text-amber-400"></i>
                </div>
                <div>
                    <h3 class="text-amber-300 font-semibold">Financial Override ⚠️</h3>
                    <p class="text-slate-400 text-sm">Cash adjustment, Lock reports</p>
                </div>
            </div>
        </a>
        
        <!-- Audit Logs -->
        <a href="/engine/audit" class="engine-module glass-card p-5 hover:border-slate-500/50 transition-all group">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-lg bg-slate-500/20 flex items-center justify-center group-hover:bg-slate-500/30 transition-colors">
                    <i data-lucide="scroll-text" class="w-6 h-6 text-slate-400"></i>
                </div>
                <div>
                    <h3 class="text-white font-semibold">Audit & Forensics</h3>
                    <p class="text-slate-400 text-sm">Deep logs, Diff view</p>
                </div>
            </div>
        </a>
    </div>
    
    <!-- Recent Engine Actions -->
    <?php if (!empty($recentLogs)): ?>
    <h2 class="text-lg font-semibold text-white mb-4">Recent Control Center Actions</h2>
    <div class="glass-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Risk</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($recentLogs, 0, 10) as $log): ?>
                    <tr>
                        <td class="text-slate-400 text-sm">
                            <?= date('d M h:i A', strtotime($log['created_at'])) ?>
                        </td>
                        <td class="text-white">
                            <?= htmlspecialchars($log['first_name'] ?? 'System') ?>
                        </td>
                        <td>
                            <span class="font-mono text-cyan-400 text-sm"><?= htmlspecialchars($log['action_type']) ?></span>
                        </td>
                        <td>
                            <?php
                            $riskBadge = match($log['risk_level']) {
                                'critical' => 'badge--red',
                                'high' => 'badge--amber',
                                'medium' => 'badge--yellow',
                                default => 'badge--gray'
                            };
                            ?>
                            <span class="badge <?= $riskBadge ?>"><?= ucfirst($log['risk_level']) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function engineDashboard() {
    return {
        // Dashboard state
    };
}
</script>

<style>
.engine-module {
    cursor: pointer;
}

.badge--amber {
    background: rgba(245, 158, 11, 0.15);
    color: #fbbf24;
    border: 1px solid rgba(245, 158, 11, 0.3);
}

.badge--gray {
    background: rgba(100, 116, 139, 0.15);
    color: #94a3b8;
    border: 1px solid rgba(100, 116, 139, 0.3);
}
</style>
