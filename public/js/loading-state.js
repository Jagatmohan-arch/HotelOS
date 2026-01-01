/**
 * HotelOS - Loading State Manager
 * Handles page transitions and ajax loading states
 */

document.addEventListener('alpine:init', () => {
    Alpine.store('loader', {
        isVisible: false,
        message: 'Loading...',

        show(msg = 'Loading...') {
            this.message = msg;
            this.isVisible = true;
        },

        hide() {
            this.isVisible = false;
        }
    });
});

window.addEventListener('load', () => {
    // Remove initial page loader if exists
    const pageLoader = document.getElementById('page-loader');
    if (pageLoader) {
        pageLoader.style.opacity = '0';
        setTimeout(() => pageLoader.remove(), 300);
    }
});
