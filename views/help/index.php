<?php
/**
 * HotelOS - Help & Quick Start Guide
 */
?>

<div class="help-page animate-fadeIn">
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-white flex items-center gap-2">
            <i data-lucide="help-circle" class="w-6 h-6 text-cyan-400"></i>
            Help & Quick Start
        </h1>
        <p class="text-slate-400 text-sm mt-1">Learn how to use HotelOS effectively</p>
    </div>

    <!-- Quick Start Guide -->
    <div class="glass-card p-6 mb-6">
        <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-2">
            <i data-lucide="rocket" class="w-5 h-5 text-cyan-400"></i>
            Quick Start Guide
        </h2>
        
        <div class="space-y-4">
            <!-- Step 1 -->
            <div class="flex gap-4">
                <div class="flex-shrink-0 w-8 h-8 bg-cyan-500/20 text-cyan-400 rounded-full flex items-center justify-center font-semibold">1</div>
                <div>
                    <h3 class="text-white font-medium">Setup Room Types & Rooms</h3>
                    <p class="text-slate-400 text-sm">Go to <strong>Admin → Room Types</strong> to create categories (Deluxe, Standard, etc.) and then add individual rooms.</p>
                </div>
            </div>
            
            <!-- Step 2 -->
            <div class="flex gap-4">
                <div class="flex-shrink-0 w-8 h-8 bg-cyan-500/20 text-cyan-400 rounded-full flex items-center justify-center font-semibold">2</div>
                <div>
                    <h3 class="text-white font-medium">Add Staff Members</h3>
                    <p class="text-slate-400 text-sm">Go to <strong>Admin → Staff</strong> to add reception, housekeeping staff with their roles and PIN codes.</p>
                </div>
            </div>
            
            <!-- Step 3 -->
            <div class="flex gap-4">
                <div class="flex-shrink-0 w-8 h-8 bg-cyan-500/20 text-cyan-400 rounded-full flex items-center justify-center font-semibold">3</div>
                <div>
                    <h3 class="text-white font-medium">Start Your Shift</h3>
                    <p class="text-slate-400 text-sm">Click <strong>Start Shift</strong> on the dashboard. Enter opening cash balance to begin work.</p>
                </div>
            </div>
            
            <!-- Step 4 -->
            <div class="flex gap-4">
                <div class="flex-shrink-0 w-8 h-8 bg-cyan-500/20 text-cyan-400 rounded-full flex items-center justify-center font-semibold">4</div>
                <div>
                    <h3 class="text-white font-medium">Create Bookings</h3>
                    <p class="text-slate-400 text-sm">Use <strong>New Booking</strong> to add guest details, select room, dates, and record advance payment.</p>
                </div>
            </div>
            
            <!-- Step 5 -->
            <div class="flex gap-4">
                <div class="flex-shrink-0 w-8 h-8 bg-cyan-500/20 text-cyan-400 rounded-full flex items-center justify-center font-semibold">5</div>
                <div>
                    <h3 class="text-white font-medium">Check-in & Check-out</h3>
                    <p class="text-slate-400 text-sm">On the dashboard, click room cards to check-in guests. At checkout, GST invoice is auto-generated.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Role Guides -->
    <div class="grid md:grid-cols-2 gap-6 mb-6">
        <!-- Owner Guide -->
        <div class="glass-card p-5">
            <h3 class="text-lg font-semibold text-white mb-3 flex items-center gap-2">
                <span class="text-2xl">👑</span> Owner Dashboard
            </h3>
            <ul class="space-y-2 text-slate-300 text-sm">
                <li class="flex items-start gap-2">
                    <i data-lucide="check" class="w-4 h-4 text-emerald-400 mt-0.5"></i>
                    View daily revenue & occupancy stats
                </li>
                <li class="flex items-start gap-2">
                    <i data-lucide="check" class="w-4 h-4 text-emerald-400 mt-0.5"></i>
                    Monitor staff shifts & cash handling
                </li>
                <li class="flex items-start gap-2">
                    <i data-lucide="check" class="w-4 h-4 text-emerald-400 mt-0.5"></i>
                    Access Hotel Engine for overrides
                </li>
                <li class="flex items-start gap-2">
                    <i data-lucide="check" class="w-4 h-4 text-emerald-400 mt-0.5"></i>
                    View & export GST reports
                </li>
            </ul>
        </div>
        
        <!-- Staff Guide -->
        <div class="glass-card p-5">
            <h3 class="text-lg font-semibold text-white mb-3 flex items-center gap-2">
                <span class="text-2xl">👤</span> Staff Operations
            </h3>
            <ul class="space-y-2 text-slate-300 text-sm">
                <li class="flex items-start gap-2">
                    <i data-lucide="check" class="w-4 h-4 text-emerald-400 mt-0.5"></i>
                    Start shift before any work
                </li>
                <li class="flex items-start gap-2">
                    <i data-lucide="check" class="w-4 h-4 text-emerald-400 mt-0.5"></i>
                    Process check-ins & check-outs
                </li>
                <li class="flex items-start gap-2">
                    <i data-lucide="check" class="w-4 h-4 text-emerald-400 mt-0.5"></i>
                    Record all payments in system
                </li>
                <li class="flex items-start gap-2">
                    <i data-lucide="check" class="w-4 h-4 text-emerald-400 mt-0.5"></i>
                    End shift with cash count
                </li>
            </ul>
        </div>
    </div>

    <!-- FAQs -->
    <div class="glass-card p-6">
        <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-2">
            <i data-lucide="message-circle-question" class="w-5 h-5 text-cyan-400"></i>
            Frequently Asked Questions
        </h2>
        
        <div class="space-y-4" x-data="{open: null}">
            <!-- FAQ 1 -->
            <div class="border border-slate-700 rounded-lg overflow-hidden">
                <button @click="open = open === 1 ? null : 1" class="w-full flex justify-between items-center p-4 text-left hover:bg-slate-700/30 transition-colors">
                    <span class="text-white font-medium">How do I reset my password?</span>
                    <i data-lucide="chevron-down" class="w-5 h-5 text-slate-400 transition-transform" :class="{'rotate-180': open === 1}"></i>
                </button>
                <div x-show="open === 1" x-collapse class="px-4 pb-4 text-slate-400 text-sm">
                    Go to the login page and click "Forgot Password". Enter your email to receive a reset link.
                </div>
            </div>
            
            <!-- FAQ 2 -->
            <div class="border border-slate-700 rounded-lg overflow-hidden">
                <button @click="open = open === 2 ? null : 2" class="w-full flex justify-between items-center p-4 text-left hover:bg-slate-700/30 transition-colors">
                    <span class="text-white font-medium">How is GST calculated?</span>
                    <i data-lucide="chevron-down" class="w-5 h-5 text-slate-400 transition-transform" :class="{'rotate-180': open === 2}"></i>
                </button>
                <div x-show="open === 2" x-collapse class="px-4 pb-4 text-slate-400 text-sm">
                    GST is auto-calculated based on room rate: 12% for rooms ₹7,500 and below, 18% for above ₹7,500. Split as CGST + SGST.
                </div>
            </div>
            
            <!-- FAQ 3 -->
            <div class="border border-slate-700 rounded-lg overflow-hidden">
                <button @click="open = open === 3 ? null : 3" class="w-full flex justify-between items-center p-4 text-left hover:bg-slate-700/30 transition-colors">
                    <span class="text-white font-medium">Can I modify a booking after check-in?</span>
                    <i data-lucide="chevron-down" class="w-5 h-5 text-slate-400 transition-transform" :class="{'rotate-180': open === 3}"></i>
                </button>
                <div x-show="open === 3" x-collapse class="px-4 pb-4 text-slate-400 text-sm">
                    Yes, you can extend stay dates, add extra charges, or move guest to a different room. All changes are logged.
                </div>
            </div>
            
            <!-- FAQ 4 -->
            <div class="border border-slate-700 rounded-lg overflow-hidden">
                <button @click="open = open === 4 ? null : 4" class="w-full flex justify-between items-center p-4 text-left hover:bg-slate-700/30 transition-colors">
                    <span class="text-white font-medium">How do refunds work?</span>
                    <i data-lucide="chevron-down" class="w-5 h-5 text-slate-400 transition-transform" :class="{'rotate-180': open === 4}"></i>
                </button>
                <div x-show="open === 4" x-collapse class="px-4 pb-4 text-slate-400 text-sm">
                    Staff can request a refund, but it requires Manager or Owner approval (2-person authorization) for security.
                </div>
            </div>
            
            <!-- FAQ 5 -->
            <div class="border border-slate-700 rounded-lg overflow-hidden">
                <button @click="open = open === 5 ? null : 5" class="w-full flex justify-between items-center p-4 text-left hover:bg-slate-700/30 transition-colors">
                    <span class="text-white font-medium">Is my data backed up?</span>
                    <i data-lucide="chevron-down" class="w-5 h-5 text-slate-400 transition-transform" :class="{'rotate-180': open === 5}"></i>
                </button>
                <div x-show="open === 5" x-collapse class="px-4 pb-4 text-slate-400 text-sm">
                    Yes, your database is automatically backed up daily. Contact support for data recovery if needed.
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Support -->
    <div class="mt-6 text-center text-slate-400 text-sm">
        <p>Need more help? Contact us at <a href="mailto:support@hotelos.in" class="text-cyan-400 hover:underline">support@hotelos.in</a></p>
    </div>
</div>
