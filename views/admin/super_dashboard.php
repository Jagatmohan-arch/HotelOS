<?php
// views/admin/super_dashboard.php
// Enterprise HQ Dashboard
// Shows aggregate data across all properties in the chain

$summary = $chainStats['summary'] ?? [];
$properties = $chainStats['tenants'] ?? [];
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-end">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Enterprise Dashboard</h1>
            <p class="text-gray-500">Chain Overview &middot; Real-time Aggregation</p>
        </div>
        <div class="flex gap-2">
            <!-- Add Property: Hidden for v5.0 (Manual Onboarding Only) -->
            <!-- 
            <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg flex items-center gap-2" onclick="alert('Coming Soon: Add Property Wizard')">
                <i data-lucide="plus-circle" class="w-4 h-4"></i> Add Property
            </button>
            -->
        </div>
    </div>

    <!-- Aggregate KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Total Revenue -->
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm relative overflow-hidden group">
            <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <i data-lucide="dollar-sign" class="w-16 h-16 text-emerald-600"></i>
            </div>
            <p class="text-sm font-medium text-gray-500">Total Group Revenue (Today)</p>
            <h3 class="text-2xl font-bold text-gray-900 mt-1">₹<?= number_format($summary['total_revenue_today'] ?? 0) ?></h3>
            <div class="mt-2 text-xs text-emerald-600 flex items-center">
                <i data-lucide="trending-up" class="w-3 h-3 mr-1"></i> Live
            </div>
        </div>

        <!-- Occupancy -->
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm relative overflow-hidden group">
             <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <i data-lucide="users" class="w-16 h-16 text-blue-600"></i>
            </div>
            <p class="text-sm font-medium text-gray-500">Group Occupancy</p>
            <h3 class="text-2xl font-bold text-gray-900 mt-1"><?= $summary['current_occupied_rooms'] ?? 0 ?> <span class="text-sm text-gray-400 font-normal">Active Rooms</span></h3>
        </div>

        <!-- New Bookings -->
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm relative overflow-hidden group">
             <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <i data-lucide="calendar" class="w-16 h-16 text-purple-600"></i>
            </div>
            <p class="text-sm font-medium text-gray-500">New Bookings (Today)</p>
            <h3 class="text-2xl font-bold text-gray-900 mt-1"><?= $summary['new_bookings_today'] ?? 0 ?></h3>
        </div>

        <!-- Properties -->
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm relative overflow-hidden group">
             <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <i data-lucide="building" class="w-16 h-16 text-gray-600"></i>
            </div>
            <p class="text-sm font-medium text-gray-500">Total Properties</p>
            <h3 class="text-2xl font-bold text-gray-900 mt-1"><?= $summary['total_properties'] ?? 0 ?></h3>
        </div>
    </div>

    <!-- Property List Table -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-bold text-gray-800">Properties Portfolio</h3>
            <div class="relative">
                <input type="text" id="propertySearch" placeholder="Search property..." class="pl-9 pr-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-500" onkeyup="filterProperties()">
                <i data-lucide="search" class="w-4 h-4 text-gray-400 absolute left-3 top-2"></i>
            </div>
        </div>
        
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500 font-medium border-b border-gray-100">
                <tr>
                    <th class="px-6 py-3">Property Name</th>
                    <th class="px-6 py-3">Location</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody id="propertyTableBody" class="divide-y divide-gray-100">
                <?php foreach($properties as $prop): ?>
                <tr class="hover:bg-gray-50 transition-colors group">
                    <td class="px-6 py-4 font-medium text-gray-900">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold">
                                <?= substr($prop['name'], 0, 1) ?>
                            </div>
                            <?= htmlspecialchars($prop['name']) ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-gray-500"><?= htmlspecialchars($prop['city']) ?></td>
                    <td class="px-6 py-4">
                        <?php if($prop['status'] === 'active'): ?>
                            <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs">Active</span>
                        <?php else: ?>
                            <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="/super-admin/switch/<?= $prop['id'] ?>" class="text-indigo-600 hover:text-indigo-800 font-medium text-xs border border-indigo-200 hover:bg-indigo-50 px-3 py-1.5 rounded transition-all">
                            Manage Dashboard &rarr;
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function filterProperties() {
    const input = document.getElementById('propertySearch');
    const filter = input.value.toLowerCase();
    const tbody = document.getElementById('propertyTableBody');
    const rows = tbody.getElementsByTagName('tr');
    
    for (let i = 0; i < rows.length; i++) {
        const nameCell = rows[i].getElementsByTagName('td')[0];
        const locationCell = rows[i].getElementsByTagName('td')[1];
        if (nameCell && locationCell) {
            const name = nameCell.textContent || nameCell.innerText;
            const location = locationCell.textContent || locationCell.innerText;
            if (name.toLowerCase().indexOf(filter) > -1 || location.toLowerCase().indexOf(filter) > -1) {
                rows[i].style.display = '';
            } else {
                rows[i].style.display = 'none';
            }
        }
    }
}
</script>
