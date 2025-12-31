/**
 * Loading State Manager
 * Prevents white screen during page transitions
 * Shows loading spinner for better UX
 */

(function () {
    'use strict';

    // Create loading overlay HTML
    const loadingHTML = `
        <div id="page-loader" class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-[9999] hidden items-center justify-center">
            <div class="text-center">
                <div class="inline-block animate-spin rounded-full h-16 w-16 border-4 border-cyan-500 border-t-transparent"></div>
                <p class="text-white mt-4 text-lg font-medium">Loading...</p>
            </div>
        </div>
    `;

    // Inject loading overlay on page load
    document.addEventListener('DOMContentLoaded', function () {
        document.body.insertAdjacentHTML('beforeend', loadingHTML);
    });

    // Loading state controller
    window.LoadingState = {
        show: function () {
            const loader = document.getElementById('page-loader');
            if (loader) {
                loader.classList.remove('hidden');
                loader.classList.add('flex');
            }
        },

        hide: function () {
            const loader = document.getElementById('page-loader');
            if (loader) {
                loader.classList.add('hidden');
                loader.classList.remove('flex');
            }
        }
    };

    // Auto-show loader on navigation clicks
    document.addEventListener('click', function (e) {
        const link = e.target.closest('a');

        // Only show for internal navigation (not external links, #anchors, or javascript:void)
        if (link &&
            link.href &&
            !link.href.startsWith('#') &&
            !link.href.startsWith('javascript:') &&
            link.hostname === window.location.hostname &&
            !link.hasAttribute('data-no-loader')) {

            // Don't show for logout (redirect anyway)
            if (!link.href.includes('/logout')) {
                window.LoadingState.show();
            }
        }
    });

    // Auto-show loader on form submissions
    document.addEventListener('submit', function (e) {
        const form = e.target;

        // Only for POST forms (not search forms)
        if (form.method && form.method.toLowerCase() === 'post') {
            window.LoadingState.show();
        }
    });

    // Auto-hide loader when page loads
    window.addEventListener('load', function () {
        window.LoadingState.hide();
    });

    // Failsafe: Hide loader after 10 seconds (in case of errors)
    setTimeout(function () {
        window.LoadingState.hide();
    }, 10000);

    // Hide loader if user navigates back via browser back button
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            window.LoadingState.hide();
        }
    });

})();
