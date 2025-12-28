<?php
// views/admin/security/sessions.php

use HotelOS\Core\Auth;
use HotelOS\Core\SessionHandler;

$auth = Auth::getInstance();
$user = $auth->user();

// Security check
if ($user['role'] !== 'owner') {
    http_response_code(403);
    echo "Access Denied";
    exit;
}

$sessionHandler = new SessionHandler();
$activeSessions = $sessionHandler->getActiveSessions($user['tenant_id']);

$analyzer = new \HotelOS\Core\SecurityAnalyzer();
$warnings = $analyzer->analyze($user['tenant_id']);

// Parsing User Agent helper
function getDeviceFromUa($ua) {
    if (strpos($ua, 'Mobile') !== false) return '📱 Mobile';
    if (strpos($ua, 'Tablet') !== false) return '📱 Tablet';
    if (strpos($ua, 'Windows') !== false) return '💻 Windows';
    if (strpos($ua, 'Macintosh') !== false) return '💻 Mac';
    if (strpos($ua, 'Linux') !== false) return '💻 Linux';
    return '🖥️ Desktop';
}
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Active Sessions</h1>
            <p class="text-gray-500 text-sm mt-1">Monitor who is currently logged into your hotel system.</p>
        </div>
        <button onclick="window.location.reload()" 
                class="flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition-colors">
            <i data-lucide="refresh-cw" class="w-4 h-4 mr-2"></i> Refresh
        </button>
    </div>

    <!-- Security Warnings -->
    <?php if (!empty($warnings)): ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i data-lucide="alert-triangle" class="h-5 w-5 text-yellow-400"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">Security Alerts Detected</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <ul class="list-disc pl-5 space-y-1">
                            <?php foreach ($warnings as $warning): ?>
                                <li><?= htmlspecialchars($warning['message']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Sessions List -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-gray-500 uppercase tracking-wider">
                        <th class="px-6 py-3 font-semibold">User</th>
                        <th class="px-6 py-3 font-semibold">Device / IP</th>
                        <th class="px-6 py-3 font-semibold">Last Activity</th>
                        <th class="px-6 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($activeSessions as $session): ?>
                        <tr class="hover:bg-gray-50 transition-colors <?= $session['is_current'] ? 'bg-blue-50/50' : '' ?>">
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-xs uppercase">
                                        <?= substr($session['first_name'], 0, 1) . substr($session['last_name'], 0, 1) ?>
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900">
                                            <?= htmlspecialchars($session['first_name'] . ' ' . $session['last_name']) ?>
                                            <?php if ($session['is_current']): ?>
                                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                    You
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-gray-500 text-xs text-transform uppercase tracking-wide">
                                            <?= htmlspecialchars($session['role']) ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-gray-900 font-medium flex items-center gap-1">
                                        <?= getDeviceFromUa($session['user_agent']) ?>
                                        <span class="text-xs text-gray-400 font-normal truncate max-w-[150px]" title="<?= htmlspecialchars($session['user_agent']) ?>">
                                            (<?= htmlspecialchars(substr($session['user_agent'], 0, 20)) ?>...)
                                        </span>
                                    </span>
                                    <span class="text-gray-500 text-xs font-mono mt-0.5">
                                        <?= htmlspecialchars($session['ip_address']) ?>
                                    </span>
                                </div>
                            </td>
                            
                            <td class="px-6 py-4 text-gray-500">
                                <div class="flex flex-col">
                                    <span class="text-gray-900 font-medium">
                                        <?php
                                            $diff = time() - $session['last_activity'];
                                            if ($diff < 60) echo "Just now";
                                            elseif ($diff < 3600) echo floor($diff/60) . " mins ago";
                                            else echo floor($diff/3600) . " hours ago";
                                        ?>
                                    </span>
                                    <span class="text-xs text-gray-400">
                                        <?= date('M j, H:i', $session['last_activity']) ?>
                                    </span>
                                </div>
                            </td>
                            
                            <td class="px-6 py-4 text-right">
                                <?php if (!$session['is_current']): ?>
                                    <form method="POST" action="/session/kill" class="inline-block"
                                          onsubmit="return confirm('Are you sure you want to force logout this user?');">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <input type="hidden" name="session_id" value="<?= $session['id'] ?>">
                                        <button type="submit" 
                                                class="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-md text-sm font-medium transition-colors flex items-center ml-auto gap-1">
                                            <i data-lucide="log-out" class="w-4 h-4"></i> Kill Session
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-gray-400 text-xsitalic px-3 py-1.5">Current Session</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (empty($activeSessions)): ?>
        <div class="p-8 text-center text-gray-500">
            No active sessions found (Wait, how are you seeing this?).
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Info Card -->
    <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 flex items-start gap-3">
        <i data-lucide="info" class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0"></i>
        <div class="text-sm text-blue-800">
            <p class="font-medium">About Active Sessions</p>
            <p class="mt-1 opacity-90">
                This list shows all devices currently logged into your HotelOS account. 
                If you see an unfamiliar device or IP address, click "Kill Session" immediately to log them out.
            </p>
        </div>
    </div>
</div>
