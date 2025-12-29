<?php
/**
 * HotelOS - Audit & Forensics Engine View
 * Owner-Only Deep Logs with Diff View
 */

$logs = $logs ?? [];
$staffList = $staffList ?? [];
?>

<div class="engine-page animate-fadeIn" x-data="auditEngine()">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
        <a href="/engine" class="text-slate-400 hover:text-white">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
        </a>
        <div class="w-10 h-10 rounded-lg bg-slate-500/20 flex items-center justify-center">
            <i data-lucide="scroll-text" class="w-5 h-5 text-slate-400"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-white">Audit & Forensics</h1>
            <p class="text-slate-400 text-sm">Engine Action Logs with Diff View</p>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="glass-card p-4 mb-6">
        <form method="GET" class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div>
                <label class="form-label">From Date</label>
                <input type="date" name="from" value="<?= htmlspecialchars($_GET['from'] ?? '') ?>" class="form-input">
            </div>
            <div>
                <label class="form-label">To Date</label>
                <input type="date" name="to" value="<?= htmlspecialchars($_GET['to'] ?? '') ?>" class="form-input">
            </div>
            <div>
                <label class="form-label">User</label>
                <select name="user_id" class="form-input">
                    <option value="">All Users</option>
                    <?php foreach ($staffList as $staff): ?>
                    <option value="<?= $staff['id'] ?>" <?= ($_GET['user_id'] ?? '') == $staff['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="form-label">Risk Level</label>
                <select name="risk" class="form-input">
                    <option value="">All</option>
                    <option value="critical" <?= ($_GET['risk'] ?? '') === 'critical' ? 'selected' : '' ?>>🔴 Critical</option>
                    <option value="high" <?= ($_GET['risk'] ?? '') === 'high' ? 'selected' : '' ?>>🟠 High</option>
                    <option value="medium" <?= ($_GET['risk'] ?? '') === 'medium' ? 'selected' : '' ?>>🟡 Medium</option>
                    <option value="low" <?= ($_GET['risk'] ?? '') === 'low' ? 'selected' : '' ?>>🟢 Low</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn btn--primary w-full">
                    <i data-lucide="search" class="w-4 h-4"></i>
                    Filter
                </button>
            </div>
        </form>
    </div>
    
    <!-- Logs -->
    <?php if (empty($logs)): ?>
    <div class="glass-card p-8 text-center">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-slate-500/20 flex items-center justify-center">
            <i data-lucide="file-search" class="w-8 h-8 text-slate-400"></i>
        </div>
        <h3 class="text-white font-medium mb-1">No Logs Found</h3>
        <p class="text-slate-400 text-sm">No engine actions match the current filters.</p>
    </div>
    <?php else: ?>
    <div class="space-y-4">
        <?php foreach ($logs as $log): ?>
        <div class="glass-card p-4" x-data="{ expanded: false }">
            <!-- Log Header -->
            <div class="flex items-center justify-between cursor-pointer" @click="expanded = !expanded">
                <div class="flex items-center gap-4">
                    <?php
                    $riskIcon = match($log['risk_level']) {
                        'critical' => '🔴',
                        'high' => '🟠',
                        'medium' => '🟡',
                        default => '🟢'
                    };
                    ?>
                    <span class="text-2xl"><?= $riskIcon ?></span>
                    <div>
                        <div class="text-white font-medium">
                            <?= htmlspecialchars(str_replace('_', ' ', ucwords($log['action_type'], '_'))) ?>
                        </div>
                        <div class="text-slate-400 text-sm">
                            <?= htmlspecialchars($log['first_name'] ?? 'System') ?> • 
                            <?= date('d M Y, h:i A', strtotime($log['created_at'])) ?>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="badge badge--<?= $log['risk_level'] === 'critical' ? 'red' : ($log['risk_level'] === 'high' ? 'amber' : 'gray') ?>">
                        <?= ucfirst($log['risk_level']) ?>
                    </span>
                    <i data-lucide="chevron-down" class="w-5 h-5 text-slate-400 transition-transform" :class="expanded && 'rotate-180'"></i>
                </div>
            </div>
            
            <!-- Log Details (Expanded) -->
            <div x-show="expanded" x-collapse class="mt-4 pt-4 border-t border-slate-700/50">
                <!-- Reason -->
                <div class="mb-3">
                    <span class="text-slate-500 text-sm">Reason:</span>
                    <p class="text-white"><?= htmlspecialchars($log['reason']) ?></p>
                </div>
                
                <!-- Diff View -->
                <?php 
                $oldValues = $log['old_values'] ? json_decode($log['old_values'], true) : null;
                $newValues = $log['new_values'] ? json_decode($log['new_values'], true) : null;
                ?>
                
                <?php if ($oldValues || $newValues): ?>
                <div class="bg-slate-800/50 rounded p-3 font-mono text-sm">
                    <div class="text-slate-500 mb-2">Changes:</div>
                    <?php 
                    $allKeys = array_unique(array_merge(
                        array_keys($oldValues ?? []), 
                        array_keys($newValues ?? [])
                    ));
                    foreach ($allKeys as $key): 
                        $old = $oldValues[$key] ?? null;
                        $new = $newValues[$key] ?? null;
                        if ($old !== $new):
                    ?>
                    <div class="flex gap-2 mb-1">
                        <span class="text-slate-400"><?= htmlspecialchars($key) ?>:</span>
                        <?php if ($old !== null): ?>
                        <span class="text-red-400 line-through"><?= htmlspecialchars($old) ?></span>
                        <?php endif; ?>
                        <span class="text-slate-500">→</span>
                        <span class="text-emerald-400"><?= htmlspecialchars($new) ?></span>
                    </div>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
                <?php endif; ?>
                
                <!-- Meta -->
                <div class="mt-3 text-xs text-slate-500">
                    IP: <?= htmlspecialchars($log['ip_address'] ?? 'N/A') ?> • 
                    <?= $log['password_confirmed'] ? '🔐 Password Confirmed' : '' ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
function auditEngine() {
    return {};
}
</script>

<style>
.badge--amber {
    background: rgba(245, 158, 11, 0.15);
    color: #fbbf24;
    border: 1px solid rgba(245, 158, 11, 0.3);
}
</style>
