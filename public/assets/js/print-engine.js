/**
 * HotelOS - Universal Print Engine
 * 
 * Handles print, PDF, and share functionality across all reports
 * Device-aware: Desktop (direct print), Tablet (dialog), Mobile (share sheet)
 */

const PrintEngine = {
    // Device detection
    isMobile: () => window.innerWidth < 768,
    isTablet: () => window.innerWidth >= 768 && window.innerWidth < 1024,
    isDesktop: () => window.innerWidth >= 1024,

    // Get print mode based on device
    getPrintMode() {
        if (this.isMobile()) return 'share';
        if (this.isTablet()) return 'dialog';
        return 'direct';
    },

    /**
     * Print a report
     * @param {string} reportId - ID of the report container element
     * @param {object} options - Print options
     */
    print(reportId, options = {}) {
        const element = document.getElementById(reportId);
        if (!element) {
            console.error('Report element not found:', reportId);
            return;
        }

        const defaults = {
            title: document.title,
            size: 'a4', // 'a4', 'thermal'
            orientation: 'portrait', // 'portrait', 'landscape'
            hideElements: ['.no-print', '.btn', 'button'],
            beforePrint: null,
            afterPrint: null
        };

        const settings = { ...defaults, ...options };

        // Call before print callback
        if (typeof settings.beforePrint === 'function') {
            settings.beforePrint();
        }

        // Create print window
        const printWindow = window.open('', '_blank', 'width=800,height=600');

        if (!printWindow) {
            alert('Please allow popups to print reports');
            return;
        }

        // Get styles
        const styles = this.getStyles(settings);

        // Clone and clean content
        const content = element.cloneNode(true);
        settings.hideElements.forEach(selector => {
            content.querySelectorAll(selector).forEach(el => el.remove());
        });

        // Write to print window
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>${settings.title}</title>
                <style>${styles}</style>
            </head>
            <body>
                ${content.outerHTML}
                <script>
                    window.onload = function() {
                        setTimeout(function() {
                            window.print();
                            window.close();
                        }, 250);
                    };
                </script>
            </body>
            </html>
        `);

        printWindow.document.close();

        // Call after print callback
        if (typeof settings.afterPrint === 'function') {
            setTimeout(settings.afterPrint, 500);
        }
    },

    /**
     * Generate PDF (using browser print to PDF)
     */
    pdf(reportId, options = {}) {
        // For now, trigger print dialog - user can save as PDF
        this.print(reportId, {
            ...options,
            title: options.title || 'Report'
        });

        // Show tip
        setTimeout(() => {
            if (this.isDesktop()) {
                console.info('Tip: To save as PDF, select "Save as PDF" or "Microsoft Print to PDF" as printer');
            }
        }, 100);
    },

    /**
     * Share report (Mobile)
     */
    async share(reportId, options = {}) {
        const element = document.getElementById(reportId);
        if (!element) return;

        const title = options.title || 'Report';
        const text = options.text || element.innerText.substring(0, 200) + '...';

        if (navigator.share) {
            try {
                await navigator.share({
                    title: title,
                    text: text,
                    url: window.location.href
                });
            } catch (e) {
                if (e.name !== 'AbortError') {
                    console.error('Share failed:', e);
                }
            }
        } else {
            // Fallback: Copy to clipboard
            this.copyToClipboard(element.innerText);
            alert('Report text copied to clipboard!');
        }
    },

    /**
     * Share via WhatsApp
     */
    shareWhatsApp(text, phone = '') {
        const encodedText = encodeURIComponent(text);
        const url = phone
            ? `https://wa.me/${phone}?text=${encodedText}`
            : `https://wa.me/?text=${encodedText}`;
        window.open(url, '_blank');
    },

    /**
     * Share via Email
     */
    shareEmail(subject, body, to = '') {
        const mailto = `mailto:${to}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
        window.location.href = mailto;
    },

    /**
     * Copy text to clipboard
     */
    async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch (e) {
            // Fallback
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            return true;
        }
    },

    /**
     * Get print styles
     */
    getStyles(settings) {
        const pageSize = settings.size === 'thermal' ? '80mm auto' : 'A4';
        const pageOrientation = settings.orientation === 'landscape' ? 'landscape' : 'portrait';

        return `
            @page {
                size: ${pageSize} ${pageOrientation};
                margin: ${settings.size === 'thermal' ? '3mm' : '15mm'};
            }
            
            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
            }
            
            body {
                font-family: 'Segoe UI', Arial, sans-serif;
                font-size: ${settings.size === 'thermal' ? '10px' : '12px'};
                line-height: 1.4;
                color: #000;
                background: #fff;
            }
            
            /* Report Container */
            .report-container {
                max-width: 100%;
                padding: ${settings.size === 'thermal' ? '2mm' : '20px'};
            }
            
            /* Header */
            .report-header {
                text-align: center;
                border-bottom: 2px solid #000;
                padding-bottom: 10px;
                margin-bottom: 15px;
            }
            
            .report-header h1 {
                font-size: ${settings.size === 'thermal' ? '14px' : '18px'};
                font-weight: bold;
                margin-bottom: 5px;
            }
            
            .report-header p {
                font-size: ${settings.size === 'thermal' ? '9px' : '11px'};
                color: #333;
            }
            
            /* Title Bar */
            .report-title {
                background: #f0f0f0;
                padding: 8px 12px;
                text-align: center;
                font-weight: bold;
                border: 1px solid #000;
                margin-bottom: 15px;
            }
            
            /* Tables */
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 15px;
            }
            
            th, td {
                border: 1px solid #000;
                padding: ${settings.size === 'thermal' ? '4px' : '8px'};
                text-align: left;
                font-size: ${settings.size === 'thermal' ? '9px' : '11px'};
            }
            
            th {
                background: #f0f0f0;
                font-weight: bold;
            }
            
            tr:nth-child(even) {
                background: #f9f9f9;
            }
            
            /* Totals Row */
            .total-row, tr.total-row td {
                font-weight: bold;
                background: #e0e0e0 !important;
            }
            
            /* Summary Cards */
            .summary-grid {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin-bottom: 15px;
            }
            
            .summary-card {
                flex: 1;
                min-width: 100px;
                border: 1px solid #000;
                padding: 10px;
                text-align: center;
            }
            
            .summary-value {
                font-size: ${settings.size === 'thermal' ? '14px' : '18px'};
                font-weight: bold;
            }
            
            .summary-label {
                font-size: ${settings.size === 'thermal' ? '8px' : '10px'};
                color: #666;
            }
            
            /* Footer */
            .report-footer {
                margin-top: 20px;
                padding-top: 10px;
                border-top: 1px solid #000;
                display: flex;
                justify-content: space-between;
                font-size: ${settings.size === 'thermal' ? '8px' : '10px'};
            }
            
            .signature-box {
                text-align: center;
                min-width: 150px;
            }
            
            .signature-line {
                border-top: 1px solid #000;
                margin-top: 30px;
                padding-top: 5px;
            }
            
            /* Utilities */
            .text-right { text-align: right; }
            .text-center { text-align: center; }
            .bold { font-weight: bold; }
            .no-print { display: none !important; }
            
            /* Hide screen-only elements */
            .screen-only, button, .btn {
                display: none !important;
            }
        `;
    },

    /**
     * Format currency (Indian Rupee)
     */
    formatCurrency(amount) {
        return '₹' + parseFloat(amount || 0).toLocaleString('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    },

    /**
     * Format date (DD-MMM-YYYY)
     */
    formatDate(dateStr) {
        const date = new Date(dateStr);
        const options = { day: '2-digit', month: 'short', year: 'numeric' };
        return date.toLocaleDateString('en-IN', options);
    }
};

// Export for use
window.PrintEngine = PrintEngine;
