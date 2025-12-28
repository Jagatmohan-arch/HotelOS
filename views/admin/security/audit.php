<?php
// views/admin/security/audit.php

use HotelOS\Core\Auth;
use HotelOS\Handlers\AuditHandler;

$auth = Auth::getInstance();
$user = $auth->user();

// Security check
if ($user['role'] !== 'owner') {
    http_response_code(403);
    echo "Access Denied";
    exit;
}

$handler = new AuditHandler();

// Filter params
$filters = [
    'date_from' => $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days')),
    'date_to' => $_GET['date_to'] ?? date('Y-m-d'),
    'action' => $_GET['action'] ?? '',
    'limit' => 100
];

$logs = $handler->getTimeline($user['tenant_id'], $filters);
$fileterActions = $handler->getActionTypes($user['tenant_id']);

function formatActionBadge($action) {
    $colors = match($action) {
        'login', 'logout' => 'bg-blue-100 text-blue-800',
        'create' => 'bg-green-100 text-green-800',
        'update' => 'bg-yellow-100 text-yellow-800',
        'delete', 'kill_session' => 'bg-red-100 text-red-800',
        default => 'bg-gray-100 text-gray-800'
    };
    return "<span class='inline-flex items-center px-2 py-0.5 rounded text-xs font-medium uppercase {$colors}'>" . htmlspecialchars($action) . "</span>";
}
?>

<div class="space-y-6">
    <!-- Header & Filters -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Audit Log</h1>
            <p class="text-gray-500 text-sm mt-1">Track all system activities and security events.</p>
        </div>
        
        <form method="GET" class="flex flex-wrap items-end gap-3 bg-white p-3 rounded-lg border border-gray-200 shadow-sm">
            <div class="space-y-1">
                <label class="text-xs font-medium text-gray-500 uppercase">From</label>
                <input type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from']) ?>" 
                       class="block w-32 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
            <div class="space-y-1">
                <label class="text-xs font-medium text-gray-500 uppercase">To</label>
                <input type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to']) ?>" 
                       class="block w-32 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
            <div class="space-y-1">
                <label class="text-xs font-medium text-gray-500 uppercase">Action</label>
                <select name="action" class="block w-32 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="">All</option>
                    <?php foreach ($fileterActions as $type): ?>
                        <option value="<?= $type['action'] ?>" <?= $filters['action'] === $type['action'] ? 'selected' : '' ?>>
                            <?= ucfirst($type['action']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="px-3 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700">
                Filter
            </button>
            <?php if (!empty($_GET)): ?>
                <a href="/admin/security/audit" class="px-3 py-2 text-gray-600 hover:text-gray-900 text-sm">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Timeline -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm">
        <?php if (empty($logs)): ?>
            <div class="p-12 text-center">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 mb-4">
                    <i data-lucide="clipboard-list" class="w-6 h-6 text-gray-400"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900">No logs found</h3>
                <p class="text-gray-500 mt-1">Try adjusting your date filters.</p>
            </div>
        <?php else: ?>
            <ul class="divide-y divide-gray-200">
                <?php foreach ($logs as $log): ?>
                    <li class="p-4 hover:bg-gray-50 transition-colors">
                        <div class="flex space-x-3">
                            <!-- Icon -->
                            <div class="flex-shrink-0 mt-1">
                                <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center">
                                    <span class="text-xs font-bold text-gray-600">
                                        <?= substr($log['first_name'] ?? 'System', 0, 1) ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars(($log['first_name'] ?? 'System') . ' ' . ($log['last_name'] ?? '')) ?>
                                    <span class="text-gray-400 font-normal">performed</span> 
                                    <?= formatActionBadge($log['action']) ?>
                                    <span class="text-gray-400 font-normal">on</span>
                                    <span class="font-mono text-xs text-gray-600"><?= htmlspecialchars($log['entity_type'] . ( $log['entity_id'] ? " #{$log['entity_id']}" : '')) ?></span>
                                </p>
                                
                                <?php if ($log['description']): ?>
                                    <p class="mt-1 text-sm text-gray-600">
                                        <?= htmlspecialchars($log['description']) ?>
                                    </p>
                                <?php endif; ?>
                                
                                <!-- Metadata -->
                                <div class="mt-2 text-xs text-gray-400 flex items-center gap-3">
                                    <span class="flex items-center gap-1">
                                        <i data-lucide="clock" class="w-3 h-3"></i>
                                        <?= date('M j, Y h:i A', strtotime($log['created_at'])) ?>
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <i data-lucide="monitor" class="w-3 h-3"></i>
                                        <?= htmlspecialchars($log['ip_address']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
