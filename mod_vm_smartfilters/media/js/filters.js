/* SPDX-License-Identifier: GPL-2.0-or-later */
(() => {
    'use strict';
    function initialize() {
        document.querySelectorAll('[data-vm-filters]').forEach((form) => {
            if (form.dataset.initialized) return;
            form.dataset.initialized = 'true';
            form.addEventListener('change', (event) => {
                if (form.dataset.autoSubmit === '1' && event.target.matches('select')) {
                    form.requestSubmit();
                }
            });
            form.addEventListener('submit', () => {
                form.setAttribute('aria-busy', 'true');
                const status = form.querySelector('[role="status"]');
                if (status) status.hidden = false;
                // Keep successful controls enabled so the browser submits every value.
            });
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
        initialize();
    }
    window.addEventListener('pageshow', () => {
        document.querySelectorAll('[data-vm-filters]').forEach((form) => {
            form.removeAttribute('aria-busy');
            const status = form.querySelector('[role="status"]');
            if (status) status.hidden = true;
        });
    });
})();
