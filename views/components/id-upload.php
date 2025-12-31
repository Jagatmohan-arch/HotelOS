<?php
/**
 * HotelOS - ID Upload Component
 * 
 * Reusable component for uploading guest ID photos
 * 
 * Usage: include with $guestId variable set
 */

$guestId = $guestId ?? null;
$existingPhoto = $existingPhoto ?? null;
?>

<div class="id-upload-component glass-card p-4" x-data="idUploader(<?= $guestId ?>)">
    <div class="flex items-center justify-between mb-3">
        <h4 class="text-sm font-semibold text-white flex items-center gap-2">
            <i data-lucide="credit-card" class="w-4 h-4 text-cyan-400"></i>
            ID Photo
        </h4>
        <span class="text-xs text-slate-500">Upload Aadhaar/PAN/Passport</span>
    </div>
    
    <!-- Upload Area -->
    <div class="relative">
        <!-- Preview -->
        <template x-if="photoUrl">
            <div class="relative rounded-lg overflow-hidden border border-white/10">
                <img :src="photoUrl" alt="ID Photo" class="w-full h-48 object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
                <div class="absolute bottom-2 right-2 flex gap-2">
                    <button @click="viewPhoto()" class="btn btn--sm btn--secondary" title="View Full">
                        <i data-lucide="eye" class="w-3 h-3"></i>
                    </button>
                    <button @click="deletePhoto()" class="btn btn--sm btn--danger" :disabled="isLoading" title="Delete">
                        <i data-lucide="trash-2" class="w-3 h-3"></i>
                    </button>
                </div>
            </div>
        </template>
        
        <!-- Upload Zone -->
        <template x-if="!photoUrl">
            <div 
                class="upload-zone border-2 border-dashed border-slate-600 rounded-lg p-6 text-center hover:border-cyan-400 transition-colors cursor-pointer"
                @click="$refs.fileInput.click()"
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="handleDrop($event)"
                :class="{'border-cyan-400 bg-cyan-500/10': isDragging}"
            >
                <input 
                    type="file" 
                    x-ref="fileInput" 
                    @change="handleFileSelect($event)"
                    accept="image/jpeg,image/png,image/webp"
                    class="hidden"
                >
                
                <template x-if="!isLoading">
                    <div>
                        <i data-lucide="camera" class="w-10 h-10 text-slate-500 mx-auto mb-2"></i>
                        <p class="text-sm text-slate-400">
                            Tap to <span class="text-cyan-400">take photo</span> or upload
                        </p>
                        <p class="text-xs text-slate-600 mt-1">JPG, PNG, WebP • Max 5MB</p>
                    </div>
                </template>
                
                <template x-if="isLoading">
                    <div>
                        <div class="animate-spin w-8 h-8 border-2 border-cyan-400 border-t-transparent rounded-full mx-auto mb-2"></div>
                        <p class="text-sm text-slate-400">Uploading...</p>
                    </div>
                </template>
            </div>
        </template>
    </div>
    
    <!-- Status Message -->
    <template x-if="message">
        <div 
            class="mt-2 text-xs p-2 rounded"
            :class="{'bg-emerald-500/20 text-emerald-400': success, 'bg-red-500/20 text-red-400': !success}"
            x-text="message"
        ></div>
    </template>
</div>

<script>
function idUploader(guestId) {
    return {
        guestId: guestId,
        photoUrl: <?= json_encode($existingPhoto) ?>,
        isLoading: false,
        isDragging: false,
        message: '',
        success: false,
        
        async handleFileSelect(event) {
            const file = event.target.files[0];
            if (file) await this.uploadFile(file);
        },
        
        async handleDrop(event) {
            this.isDragging = false;
            const file = event.dataTransfer.files[0];
            if (file) await this.uploadFile(file);
        },
        
        async uploadFile(file) {
            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                this.showMessage('Invalid file type. Use JPG, PNG, or WebP.', false);
                return;
            }
            
            // Validate file size
            if (file.size > 5 * 1024 * 1024) {
                this.showMessage('File too large. Max 5MB allowed.', false);
                return;
            }
            
            this.isLoading = true;
            this.message = '';
            
            try {
                const formData = new FormData();
                formData.append('id_photo', file);
                
                const response = await fetch(`/api/guest/${this.guestId}/upload-id`, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.photoUrl = result.path;
                    this.showMessage('ID photo uploaded successfully!', true);
                } else {
                    this.showMessage(result.error || 'Upload failed', false);
                }
            } catch (error) {
                this.showMessage('Network error. Please try again.', false);
            } finally {
                this.isLoading = false;
            }
        },
        
        async deletePhoto() {
            if (!confirm('Delete this ID photo?')) return;
            
            this.isLoading = true;
            
            try {
                const response = await fetch(`/api/guest/${this.guestId}/id-photo`, {
                    method: 'DELETE',
                    credentials: 'same-origin'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.photoUrl = null;
                    this.showMessage('ID photo deleted', true);
                } else {
                    this.showMessage(result.error || 'Delete failed', false);
                }
            } catch (error) {
                this.showMessage('Network error', false);
            } finally {
                this.isLoading = false;
            }
        },
        
        viewPhoto() {
            if (this.photoUrl) {
                window.open(this.photoUrl, '_blank');
            }
        },
        
        showMessage(msg, isSuccess) {
            this.message = msg;
            this.success = isSuccess;
            setTimeout(() => { this.message = ''; }, 3000);
        }
    }
}
</script>

<style>
.id-upload-component .btn--sm {
    padding: 0.375rem 0.5rem;
    font-size: 0.75rem;
}

.id-upload-component .btn--danger {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.upload-zone {
    transition: all 0.2s;
}

.upload-zone:hover {
    background: rgba(34, 211, 238, 0.05);
}
</style>
