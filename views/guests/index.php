<?php
/**
 * Guest Management - List View
 */

$guestHandler = new \HotelOS\Handlers\GuestHandler();

$page = $_GET['page'] ?? 1;
$search = $_GET['q'] ?? '';
$result = $guestHandler->getAll((int)$page, 20, $search);

$guests = $result['data'];
$total = $result['total'];
$totalPages = $result['totalPages'];

// Category Badge Helper
function getCategoryBadge($cat) {
    if (class_exists('\HotelOS\Handlers\GuestHandler')) {
        $info = \HotelOS\Handlers\GuestHandler::getCategoryBadge($cat);
        $colors = [
            'amber' => 'bg-amber-500/20 text-amber-400 border-amber-500/30',
            'blue' => 'bg-blue-500/20 text-blue-400 border-blue-500/30',
            'purple' => 'bg-purple-500/20 text-purple-400 border-purple-500/30',
            'red' => 'bg-red-500/20 text-red-400 border-red-500/30',
            'gray' => 'bg-slate-500/20 text-slate-400 border-slate-500/30',
        ];
        $colorClass = $colors[$info['color']] ?? $colors['gray'];
        return "<span class=\"px-2 py-0.5 rounded text-xs border {$colorClass}\">{$info['label']}</span>";
    }
    return "<span class=\"px-2 py-0.5 rounded text-xs bg-slate-700 text-slate-300\">{$cat}</span>";
}
?>

<div class="flex flex-col h-full">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white mb-1">Guest Directory</h1>
            <p class="text-slate-400 text-sm">Manage guest profiles and history</p>
        </div>
        
        <div class="flex items-center gap-3">
             <div class="relative">
                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                <input type="text" 
                       id="guestSearch" 
                       value="<?= htmlspecialchars($search) ?>"
                       placeholder="Search name or phone..." 
                       class="pl-9 pr-4 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-white focus:outline-none focus:border-cyan-500 w-64 md:w-80"
                >
            </div>
            <button onclick="openAddGuestModal()" class="btn btn--primary">
                <i data-lucide="user-plus" class="w-4 h-4 mr-2"></i>
                Add Guest
            </button>
        </div>
    </div>

    <!-- Table Card -->
    <div class="glass-card flex-1 overflow-hidden flex flex-col">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-700/50 text-xs uppercase text-slate-400 bg-slate-800/50">
                        <th class="p-4 font-medium">Guest Name</th>
                        <th class="p-4 font-medium">Contact</th>
                        <th class="p-4 font-medium">Category</th>
                        <th class="p-4 font-medium text-center">Stays</th>
                        <th class="p-4 font-medium text-right">Total Spent</th>
                        <th class="p-4 font-medium">Last Visit</th>
                        <th class="p-4 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700/50">
                    <?php if (empty($guests)): ?>
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-500">
                                No guests found. Try a different search.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($guests as $guest): ?>
                        <tr class="hover:bg-slate-700/20 transition-colors group">
                            <td class="p-4">
                                <div class="font-medium text-white">
                                    <?= htmlspecialchars(($guest['title'] ?? '') . ' ' . $guest['first_name'] . ' ' . $guest['last_name']) ?>
                                </div>
                                <?php if ($guest['company_name']): ?>
                                    <div class="text-xs text-slate-500 flex items-center gap-1 mt-0.5">
                                        <i data-lucide="building-2" class="w-3 h-3"></i>
                                        <?= htmlspecialchars($guest['company_name']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="p-4">
                                <div class="text-sm text-slate-300 flex items-center gap-2">
                                    <i data-lucide="phone" class="w-3 h-3 text-slate-500"></i>
                                    <?= htmlspecialchars($guest['phone']) ?>
                                </div>
                                <?php if ($guest['email']): ?>
                                    <div class="text-xs text-slate-500 flex items-center gap-2 mt-1">
                                        <i data-lucide="mail" class="w-3 h-3 text-slate-500"></i>
                                        <?= htmlspecialchars($guest['email']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="p-4">
                                <?= getCategoryBadge($guest['category']) ?>
                            </td>
                            <td class="p-4 text-center">
                                <span class="bg-slate-800 text-slate-300 px-2 py-1 rounded text-xs">
                                    <?= number_format($guest['total_stays']) ?>
                                </span>
                            </td>
                            <td class="p-4 text-right font-mono text-slate-300">
                                ₹<?= number_format($guest['total_spent']) ?>
                            </td>
                            <td class="p-4 text-sm text-slate-400">
                                <?= $guest['last_visit_at'] ? date('M j, Y', strtotime($guest['last_visit_at'])) : '-' ?>
                            </td>
                            <td class="p-4 text-right">
                                <a href="/guests/profile?id=<?= $guest['id'] ?>" class="btn btn--sm btn--secondary opacity-0 group-hover:opacity-100 transition-opacity">
                                    View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="p-4 border-t border-slate-700/50 flex items-center justify-between bg-slate-800/30 mt-auto">
            <span class="text-sm text-slate-400">
                Page <?= $page ?> of <?= $totalPages ?> (<?= $total ?> guests)
            </span>
            <div class="flex gap-2">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&q=<?= urlencode($search) ?>" class="btn btn--sm btn--secondary">Previous</a>
                <?php endif; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&q=<?= urlencode($search) ?>" class="btn btn--sm btn--secondary">Next</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Debounced Search
    let searchTimeout;
    document.getElementById('guestSearch').addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const query = e.target.value;
            window.location.href = `?q=${encodeURIComponent(query)}`;
        }, 500);
    });
    
    function openAddGuestModal() {
        // Redirecting to create booking for now as the specialized add guest modal isn't built yet
        // OR we can link to a separate create page.
        // For Phase 3, let's keep it simple: Adding guest usually happens during booking.
        // But for this directory, we might want a standalone modal.
        // For now, alert
        alert('To add a new guest, please initiate a "New Booking"');
    }
</script>
