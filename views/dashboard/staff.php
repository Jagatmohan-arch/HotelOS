<?php
/**
 * HotelOS - Staff Dashboard
 * "The Executor" view - Tasks, Shift, Attendance
 * Zero Financial Data. Zero Booking Control.
 */
?>

<div class="max-w-md mx-auto">
    <!-- Shift Status Card -->
    <div class="bg-slate-800 rounded-xl p-6 border border-slate-700 shadow-lg text-center mb-6">
        <?php
            // Mock Shift Logic - Real implementation checks DB
            // In Phase 0 we added shift warning component which handles the "not started" CLI
            // Here we just provide the action container
        ?>
        <div class="mb-4">
            <div class="w-16 h-16 bg-slate-700 rounded-full flex items-center justify-center mx-auto mb-3">
                <i data-lucide="clock" class="w-8 h-8 text-cyan-400"></i>
            </div>
            <h2 class="text-xl font-bold text-white">My Shift</h2>
            <p class="text-slate-400 text-sm">Track your work hours</p>
        </div>
        
        <a href="/shifts" class="block w-full py-3 bg-cyan-500 hover:bg-cyan-600 text-white font-bold rounded-lg transition-colors">
            Manage Shift
        </a>
    </div>

    <!-- Quick Tasks -->
    <div class="grid grid-cols-2 gap-4">
        <a href="/housekeeping" class="bg-slate-800 p-5 rounded-xl border border-slate-700 flex flex-col items-center justify-center gap-3 hover:bg-slate-750 transition-colors group text-decoration-none">
            <div class="p-3 bg-emerald-500/10 rounded-full group-hover:scale-110 transition-transform">
                <i data-lucide="spray-can" class="w-6 h-6 text-emerald-400"></i>
            </div>
            <div class="text-white font-medium">Housekeeping</div>
        </a>
        
        <a href="/profile" class="bg-slate-800 p-5 rounded-xl border border-slate-700 flex flex-col items-center justify-center gap-3 hover:bg-slate-750 transition-colors group text-decoration-none">
            <div class="p-3 bg-indigo-500/10 rounded-full group-hover:scale-110 transition-transform">
                <i data-lucide="user-check" class="w-6 h-6 text-indigo-400"></i>
            </div>
            <div class="text-white font-medium">My Profile</div>
        </a>
    </div>
    
    <!-- Simple Notice -->
    <div class="mt-8 text-center">
        <p class="text-xs text-slate-500">Need help? Contact your Manager.</p>
    </div>
</div>
