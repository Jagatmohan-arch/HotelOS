<?php
// views/admin/staff/create.php

$error = $_GET['error'] ?? null;
?>

<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center space-x-2 text-sm text-gray-500">
        <a href="/admin/staff" class="hover:text-gray-900">Staff</a>
        <i data-lucide="chevron-right" class="w-4 h-4"></i>
        <span class="text-gray-900">Add New</span>
    </div>

    <div>
        <h1 class="text-2xl font-bold text-gray-900">Add New Staff Member</h1>
        <p class="text-gray-500 text-sm mt-1">Create a new user account for system access.</p>
    </div>

    <?php if ($error): ?>
        <div class="rounded-md bg-red-50 p-4 border border-red-200">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i data-lucide="alert-circle" class="h-5 w-5 text-red-400"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Error creating user</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <p><?= htmlspecialchars($error) ?></p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <form action="/admin/staff/create" method="POST" class="bg-white shadow rounded-lg border border-gray-200 overflow-hidden">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        
        <div class="p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">First Name</label>
                    <input type="text" name="first_name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Last Name</label>
                    <input type="text" name="last_name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Email Address (Login ID)</label>
                <input type="email" name="email" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="staff@example.com">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                <input type="text" name="phone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="+91 9999999999">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Role</label>
                <select name="role" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="reception">Reception (Front Desk)</option>
                    <option value="manager">Manager (Admin Access)</option>
                    <option value="housekeeping">Housekeeping</option>
                    <option value="accountant">Accountant</option>
                </select>
                <p class="mt-2 text-sm text-gray-500">
                    <strong>Manager</strong> has full access. <strong>Reception</strong> can manage bookings.
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Initial Password</label>
                <input type="password" name="password" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" minlength="8">
            </div>
        </div>

        <div class="bg-gray-50 px-4 py-3 sm:px-6 flex flex-row-reverse">
            <button type="submit" class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 ml-3">
                Create User
            </button>
            <a href="/admin/staff" class="inline-flex justify-center rounded-md border border-gray-300 bg-white py-2 px-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                Cancel
            </a>
        </div>
    </form>
</div>
