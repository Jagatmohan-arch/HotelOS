<?php
/**
 * HotelOS - Branding Engine View
 * Owner-Only Logo/Stamp/Signature Upload
 */

$branding = $branding ?? [];

// Convert to keyed array
$brandingMap = [];
foreach ($branding as $asset) {
    $brandingMap[$asset['asset_type']] = $asset;
}
?>

<div class="engine-page animate-fadeIn" x-data="brandingEngine()">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
        <a href="/engine" class="text-slate-400 hover:text-white">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
        </a>
        <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center">
            <i data-lucide="palette" class="w-5 h-5 text-purple-400"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-white">Branding Engine</h1>
            <p class="text-slate-400 text-sm">Logo, Stamp & Signature for Invoices</p>
        </div>
    </div>
    
    <!-- Upload Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Logo -->
        <div class="glass-card p-5">
            <h3 class="text-white font-semibold mb-4 flex items-center gap-2">
                <i data-lucide="image" class="w-5 h-5 text-cyan-400"></i>
                Hotel Logo
            </h3>
            
            <?php if (isset($brandingMap['logo'])): ?>
            <div class="mb-4">
                <img src="<?= htmlspecialchars($brandingMap['logo']['file_path']) ?>" 
                     alt="Logo" 
                     class="max-w-full h-24 object-contain mx-auto bg-white/10 rounded p-2">
            </div>
            <?php else: ?>
            <div class="h-24 bg-slate-700/50 rounded flex items-center justify-center mb-4">
                <span class="text-slate-500">No logo uploaded</span>
            </div>
            <?php endif; ?>
            
            <form @submit.prevent="uploadAsset('logo', $event)" enctype="multipart/form-data">
                <input type="file" name="file" accept="image/png,image/jpeg" class="form-input text-sm mb-2" required>
                <button type="submit" class="btn btn--primary w-full" :disabled="uploading">
                    <span x-text="uploading ? 'Uploading...' : 'Upload Logo'"></span>
                </button>
            </form>
        </div>
        
        <!-- Stamp -->
        <div class="glass-card p-5">
            <h3 class="text-white font-semibold mb-4 flex items-center gap-2">
                <i data-lucide="stamp" class="w-5 h-5 text-amber-400"></i>
                Official Stamp
            </h3>
            
            <?php if (isset($brandingMap['stamp'])): ?>
            <div class="mb-4">
                <img src="<?= htmlspecialchars($brandingMap['stamp']['file_path']) ?>" 
                     alt="Stamp" 
                     class="max-w-full h-24 object-contain mx-auto bg-white/10 rounded p-2">
            </div>
            <?php else: ?>
            <div class="h-24 bg-slate-700/50 rounded flex items-center justify-center mb-4">
                <span class="text-slate-500">No stamp uploaded</span>
            </div>
            <?php endif; ?>
            
            <form @submit.prevent="uploadAsset('stamp', $event)" enctype="multipart/form-data">
                <input type="file" name="file" accept="image/png,image/jpeg" class="form-input text-sm mb-2" required>
                <button type="submit" class="btn btn--primary w-full" :disabled="uploading">
                    <span x-text="uploading ? 'Uploading...' : 'Upload Stamp'"></span>
                </button>
            </form>
        </div>
        
        <!-- Signature -->
        <div class="glass-card p-5">
            <h3 class="text-white font-semibold mb-4 flex items-center gap-2">
                <i data-lucide="pen-tool" class="w-5 h-5 text-emerald-400"></i>
                Owner Signature
            </h3>
            
            <?php if (isset($brandingMap['signature'])): ?>
            <div class="mb-4">
                <img src="<?= htmlspecialchars($brandingMap['signature']['file_path']) ?>" 
                     alt="Signature" 
                     class="max-w-full h-24 object-contain mx-auto bg-white/10 rounded p-2">
            </div>
            <?php else: ?>
            <div class="h-24 bg-slate-700/50 rounded flex items-center justify-center mb-4">
                <span class="text-slate-500">No signature uploaded</span>
            </div>
            <?php endif; ?>
            
            <form @submit.prevent="uploadAsset('signature', $event)" enctype="multipart/form-data">
                <input type="file" name="file" accept="image/png,image/jpeg" class="form-input text-sm mb-2" required>
                <button type="submit" class="btn btn--primary w-full" :disabled="uploading">
                    <span x-text="uploading ? 'Uploading...' : 'Upload Signature'"></span>
                </button>
            </form>
        </div>
    </div>
    
    <!-- Info -->
    <div class="mt-6 bg-slate-700/30 rounded-lg p-4">
        <h4 class="text-white font-medium mb-2">📄 Invoice Preview</h4>
        <p class="text-slate-400 text-sm">
            Uploaded assets will automatically appear on all generated invoices:
        </p>
        <ul class="text-slate-400 text-sm mt-2 list-disc list-inside">
            <li><strong>Logo</strong> → Top-left corner of invoice</li>
            <li><strong>Stamp</strong> → Bottom-right of invoice</li>
            <li><strong>Signature</strong> → Above the stamp</li>
        </ul>
    </div>
</div>

<script>
function brandingEngine() {
    return {
        uploading: false,
        
        async uploadAsset(type, event) {
            const form = event.target;
            const fileInput = form.querySelector('input[type="file"]');
            
            if (!fileInput.files[0]) {
                alert('Please select a file');
                return;
            }
            
            const formData = new FormData();
            formData.append('file', fileInput.files[0]);
            formData.append('asset_type', type);
            formData.append('reason', 'Branding updated via Engine');
            
            this.uploading = true;
            try {
                const res = await fetch('/api/engine/branding/upload', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                
                if (data.success) {
                    alert('✅ ' + type.charAt(0).toUpperCase() + type.slice(1) + ' uploaded successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Upload failed'));
                }
            } catch (e) {
                alert('Network error');
            }
            this.uploading = false;
        }
    };
}
</script>
