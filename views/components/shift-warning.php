<?php
/**
 * HotelOS - Shift Warning Component
 * 
 * Displays a warning banner when no shift is active
 * UX-1: Shift System Clarity
 */

// Get current shift status
$auth = \HotelOS\Core\Auth::getInstance();
$shiftHandler = new \HotelOS\Handlers\ShiftHandler();
$currentShift = $shiftHandler->getCurrentShift();
$hasActiveShift = $currentShift && $currentShift['status'] === 'OPEN';
?>

<?php if (!$hasActiveShift): ?>
<!-- Shift Warning Banner -->
<div class="bg-amber-900/40 border border-amber-500/50 rounded-xl p-4 mb-6 flex items-center gap-4">
    <div class="w-12 h-12 rounded-full bg-amber-500/20 flex items-center justify-center flex-shrink-0">
        <i data-lucide="alert-triangle" class="w-6 h-6 text-amber-400"></i>
    </div>
    <div class="flex-1">
        <h3 class="font-semibold text-amber-200">⚠️ Shift Not Started</h3>
        <p class="text-amber-300/80 text-sm mt-1">
            Start your shift to accept payments. HotelOS tracks cash responsibility per shift.
        </p>
    </div>
    <a href="/shifts" class="btn btn--primary whitespace-nowrap">
        <i data-lucide="play" class="w-4 h-4"></i>
        Start Shift
    </a>
</div>
<?php endif; ?>
