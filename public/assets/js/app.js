/**
 * HotelOS - Alpine.js Components
 * Lightweight interactivity without full page reloads
 */

document.addEventListener('alpine:init', () => {

    // ============================================
    // Theme Manager
    // ============================================
    Alpine.store('theme', {
        current: localStorage.getItem('hotelos_theme') || 'cosmic',

        init() {
            this.apply(this.current);
        },

        set(theme) {
            this.current = theme;
            localStorage.setItem('hotelos_theme', theme);
            this.apply(theme);
        },

        apply(theme) {
            document.documentElement.setAttribute('data-theme', theme);
        },

        toggle() {
            const themes = ['cosmic', 'royal', 'comfort'];
            const idx = themes.indexOf(this.current);
            this.set(themes[(idx + 1) % themes.length]);
        }
    });

    // ============================================
    // Toast Notifications
    // ============================================
    Alpine.store('toast', {
        items: [],

        show(message, type = 'info', duration = 4000) {
            const id = Date.now();
            this.items.push({ id, message, type });

            setTimeout(() => {
                this.dismiss(id);
            }, duration);
        },

        dismiss(id) {
            this.items = this.items.filter(t => t.id !== id);
        },

        success(message) { this.show(message, 'success'); },
        error(message) { this.show(message, 'error'); },
        warning(message) { this.show(message, 'warning'); }
    });

    // ============================================
    // Auth Component (Login Form)
    // ============================================
    Alpine.data('loginForm', () => ({
        email: '',
        password: '',
        showPassword: false,
        loading: false,
        error: null,
        errors: {},

        togglePassword() {
            this.showPassword = !this.showPassword;
        },

        validate() {
            this.errors = {};

            if (!this.email) {
                this.errors.email = 'Email is required';
            } else if (!this.isValidEmail(this.email)) {
                this.errors.email = 'Please enter a valid email';
            }

            if (!this.password) {
                this.errors.password = 'Password is required';
            } else if (this.password.length < 6) {
                this.errors.password = 'Password must be at least 6 characters';
            }

            return Object.keys(this.errors).length === 0;
        },

        isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        },

        async submit() {
            this.error = null;

            if (!this.validate()) {
                return;
            }

            this.loading = true;

            try {
                const response = await fetch('/api/auth/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        email: this.email,
                        password: this.password
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Redirect to dashboard
                    window.location.href = data.redirect || '/dashboard';
                } else {
                    this.error = data.message || 'Login failed. Please try again.';
                }
            } catch (err) {
                console.error('Login error:', err);
                this.error = 'Network error. Please check your connection.';
            } finally {
                this.loading = false;
            }
        }
    }));

    // ============================================
    // Modal Component
    // ============================================
    Alpine.data('modal', (options = {}) => ({
        open: false,

        show() {
            this.open = true;
            document.body.style.overflow = 'hidden';
        },

        close() {
            this.open = false;
            document.body.style.overflow = '';
        },

        toggle() {
            this.open ? this.close() : this.show();
        }
    }));

    // ============================================
    // Dropdown Component
    // ============================================
    Alpine.data('dropdown', () => ({
        open: false,

        toggle() {
            this.open = !this.open;
        },

        close() {
            this.open = false;
        }
    }));

    // ============================================
    // Loading State Manager
    // ============================================
    Alpine.data('loader', () => ({
        loading: false,

        start() { this.loading = true; },
        stop() { this.loading = false; },

        async wrap(fn) {
            this.start();
            try {
                return await fn();
            } finally {
                this.stop();
            }
        }
    }));
});

// ============================================
// Utility Functions
// ============================================

/**
 * Format currency for Indian Rupees
 */
function formatINR(amount) {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    }).format(amount);
}

/**
 * Format date for Indian locale
 */
function formatDate(date, format = 'short') {
    const d = new Date(date);
    const options = format === 'short'
        ? { day: '2-digit', month: 'short', year: 'numeric' }
        : { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' };

    return d.toLocaleDateString('en-IN', options);
}

/**
 * Debounce function for search inputs
 */
function debounce(fn, delay = 300) {
    let timeout;
    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => fn(...args), delay);
    };
}

/**
 * Copy text to clipboard
 */
async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text);
        Alpine.store('toast').success('Copied to clipboard!');
        return true;
    } catch (err) {
        console.error('Copy failed:', err);
        return false;
    }
}

// ============================================
// Global Event Handlers
// ============================================

// Initialize theme on page load
document.addEventListener('DOMContentLoaded', () => {
    const savedTheme = localStorage.getItem('hotelos_theme') || 'cosmic';
    document.documentElement.setAttribute('data-theme', savedTheme);
});

// Handle HTMX events (if using HTMX)
document.body.addEventListener('htmx:afterRequest', (event) => {
    if (event.detail.failed) {
        Alpine.store('toast').error('Request failed. Please try again.');
    }
});

// Keyboard shortcuts
document.addEventListener('keydown', (e) => {
    // Escape to close modals
    if (e.key === 'Escape') {
        document.querySelectorAll('[x-data*="modal"]').forEach(el => {
            if (el._x_dataStack) {
                el._x_dataStack[0].close?.();
            }
        });
    }
});
