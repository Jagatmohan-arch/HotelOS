<?php
/**
 * Guest Profile - Detail View
 */

$id = $_GET['id'] ?? 0;
$guestHandler = new \HotelOS\Handlers\GuestHandler();
$uploadHandler = new \HotelOS\Handlers\UploadHandler();
$bookingHandler = new \HotelOS\Handlers\BookingHandler();

$guest = $guestHandler->getById((int)$id);

if (!$guest) {
    echo "<div class='p-8 text-center text-red-400'>Guest not found.</div>";
    return;
}

// Get history
// We need a method in GuestHandler or BookingHandler to get bookings by guest
// BookingHandler doesn't have it exposed publicly yet, let's ad-hoc query or use existing pattern
// For now, let's assume we can add a method or just query here for simplicity in view (not ideal but practical for now)
// Or use: $db->query("SELECT * FROM bookings WHERE guest_id = ...") 
// Let's rely on GuestHandler::getHistory if it existed, or add it later.
// For now, we will query purely SQL here as a quick fix, or add method.
// Let's add method to BookingHandler dynamically later? No, let's just use raw DB if needed or skip history content for now.
// Wait, let's do it right. I will fetch bookings using DB instance if accessible, or just show placeholder.
// Actually, I can instantiate DB here.
$db = \HotelOS\Core\Database::getInstance();
$history = $db->query(
    "SELECT b.*, r.room_number 
     FROM bookings b 
     LEFT JOIN rooms r ON b.room_id = r.id
     WHERE b.guest_id = :id AND b.tenant_id = :tenant_id
     ORDER BY b.check_in_date DESC LIMIT 10",
    ['id' => $id, 'tenant_id' => \HotelOS\Core\TenantContext::getId()],
    enforceTenant: false
);

// Helper for category badge (reused)
function getProfileBadge($cat) {
    if (class_exists('\HotelOS\Handlers\GuestHandler')) {
        $info = \HotelOS\Handlers\GuestHandler::getCategoryBadge($cat);
        $colors = [
            'amber' => 'bg-amber-500 text-amber-900',
            'blue' => 'bg-blue-500 text-blue-900',
            'purple' => 'bg-purple-500 text-purple-900',
            'red' => 'bg-red-500 text-red-900',
            'gray' => 'bg-slate-500 text-slate-100',
        ];
        $colorClass = $colors[$info['color']] ?? $colors['gray'];
        return "<span class=\"px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider {$colorClass}\">{$info['label']}</span>";
    }
    return "";
}

$activeTab = $_GET['tab'] ?? 'overview';
?>

<div class="max-w-5xl mx-auto">
    <!-- Back -->
    <a href="/guests" class="inline-flex items-center text-slate-400 hover:text-white mb-6 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i> Back to Directory
    </a>

    <!-- Header Card -->
    <div class="glass-card p-6 mb-6 flex flex-col md:flex-row md:items-start justify-between gap-6 relative overflow-hidden">
        <!-- Background Decor -->
        <div class="absolute top-0 right-0 w-64 h-64 bg-cyan-500/5 rounded-full blur-3xl -mr-16 -mt-16 pointer-events-none"></div>

        <div class="flex items-start gap-5 relative z-10">
            <!-- Avatar / Initials -->
            <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-slate-700 to-slate-800 flex items-center justify-center text-2xl font-bold text-white shadow-lg border border-slate-600/50">
                <?= strtoupper(substr($guest['first_name'], 0, 1) . substr($guest['last_name'], 0, 1)) ?>
            </div>
            
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <h1 class="text-3xl font-bold text-white">
                        <?= htmlspecialchars($guest['title'] . ' ' . $guest['first_name'] . ' ' . $guest['last_name']) ?>
                    </h1>
                    <?= getProfileBadge($guest['category']) ?>
                </div>
                
                <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-slate-400">
                    <div class="flex items-center gap-2">
                        <i data-lucide="phone" class="w-4 h-4 text-slate-500"></i>
                        <?= htmlspecialchars($guest['phone']) ?>
                    </div>
                    <?php if ($guest['email']): ?>
                    <div class="flex items-center gap-2">
                        <i data-lucide="mail" class="w-4 h-4 text-slate-500"></i>
                        <?= htmlspecialchars($guest['email']) ?>
                    </div>
                    <?php endif; ?>
                    <div class="flex items-center gap-2">
                        <i data-lucide="map-pin" class="w-4 h-4 text-slate-500"></i>
                        <?= htmlspecialchars($guest['city'] ?? 'Unknown City') ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Key Stats -->
        <div class="flex items-center gap-6 md:border-l md:border-slate-700 md:pl-6 relative z-10">
            <div class="text-center">
                <div class="text-2xl font-bold text-white"><?= number_format($guest['total_stays']) ?></div>
                <div class="text-xs text-slate-500 uppercase tracking-wider">Stays</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-cyan-400">₹<?= number_format($guest['total_spent'] / 1000, 1) ?>k</div>
                <div class="text-xs text-slate-500 uppercase tracking-wider">Spent</div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="flex border-b border-slate-700 mb-6 gap-6">
        <button onclick="switchTab('overview')" id="tab-overview" class="pb-3 text-sm font-medium border-b-2 border-cyan-500 text-cyan-400 transition-colors">Overview</button>
        <button onclick="switchTab('history')" id="tab-history" class="pb-3 text-sm font-medium border-b-2 border-transparent text-slate-400 hover:text-white transition-colors">Stay History</button>
        <button onclick="switchTab('documents')" id="tab-documents" class="pb-3 text-sm font-medium border-b-2 border-transparent text-slate-400 hover:text-white transition-colors">Documents</button>
    </div>

    <!-- Content: Overview -->
    <div id="content-overview" class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Details Column -->
        <div class="md:col-span-2 space-y-6">
            <div class="glass-card p-6">
                <h3 class="text-lg font-semibold text-white mb-4">Personal Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-6">
                    <div>
                        <label class="text-xs text-slate-500 uppercase">Nationality</label>
                        <div class="text-slate-200"><?= htmlspecialchars($guest['nationality']) ?></div>
                    </div>
                    <div>
                        <label class="text-xs text-slate-500 uppercase">Address</label>
                        <div class="text-slate-200"><?= htmlspecialchars($guest['address'] ?? '-') ?></div>
                    </div>
                    <div>
                        <label class="text-xs text-slate-500 uppercase">City / State</label>
                        <div class="text-slate-200">
                            <?= htmlspecialchars(($guest['city'] ?? '-') . ', ' . ($guest['state'] ?? '-')) ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="glass-card p-6">
                <h3 class="text-lg font-semibold text-white mb-4">Company Details</h3>
                <?php if ($guest['company_name']): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-6">
                        <div>
                            <label class="text-xs text-slate-500 uppercase">Company Name</label>
                            <div class="text-slate-200"><?= htmlspecialchars($guest['company_name']) ?></div>
                        </div>
                        <div>
                            <label class="text-xs text-slate-500 uppercase">GSTIN</label>
                            <div class="text-slate-200 font-mono"><?= htmlspecialchars($guest['company_gst'] ?? '-') ?></div>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-slate-500 text-sm">No company details linked.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <div class="glass-card p-6">
                <h3 class="text-lg font-semibold text-white mb-4">Notes</h3>
                <div class="bg-slate-900/50 rounded-lg p-4 text-sm text-slate-400 min-h-[100px] whitespace-pre-wrap">
                    <?= htmlspecialchars($guest['notes'] ?? 'No notes available.') ?>
                </div>
            </div>
            
            <!-- Category Actions -->
            <div class="glass-card p-6">
                 <h3 class="text-lg font-semibold text-white mb-4">Manage Status</h3>
                 <form action="/api/guests/category" method="POST" class="space-y-3">
                     <input type="hidden" name="id" value="<?= $id ?>">
                     <select name="category" class="w-full bg-slate-800 border-slate-700 rounded text-sm text-white p-2">
                         <option value="regular" <?= $guest['category'] == 'regular' ? 'selected' : '' ?>>Regular</option>
                         <option value="vip" <?= $guest['category'] == 'vip' ? 'selected' : '' ?>>VIP</option>
                         <option value="corporate" <?= $guest['category'] == 'corporate' ? 'selected' : '' ?>>Corporate</option>
                         <option value="blacklisted" <?= $guest['category'] == 'blacklisted' ? 'selected' : '' ?>>Blacklisted</option>
                     </select>
                     <button type="submit" class="w-full btn btn--secondary btn--sm">Update Category</button>
                 </form>
            </div>
        </div>
    </div>
    
    <!-- Content: History -->
    <div id="content-history" class="hidden">
        <div class="glass-card overflow-hidden">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-800/50 text-xs uppercase text-slate-400">
                        <th class="p-4">Booking #</th>
                        <th class="p-4">Room</th>
                        <th class="p-4">Dates</th>
                        <th class="p-4">Total</th>
                        <th class="p-4">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700/50">
                    <?php foreach ($history as $bk): ?>
                    <tr class="hover:bg-slate-700/20">
                        <td class="p-4 font-mono text-sm text-cyan-400">
                            <a href="/bookings/<?= $bk['id'] ?>">#<?= $bk['booking_number'] ?></a>
                        </td>
                        <td class="p-4 text-sm text-white"><?= $bk['room_number'] ?? 'Unassigned' ?></td>
                        <td class="p-4 text-sm text-slate-300">
                            <?= date('M j', strtotime($bk['check_in_date'])) ?> - <?= date('M j, Y', strtotime($bk['check_out_date'])) ?>
                        </td>
                        <td class="p-4 font-mono text-sm text-white">₹<?= number_format($bk['grand_total']) ?></td>
                        <td class="p-4">
                            <span class="text-xs uppercase font-bold <?= $bk['status'] === 'checked_out' ? 'text-slate-500' : 'text-green-400' ?>">
                                <?= str_replace('_', ' ', $bk['status']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (empty($history)): ?>
                <div class="p-8 text-center text-slate-500">No previous stays recorded.</div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Content: Documents -->
    <div id="content-documents" class="hidden">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- ID Card Section -->
            <div class="glass-card p-6">
                <h3 class="text-lg font-semibold text-white mb-4">Identity Document</h3>
                
                <?php if ($guest['id_photo_path']): ?>
                    <div class="relative group bg-slate-900 rounded-lg overflow-hidden border border-slate-700 mb-4 h-64 flex items-center justify-center">
                        <img src="<?= $guest['id_photo_path'] ?>" alt="Guest ID" class="max-w-full max-h-full object-contain">
                    </div>
                <?php else: ?>
                    <div class="bg-slate-800/50 border-2 border-dashed border-slate-700 rounded-lg h-64 flex flex-col items-center justify-center text-slate-500 mb-4">
                        <i data-lucide="image" class="w-8 h-8 mb-2 opacity-50"></i>
                        <p class="text-sm">No ID uploaded</p>
                    </div>
                <?php endif; ?>
                
                <form action="/api/guests/upload-id" method="POST" enctype="multipart/form-data" class="flex gap-2">
                    <input type="hidden" name="guest_id" value="<?= $id ?>">
                    <input type="file" name="id_photo" class="text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-slate-700 file:text-white hover:file:bg-slate-600">
                    <button type="submit" class="btn btn--primary btn--sm">Upload</button>
                </form>
            </div>
            
            <div class="glass-card p-6 flex flex-col justify-center text-slate-400 text-sm">
                 <p class="mb-2"><strong>ID Type:</strong> <?= $guest['id_type'] ?? 'Not set' ?></p>
                 <p><strong>ID Number:</strong> <span class="font-mono bg-slate-800 px-2 py-1 rounded"><?= $guest['id_number'] ?? 'N/A' ?></span></p>
            </div>
        </div>
    </div>
</div>

<script>
    function switchTab(tabName) {
        // Hide all
        ['overview', 'history', 'documents'].forEach(t => {
            document.getElementById(`content-${t}`).classList.add('hidden');
            const btn = document.getElementById(`tab-${t}`);
            btn.classList.remove('border-cyan-500', 'text-cyan-400');
            btn.classList.add('border-transparent', 'text-slate-400');
        });
        
        // Show active
        document.getElementById(`content-${tabName}`).classList.remove('hidden');
        const activeBtn = document.getElementById(`tab-${tabName}`);
        activeBtn.classList.remove('border-transparent', 'text-slate-400');
        activeBtn.classList.add('border-cyan-500', 'text-cyan-400');
    }
</script>
