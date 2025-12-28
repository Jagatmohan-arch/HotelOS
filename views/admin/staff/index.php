<?php
// views/admin/staff/index.php

use HotelOS\Handlers\UserHandler;

$handler = new UserHandler();
$users = $handler->getAllUsers();

$roleBadges = [
    'owner' => 'bg-purple-100 text-purple-800',
    'manager' => 'bg-indigo-100 text-indigo-800',
    'reception' => 'bg-green-100 text-green-800',
    'housekeeping' => 'bg-blue-100 text-blue-800',
    'accountant' => 'bg-amber-100 text-amber-800'
];

$success = $_GET['success'] ?? null;
?>

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Staff Management</h1>
            <p class="text-gray-500 text-sm mt-1">Manage user access and roles.</p>
        </div>
        <a href="/admin/staff/create" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
            Add New Staff
        </a>
    </div>

    <?php if ($success === 'created'): ?>
        <div class="rounded-md bg-green-50 p-4 border border-green-200">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i data-lucide="check-circle" class="h-5 w-5 text-green-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">New staff member added successfully.</p>
                </div>
            </div>
        </div>
    <?php elseif ($success === 'updated'): ?>
        <div class="rounded-md bg-green-50 p-4 border border-green-200">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i data-lucide="check-circle" class="h-5 w-5 text-green-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">Staff details updated successfully.</p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name & Email</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($users as $staff): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 font-bold">
                                        <?= strtoupper(substr($staff['first_name'], 0, 1) . substr($staff['last_name'], 0, 1)) ?>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']) ?></div>
                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($staff['email']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $roleBadges[$staff['role']] ?? 'bg-gray-100 text-gray-800' ?>">
                                <?= ucfirst($staff['role']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($staff['is_active']): ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Active
                                </span>
                            <?php else: ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    Inactive
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= htmlspecialchars($staff['phone'] ?? '-') ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="/admin/staff/edit?id=<?= $staff['id'] ?>" class="text-indigo-600 hover:text-indigo-900">
                                <i data-lucide="edit-2" class="w-4 h-4 inline"></i> Edit
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
