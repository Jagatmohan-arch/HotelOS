<?php
/**
 * HotelOS - Setup Engine View
 * Owner-Only Hotel Configuration
 */

$hotelSetup = $hotelSetup ?? [];
$statesList = \HotelOS\Handlers\SettingsHandler::getStatesList();
?>

<div class="engine-page animate-fadeIn" x-data="setupEngine()">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
        <a href="/engine" class="text-slate-400 hover:text-white">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
        </a>
        <div class="w-10 h-10 rounded-lg bg-cyan-500/20 flex items-center justify-center">
            <i data-lucide="building-2" class="w-5 h-5 text-cyan-400"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-white">Hotel Setup Engine</h1>
            <p class="text-slate-400 text-sm">Hotel Profile, GST, Timezone Settings</p>
        </div>
    </div>
    
    <!-- Form -->
    <form @submit.prevent="saveSetup()" class="space-y-6">
        <!-- Hotel Info -->
        <div class="glass-card p-5">
            <h3 class="text-white font-semibold mb-4 flex items-center gap-2">
                <i data-lucide="info" class="w-5 h-5 text-cyan-400"></i>
                Hotel Information
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Hotel Name *</label>
                    <input type="text" x-model="form.name" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Legal Name (for GST)</label>
                    <input type="text" x-model="form.legal_name" class="form-input">
                </div>
                <div>
                    <label class="form-label">Email</label>
                    <input type="email" x-model="form.email" class="form-input">
                </div>
                <div>
                    <label class="form-label">Phone</label>
                    <input type="tel" x-model="form.phone" class="form-input">
                </div>
                <div>
                    <label class="form-label">Alt Phone</label>
                    <input type="tel" x-model="form.alt_phone" class="form-input">
                </div>
                <div>
                    <label class="form-label">Website</label>
                    <input type="url" x-model="form.website" class="form-input">
                </div>
            </div>
        </div>
        
        <!-- Address -->
        <div class="glass-card p-5">
            <h3 class="text-white font-semibold mb-4 flex items-center gap-2">
                <i data-lucide="map-pin" class="w-5 h-5 text-emerald-400"></i>
                Address
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="form-label">Address Line 1</label>
                    <input type="text" x-model="form.address_line1" class="form-input">
                </div>
                <div class="md:col-span-2">
                    <label class="form-label">Address Line 2</label>
                    <input type="text" x-model="form.address_line2" class="form-input">
                </div>
                <div>
                    <label class="form-label">City</label>
                    <input type="text" x-model="form.city" class="form-input">
                </div>
                <div>
                    <label class="form-label">State</label>
                    <select x-model="form.state" class="form-input">
                        <?php foreach ($statesList as $code => $name): ?>
                        <option value="<?= htmlspecialchars($name) ?>"><?= htmlspecialchars($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Pincode</label>
                    <input type="text" x-model="form.pincode" class="form-input" maxlength="6">
                </div>
            </div>
        </div>
        
        <!-- Tax Settings -->
        <div class="glass-card p-5">
            <h3 class="text-white font-semibold mb-4 flex items-center gap-2">
                <i data-lucide="receipt" class="w-5 h-5 text-amber-400"></i>
                Tax & GST Settings
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="form-label">GST Number</label>
                    <input type="text" x-model="form.gst_number" class="form-input font-mono" 
                           maxlength="15" placeholder="27AAAAA0000A1Z5">
                </div>
                <div>
                    <label class="form-label">State Code</label>
                    <select x-model="form.state_code" class="form-input">
                        <?php foreach ($statesList as $code => $name): ?>
                        <option value="<?= $code ?>"><?= $code ?> - <?= htmlspecialchars($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">PAN Number</label>
                    <input type="text" x-model="form.pan_number" class="form-input font-mono" 
                           maxlength="10" placeholder="AAAAA0000A">
                </div>
            </div>
        </div>
        
        <!-- Timings -->
        <div class="glass-card p-5">
            <h3 class="text-white font-semibold mb-4 flex items-center gap-2">
                <i data-lucide="clock" class="w-5 h-5 text-purple-400"></i>
                Check-in / Check-out Times
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="form-label">Check-in Time</label>
                    <input type="time" x-model="form.check_in_time" class="form-input">
                </div>
                <div>
                    <label class="form-label">Check-out Time</label>
                    <input type="time" x-model="form.check_out_time" class="form-input">
                </div>
                <div>
                    <label class="form-label">Timezone</label>
                    <select x-model="form.timezone" class="form-input">
                        <option value="Asia/Kolkata">Asia/Kolkata (IST)</option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Reason -->
        <div class="glass-card p-5 border-amber-500/30">
            <h3 class="text-amber-300 font-semibold mb-4 flex items-center gap-2">
                <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                Change Reason (Required)
            </h3>
            <textarea x-model="reason" class="form-input" rows="2" 
                      placeholder="Why are you making these changes?" required></textarea>
        </div>
        
        <!-- Submit -->
        <div class="flex gap-4">
            <button type="submit" class="btn btn--primary" :disabled="saving">
                <i data-lucide="save" class="w-4 h-4"></i>
                <span x-text="saving ? 'Saving...' : 'Save Changes'"></span>
            </button>
            <a href="/engine" class="btn btn--secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
function setupEngine() {
    return {
        saving: false,
        reason: '',
        form: {
            name: <?= json_encode($hotelSetup['name'] ?? '') ?>,
            legal_name: <?= json_encode($hotelSetup['legal_name'] ?? '') ?>,
            email: <?= json_encode($hotelSetup['email'] ?? '') ?>,
            phone: <?= json_encode($hotelSetup['phone'] ?? '') ?>,
            alt_phone: <?= json_encode($hotelSetup['alt_phone'] ?? '') ?>,
            website: <?= json_encode($hotelSetup['website'] ?? '') ?>,
            address_line1: <?= json_encode($hotelSetup['address_line1'] ?? '') ?>,
            address_line2: <?= json_encode($hotelSetup['address_line2'] ?? '') ?>,
            city: <?= json_encode($hotelSetup['city'] ?? '') ?>,
            state: <?= json_encode($hotelSetup['state'] ?? '') ?>,
            pincode: <?= json_encode($hotelSetup['pincode'] ?? '') ?>,
            gst_number: <?= json_encode($hotelSetup['gst_number'] ?? '') ?>,
            state_code: <?= json_encode($hotelSetup['state_code'] ?? '27') ?>,
            pan_number: <?= json_encode($hotelSetup['pan_number'] ?? '') ?>,
            check_in_time: <?= json_encode($hotelSetup['check_in_time'] ?? '14:00') ?>,
            check_out_time: <?= json_encode($hotelSetup['check_out_time'] ?? '11:00') ?>,
            timezone: <?= json_encode($hotelSetup['timezone'] ?? 'Asia/Kolkata') ?>
        },
        
        async saveSetup() {
            if (!this.reason.trim()) {
                alert('Please enter a reason for this change');
                return;
            }
            
            this.saving = true;
            try {
                const payload = { ...this.form, reason: this.reason };
                const res = await fetch('/api/engine/setup', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                
                if (data.success) {
                    alert('✅ Hotel settings updated successfully!');
                    location.href = '/engine';
                } else {
                    alert('Error: ' + (data.error || 'Save failed'));
                }
            } catch (e) {
                alert('Network error');
            }
            this.saving = false;
        }
    };
}
</script>
