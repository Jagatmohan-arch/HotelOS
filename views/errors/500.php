<?php
/**
 * HotelOS - 500 Error Page
 */
?>
<div class="min-h-screen flex items-center justify-center p-4">
    <div class="glass-card p-8 max-w-md w-full text-center animate-fadeIn">
        <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-red-500/20 flex items-center justify-center">
            <i data-lucide="alert-triangle" class="w-10 h-10 text-red-400"></i>
        </div>
        <h1 class="text-xl font-semibold text-white mb-2">Something Went Wrong</h1>
        <p class="text-slate-400 mb-6">
            We're experiencing technical difficulties. Please try again later.
        </p>
        <div class="flex gap-4 justify-center">
            <button onclick="location.reload()" class="btn btn--secondary">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                Try Again
            </button>
            <a href="/" class="btn btn--primary">
                <i data-lucide="home" class="w-4 h-4"></i>
                Home
            </a>
        </div>
    </div>
</div>
