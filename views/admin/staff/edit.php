<?php
// views/admin/staff/edit.php

use HotelOS\Handlers\UserHandler;

$id = (int)($_GET['id'] ?? 0);
$handler = new UserHandler();
$user = $handler->getById($id);

if (!$user) {
    echo "User not found.";
    exit;
}

$error = $_GET['error'] ?? null;
?>

<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center space-x-2 text-sm text-gray-500">
        <a href="/admin/staff" class="hover:text-gray-900">Staff</a>
        <i data-lucide="chevron-right" class="w-4 h-4"></i>
        <span class="text-gray-900">Edit User</span>
    </div>

    <div>
        <h1 class="text-2xl font-bold text-gray-900">Edit Staff Member</h1>
        <p class="text-gray-500 text-sm mt-1">Update details for <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></p>
    </div>

    <?php if ($error): ?>
        <div class="rounded-md bg-red-50 p-4 border border-red-200">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i data-lucide="alert-circle" class="h-5 w-5 text-red-400"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Error updating user</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <p><?= htmlspecialchars($error) ?></p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <form action="/admin/staff/edit" method="POST" class="bg-white shadow rounded-lg border border-gray-200 overflow-hidden">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="id" value="<?= $user['id'] ?>">
        
        <div class="p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">First Name</label>
                    <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Last Name</label>
                    <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Email Address (Read Only)</label>
                <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Role</label>
                <select name="role" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="reception" <?= $user['role'] === 'reception' ? 'selected' : '' ?>>Reception (Front Desk)</option>
                    <option value="manager" <?= $user['role'] === 'manager' ? 'selected' : '' ?>>Manager (Admin Access)</option>
                    <option value="housekeeping" <?= $user['role'] === 'housekeeping' ? 'selected' : '' ?>>Housekeeping</option>
                    <option value="accountant" <?= $user['role'] === 'accountant' ? 'selected' : '' ?>>Accountant</option>
                </select>
            </div>

            <div class="pt-4 border-t border-gray-200">
                <h3 class="text-sm font-medium text-gray-900 mb-4">Security</h3>
                
                <div class="flex items-center mb-4">
                    <input type="checkbox" name="is_active" id="is_active" <?= $user['is_active'] ? 'checked' : '' ?> value="1" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                    <label for="is_active" class="ml-2 block text-sm text-gray-900">Account Active</label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Reset Password (Optional)</label>
                    <input type="password" name="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Leave blank to keep current password" minlength="8">
                </div>
            </div>
        </div>

        <div class="bg-gray-50 px-4 py-3 sm:px-6 flex flex-row-reverse">
            <button type="submit" class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 ml-3">
                Update User
            </button>
            <a href="/admin/staff" class="inline-flex justify-center rounded-md border border-gray-300 bg-white py-2 px-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                Cancel
            </a>
        </div>
    </form>
</div>
