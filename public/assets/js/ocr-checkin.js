/**
 * HotelOS - OCR Check-in Module
 * Client-side ID document scanning using Tesseract.js v5
 * 
 * Supports:
 * - Aadhaar Card (12-digit)
 * - PAN Card (10-character alphanumeric)
 * - Passport
 * - Driving License
 * 
 * Usage:
 *   const ocr = new OCRCheckIn();
 *   const result = await ocr.scanDocument(imageFile);
 */

class OCRCheckIn {
    constructor(options = {}) {
        this.options = {
            language: 'eng+hin', // English + Hindi
            progressCallback: null,
            ...options
        };

        this.worker = null;
        this.isReady = false;

        // ID Patterns (Indian Documents)
        this.patterns = {
            // Aadhaar: 4 digits - 4 digits - 4 digits
            aadhaar: /\b(\d{4}\s?\d{4}\s?\d{4})\b/g,

            // PAN: 5 letters + 4 digits + 1 letter (e.g., ABCDE1234F)
            pan: /\b([A-Z]{5}\d{4}[A-Z])\b/gi,

            // Passport: Letter + 7 digits (e.g., A1234567)
            passport: /\b([A-Z]\d{7})\b/gi,

            // Date patterns (DD/MM/YYYY or DD-MM-YYYY)
            date: /\b(\d{1,2}[\/-]\d{1,2}[\/-]\d{4})\b/g,

            // Name patterns (after common labels)
            nameLabels: /(?:name|नाम)\s*[:\s]*([A-Za-z\s]+)/gi,

            // DOB patterns
            dobLabels: /(?:dob|date of birth|जन्म तिथि|d\.o\.b)\s*[:\s]*(\d{1,2}[\/-]\d{1,2}[\/-]\d{4})/gi,

            // Gender
            gender: /\b(male|female|पुरुष|महिला)\b/gi,

            // Father's name
            fatherName: /(?:father|s\/o|d\/o|पिता)\s*[:\s]*([A-Za-z\s]+)/gi,

            // Address (pincode based)
            pincode: /\b(\d{6})\b/g,

            // Phone (10 digits starting with 6-9)
            phone: /\b([6-9]\d{9})\b/g,
        };
    }

    /**
     * Initialize Tesseract worker
     */
    async initialize() {
        if (this.isReady) return;

        try {
            // Load Tesseract.js from CDN if not available
            if (typeof Tesseract === 'undefined') {
                await this.loadScript('https://unpkg.com/tesseract.js@5/dist/tesseract.min.js');
            }

            this.updateProgress('Initializing OCR engine...', 0);

            this.worker = await Tesseract.createWorker(this.options.language, 1, {
                logger: (m) => {
                    if (m.status === 'recognizing text') {
                        this.updateProgress('Scanning document...', Math.round(m.progress * 100));
                    }
                }
            });

            this.isReady = true;
            this.updateProgress('OCR engine ready', 100);
        } catch (error) {
            console.error('OCR initialization failed:', error);
            throw new Error('Failed to initialize OCR engine. Please check your internet connection.');
        }
    }

    /**
     * Scan document and extract guest information
     * @param {File|Blob|string} image - Image file, blob, or base64 string
     * @returns {Object} Extracted guest data
     */
    async scanDocument(image) {
        await this.initialize();

        this.updateProgress('Processing image...', 10);

        try {
            const { data } = await this.worker.recognize(image);
            const text = data.text;

            console.log('OCR Raw Text:', text);

            this.updateProgress('Extracting information...', 80);

            // Detect document type and extract data
            const result = this.extractData(text);

            this.updateProgress('Complete!', 100);

            return result;
        } catch (error) {
            console.error('OCR scan failed:', error);
            throw new Error('Failed to scan document. Please try with a clearer image.');
        }
    }

    /**
     * Extract structured data from OCR text
     */
    extractData(text) {
        const result = {
            raw_text: text,
            document_type: null,
            confidence: 0,
            extracted: {
                id_type: null,
                id_number: null,
                name: null,
                dob: null,
                gender: null,
                father_name: null,
                address: null,
                pincode: null,
                phone: null,
            }
        };

        // Detect document type
        if (this.patterns.aadhaar.test(text)) {
            result.document_type = 'aadhaar';
            result.extracted.id_type = 'Aadhaar';
            const match = text.match(this.patterns.aadhaar);
            if (match) {
                result.extracted.id_number = match[0].replace(/\s/g, '');
            }
            result.confidence = 0.9;
        } else if (this.patterns.pan.test(text)) {
            result.document_type = 'pan';
            result.extracted.id_type = 'PAN';
            const match = text.match(this.patterns.pan);
            if (match) {
                result.extracted.id_number = match[0].toUpperCase();
            }
            result.confidence = 0.85;
        } else if (this.patterns.passport.test(text)) {
            result.document_type = 'passport';
            result.extracted.id_type = 'Passport';
            const match = text.match(this.patterns.passport);
            if (match) {
                result.extracted.id_number = match[0].toUpperCase();
            }
            result.confidence = 0.8;
        }

        // Reset regex lastIndex
        Object.values(this.patterns).forEach(p => p.lastIndex = 0);

        // Extract common fields

        // Name
        const nameMatch = this.patterns.nameLabels.exec(text);
        if (nameMatch) {
            result.extracted.name = this.cleanName(nameMatch[1]);
        }

        // Date of Birth
        const dobMatch = this.patterns.dobLabels.exec(text);
        if (dobMatch) {
            result.extracted.dob = this.parseDate(dobMatch[1]);
        } else {
            // Try to find any date (first one might be DOB)
            const dateMatch = text.match(this.patterns.date);
            if (dateMatch && dateMatch.length > 0) {
                result.extracted.dob = this.parseDate(dateMatch[0]);
            }
        }

        // Gender
        const genderMatch = text.match(this.patterns.gender);
        if (genderMatch) {
            const g = genderMatch[0].toLowerCase();
            result.extracted.gender = (g === 'male' || g === 'पुरुष') ? 'Male' : 'Female';
        }

        // Father's Name
        const fatherMatch = this.patterns.fatherName.exec(text);
        if (fatherMatch) {
            result.extracted.father_name = this.cleanName(fatherMatch[1]);
        }

        // Pincode
        const pincodeMatch = text.match(this.patterns.pincode);
        if (pincodeMatch) {
            result.extracted.pincode = pincodeMatch[0];
        }

        // Phone
        const phoneMatch = text.match(this.patterns.phone);
        if (phoneMatch) {
            result.extracted.phone = phoneMatch[0];
        }

        // Try to extract address from text (lines containing pincode)
        if (result.extracted.pincode) {
            const lines = text.split('\n');
            for (let i = 0; i < lines.length; i++) {
                if (lines[i].includes(result.extracted.pincode)) {
                    // Get 2-3 lines before pincode as address
                    const addressLines = lines.slice(Math.max(0, i - 2), i + 1);
                    result.extracted.address = addressLines.join(', ').replace(/\s+/g, ' ').trim();
                    break;
                }
            }
        }

        return result;
    }

    /**
     * Clean and format name
     */
    cleanName(name) {
        if (!name) return null;
        return name
            .replace(/[^A-Za-z\s]/g, '')
            .replace(/\s+/g, ' ')
            .trim()
            .split(' ')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
            .join(' ');
    }

    /**
     * Parse date string to YYYY-MM-DD format
     */
    parseDate(dateStr) {
        if (!dateStr) return null;

        const parts = dateStr.split(/[\/-]/);
        if (parts.length !== 3) return null;

        let day = parseInt(parts[0], 10);
        let month = parseInt(parts[1], 10);
        let year = parseInt(parts[2], 10);

        // Handle 2-digit year
        if (year < 100) {
            year = year > 50 ? 1900 + year : 2000 + year;
        }

        // Validate
        if (day < 1 || day > 31 || month < 1 || month > 12) {
            return null;
        }

        return `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    }

    /**
     * Load external script
     */
    loadScript(src) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = src;
            script.onload = resolve;
            script.onerror = () => reject(new Error(`Failed to load script: ${src}`));
            document.head.appendChild(script);
        });
    }

    /**
     * Update progress callback
     */
    updateProgress(status, percent) {
        if (this.options.progressCallback) {
            this.options.progressCallback({ status, percent });
        }
    }

    /**
     * Cleanup worker
     */
    async terminate() {
        if (this.worker) {
            await this.worker.terminate();
            this.worker = null;
            this.isReady = false;
        }
    }
}

/**
 * OCR Camera/Upload UI Component
 */
class OCRUploadUI {
    constructor(containerSelector, options = {}) {
        this.container = document.querySelector(containerSelector);
        this.options = {
            onResult: null,
            onError: null,
            ...options
        };

        this.ocr = new OCRCheckIn({
            progressCallback: (p) => this.updateProgress(p)
        });

        this.render();
        this.bindEvents();
    }

    render() {
        this.container.innerHTML = `
            <div class="ocr-upload-container">
                <div class="ocr-dropzone" id="ocrDropzone">
                    <div class="ocr-icon">📸</div>
                    <p class="ocr-title">Scan ID Document</p>
                    <p class="ocr-subtitle">Drag & drop or click to upload Aadhaar, PAN, or Passport</p>
                    <input type="file" id="ocrFileInput" accept="image/*" capture="environment" hidden>
                </div>
                
                <div class="ocr-progress" id="ocrProgress" style="display: none;">
                    <div class="ocr-progress-bar">
                        <div class="ocr-progress-fill" id="ocrProgressFill"></div>
                    </div>
                    <p class="ocr-progress-text" id="ocrProgressText">Initializing...</p>
                </div>
                
                <div class="ocr-preview" id="ocrPreview" style="display: none;">
                    <img id="ocrPreviewImage" alt="Document preview">
                    <button type="button" class="ocr-retry-btn" id="ocrRetryBtn">↺ Scan Again</button>
                </div>
            </div>
        `;

        this.addStyles();
    }

    addStyles() {
        if (document.getElementById('ocr-styles')) return;

        const styles = document.createElement('style');
        styles.id = 'ocr-styles';
        styles.textContent = `
            .ocr-upload-container {
                width: 100%;
            }
            
            .ocr-dropzone {
                border: 2px dashed rgba(34, 211, 238, 0.4);
                border-radius: 12px;
                padding: 30px 20px;
                text-align: center;
                cursor: pointer;
                transition: all 0.3s;
                background: rgba(34, 211, 238, 0.05);
            }
            
            .ocr-dropzone:hover,
            .ocr-dropzone.dragover {
                border-color: #22d3ee;
                background: rgba(34, 211, 238, 0.1);
            }
            
            .ocr-icon {
                font-size: 2.5rem;
                margin-bottom: 10px;
            }
            
            .ocr-title {
                color: #22d3ee;
                font-weight: 600;
                margin-bottom: 5px;
            }
            
            .ocr-subtitle {
                color: #64748b;
                font-size: 0.8rem;
            }
            
            .ocr-progress {
                padding: 20px;
                text-align: center;
            }
            
            .ocr-progress-bar {
                height: 8px;
                background: rgba(255,255,255,0.1);
                border-radius: 4px;
                overflow: hidden;
                margin-bottom: 10px;
            }
            
            .ocr-progress-fill {
                height: 100%;
                width: 0%;
                background: linear-gradient(90deg, #22d3ee, #a78bfa);
                border-radius: 4px;
                transition: width 0.3s;
            }
            
            .ocr-progress-text {
                color: #94a3b8;
                font-size: 0.85rem;
            }
            
            .ocr-preview {
                text-align: center;
            }
            
            .ocr-preview img {
                max-width: 100%;
                max-height: 200px;
                border-radius: 8px;
                margin-bottom: 10px;
            }
            
            .ocr-retry-btn {
                background: rgba(255,255,255,0.1);
                border: none;
                color: #94a3b8;
                padding: 8px 16px;
                border-radius: 6px;
                cursor: pointer;
                transition: all 0.2s;
            }
            
            .ocr-retry-btn:hover {
                background: rgba(255,255,255,0.15);
                color: #e2e8f0;
            }
        `;
        document.head.appendChild(styles);
    }

    bindEvents() {
        const dropzone = this.container.querySelector('#ocrDropzone');
        const fileInput = this.container.querySelector('#ocrFileInput');
        const retryBtn = this.container.querySelector('#ocrRetryBtn');

        // Click to upload
        dropzone.addEventListener('click', () => fileInput.click());

        // File selected
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                this.processFile(e.target.files[0]);
            }
        });

        // Drag and drop
        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.classList.add('dragover');
        });

        dropzone.addEventListener('dragleave', () => {
            dropzone.classList.remove('dragover');
        });

        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('dragover');
            if (e.dataTransfer.files.length > 0) {
                this.processFile(e.dataTransfer.files[0]);
            }
        });

        // Retry
        retryBtn.addEventListener('click', () => this.reset());
    }

    async processFile(file) {
        // Validate file type
        if (!file.type.startsWith('image/')) {
            this.showError('Please upload an image file');
            return;
        }

        // Show preview
        const preview = this.container.querySelector('#ocrPreview');
        const previewImg = this.container.querySelector('#ocrPreviewImage');
        const dropzone = this.container.querySelector('#ocrDropzone');
        const progress = this.container.querySelector('#ocrProgress');

        const reader = new FileReader();
        reader.onload = (e) => {
            previewImg.src = e.target.result;
        };
        reader.readAsDataURL(file);

        // Show progress
        dropzone.style.display = 'none';
        progress.style.display = 'block';
        preview.style.display = 'none';

        try {
            const result = await this.ocr.scanDocument(file);

            // Show preview with result
            progress.style.display = 'none';
            preview.style.display = 'block';

            if (this.options.onResult) {
                this.options.onResult(result);
            }
        } catch (error) {
            this.showError(error.message);
            this.reset();
        }
    }

    updateProgress({ status, percent }) {
        const fill = this.container.querySelector('#ocrProgressFill');
        const text = this.container.querySelector('#ocrProgressText');

        fill.style.width = `${percent}%`;
        text.textContent = status;
    }

    showError(message) {
        if (this.options.onError) {
            this.options.onError(message);
        } else {
            alert(message);
        }
    }

    reset() {
        const dropzone = this.container.querySelector('#ocrDropzone');
        const progress = this.container.querySelector('#ocrProgress');
        const preview = this.container.querySelector('#ocrPreview');
        const fileInput = this.container.querySelector('#ocrFileInput');

        dropzone.style.display = 'block';
        progress.style.display = 'none';
        preview.style.display = 'none';
        fileInput.value = '';
    }
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { OCRCheckIn, OCRUploadUI };
}
