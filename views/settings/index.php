<?php
/**
 * HotelOS - Settings Page
 * Tabbed interface for hotel configuration
 */



$profile = $profile ?? [];
$states = $states ?? [];
$activeTab = $activeTab ?? 'profile';
$success = $success ?? null;
$error = $error ?? null;
?>

<div class="settings-page animate-fadeIn">
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white">Settings</h1>
            <p class="text-slate-400 text-sm mt-1">Configure your hotel profile and preferences</p>
        </div>
    </div>
    
    <!-- Messages -->
    <?php if ($success): ?>
    <div class="mb-4 p-4 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm">
        <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="mb-4 p-4 rounded-lg bg-red-500/10 border border-red-500/30 text-red-300 text-sm">
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>
    
    <!-- Tabs -->
    <div class="flex gap-2 mb-6 overflow-x-auto pb-2">
        <a href="/settings?tab=profile" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition-colors whitespace-nowrap <?= $activeTab === 'profile' ? 'bg-cyan-500/20 text-cyan-400 border border-cyan-500/30' : 'bg-slate-800/50 text-slate-400 hover:text-white' ?>">
            🏨 Hotel Profile
        </a>
        <a href="/settings?tab=tax" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition-colors whitespace-nowrap <?= $activeTab === 'tax' ? 'bg-cyan-500/20 text-cyan-400 border border-cyan-500/30' : 'bg-slate-800/50 text-slate-400 hover:text-white' ?>">
            📋 Tax & GST
        </a>
        <a href="/settings?tab=times" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition-colors whitespace-nowrap <?= $activeTab === 'times' ? 'bg-cyan-500/20 text-cyan-400 border border-cyan-500/30' : 'bg-slate-800/50 text-slate-400 hover:text-white' ?>">
            ⏰ Check Times
        </a>
    </div>
    
    <!-- Tab Content -->
    <div class="glass-card p-6">
        
        <?php if ($activeTab === 'profile'): ?>
        <!-- Hotel Profile Tab -->
        <form method="POST" action="/settings/profile" class="space-y-6">
            <input type="hidden" name="_csrf" value="<?= $csrfToken ?? '' ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Hotel Name -->
                <div>
                    <label class="form-label">Hotel Name *</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($profile['name'] ?? '') ?>" 
                           class="form-input" required placeholder="Your Hotel Name">
                </div>
                
                <!-- Legal Name -->
                <div>
                    <label class="form-label">Legal/Business Name</label>
                    <input type="text" name="legal_name" value="<?= htmlspecialchars($profile['legal_name'] ?? '') ?>" 
                           class="form-input" placeholder="For GST invoices">
                </div>
                
                <!-- Email -->
                <div>
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($profile['email'] ?? '') ?>" 
                           class="form-input" required placeholder="contact@hotel.com">
                </div>
                
                <!-- Phone -->
                <div>
                    <label class="form-label">Phone *</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>" 
                           class="form-input" required placeholder="+91 9876543210">
                </div>
                
                <!-- Alt Phone -->
                <div>
                    <label class="form-label">Alternate Phone</label>
                    <input type="text" name="alt_phone" value="<?= htmlspecialchars($profile['alt_phone'] ?? '') ?>" 
                           class="form-input" placeholder="Optional">
                </div>
                
                <!-- Website -->
                <div>
                    <label class="form-label">Website</label>
                    <input type="url" name="website" value="<?= htmlspecialchars($profile['website'] ?? '') ?>" 
                           class="form-input" placeholder="https://yourhotel.com">
                </div>
            </div>
            
            <hr class="border-slate-700/50">
            
            <h3 class="text-lg font-semibold text-white">Address</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Address Line 1 -->
                <div class="md:col-span-2">
                    <label class="form-label">Address Line 1 *</label>
                    <input type="text" name="address_line1" value="<?= htmlspecialchars($profile['address_line1'] ?? '') ?>" 
                           class="form-input" required placeholder="Building, Street">
                </div>
                
                <!-- Address Line 2 -->
                <div class="md:col-span-2">
                    <label class="form-label">Address Line 2</label>
                    <input type="text" name="address_line2" value="<?= htmlspecialchars($profile['address_line2'] ?? '') ?>" 
                           class="form-input" placeholder="Area, Landmark">
                </div>
                
                <!-- City -->
                <div>
                    <label class="form-label">City *</label>
                    <input type="text" name="city" value="<?= htmlspecialchars($profile['city'] ?? '') ?>" 
                           class="form-input" required placeholder="Mumbai">
                </div>
                
                <!-- State -->
                <div>
                    <label class="form-label">State *</label>
                    <input type="text" name="state" value="<?= htmlspecialchars($profile['state'] ?? '') ?>" 
                           class="form-input" required placeholder="Maharashtra">
                </div>
                
                <!-- Pincode -->
                <div>
                    <label class="form-label">Pincode *</label>
                    <input type="text" name="pincode" value="<?= htmlspecialchars($profile['pincode'] ?? '') ?>" 
                           class="form-input" required placeholder="400001" maxlength="6">
                </div>
            </div>
            
            <div class="flex justify-end pt-4">
                <button type="submit" class="btn btn--primary">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    Save Profile
                </button>
            </div>
        </form>
        
        <?php elseif ($activeTab === 'tax'): ?>
        <!-- Tax Settings Tab -->
        <form method="POST" action="/settings/tax" class="space-y-6">
            <input type="hidden" name="_csrf" value="<?= $csrfToken ?? '' ?>">
            
            <div class="p-4 rounded-lg bg-amber-500/10 border border-amber-500/30 text-amber-300 text-sm mb-6">
                <strong>Important:</strong> These details appear on your GST invoices. Ensure they match your GST registration.
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- GST Number -->
                <div>
                    <label class="form-label">GSTIN (GST Number)</label>
                    <input type="text" name="gst_number" value="<?= htmlspecialchars($profile['gst_number'] ?? '') ?>" 
                           class="form-input" placeholder="22AAAAA0000A1Z5" maxlength="15"
                           pattern="[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}">
                    <p class="text-xs text-slate-500 mt-1">15-character GST Identification Number</p>
                </div>
                
                <!-- State Code -->
                <div>
                    <label class="form-label">GST State Code</label>
                    <select name="state_code" class="form-input">
                        <?php foreach ($states as $code => $name): ?>
                        <option value="<?= $code ?>" <?= ($profile['state_code'] ?? '27') === $code ? 'selected' : '' ?>>
                            <?= $code ?> - <?= htmlspecialchars($name) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- PAN Number -->
                <div>
                    <label class="form-label">PAN Number</label>
                    <input type="text" name="pan_number" value="<?= htmlspecialchars($profile['pan_number'] ?? '') ?>" 
                           class="form-input" placeholder="AAAAA0000A" maxlength="10"
                           pattern="[A-Z]{5}[0-9]{4}[A-Z]{1}">
                    <p class="text-xs text-slate-500 mt-1">10-character PAN for tax purposes</p>
                </div>
            </div>
            
            <div class="flex justify-end pt-4">
                <button type="submit" class="btn btn--primary">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    Save Tax Settings
                </button>
            </div>
        </form>
        
        <?php elseif ($activeTab === 'times'): ?>
        <!-- Check Times Tab -->
        <form method="POST" action="/settings/times" class="space-y-6">
            <input type="hidden" name="_csrf" value="<?= $csrfToken ?? '' ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Check-in Time -->
                <div>
                    <label class="form-label">Standard Check-in Time</label>
                    <input type="time" name="check_in_time" 
                           value="<?= htmlspecialchars(substr($profile['check_in_time'] ?? '14:00', 0, 5)) ?>" 
                           class="form-input">
                    <p class="text-xs text-slate-500 mt-1">Default: 2:00 PM</p>
                </div>
                
                <!-- Check-out Time -->
                <div>
                    <label class="form-label">Standard Check-out Time</label>
                    <input type="time" name="check_out_time" 
                           value="<?= htmlspecialchars(substr($profile['check_out_time'] ?? '11:00', 0, 5)) ?>" 
                           class="form-input">
                    <p class="text-xs text-slate-500 mt-1">Default: 11:00 AM</p>
                </div>
            </div>
            
            <div class="p-4 rounded-lg bg-blue-500/10 border border-blue-500/30 text-blue-300 text-sm">
                <strong>Late Checkout Policy:</strong><br>
                • 12:00 PM - 2:00 PM: 25% of room rate<br>
                • After 2:00 PM: 50% of room rate
            </div>
            
            <div class="flex justify-end pt-4">
                <button type="submit" class="btn btn--primary">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    Save Times
                </button>
            </div>
        </form>
        <?php endif; ?>
        
    </div>
</div>
