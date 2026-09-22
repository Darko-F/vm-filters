/* SPDX-License-Identifier: GPL-2.0-or-later */
(() => {
    'use strict';
    function initialize() {
        document.querySelectorAll('[data-vm-filters]').forEach((form) => {
            if (form.dataset.initialized) return;
            form.dataset.initialized = 'true';
            const panels = Array.from(form.querySelectorAll('.vm-filter-dropdown'));
            panels.forEach((details) => {
                details.addEventListener('toggle', () => {
                    if (!details.open) return;
                    panels.forEach((other) => { if (other !== details) other.open = false; });
                    const panel = details.querySelector('.vm-filter-panel');
                    panel.classList.remove('vm-filter-panel-end');
                    const rect = panel.getBoundingClientRect();
                    if (rect.right > document.documentElement.clientWidth - 16 || rect.left < 16) {
                        panel.classList.add('vm-filter-panel-end');
                    }
                });
                details.addEventListener('keydown', (event) => {
                    if (event.key !== 'Escape') return;
                    details.open = false;
                    details.querySelector('summary').focus();
                    event.stopPropagation();
                });
            });
            // Reveal invalid controls before the browser tries to focus them.
            form.addEventListener('invalid', (event) => {
                const details = event.target.closest('.vm-filter-dropdown');
                if (details) details.open = true;
            }, true);
            if (panels.length) {
                document.addEventListener('click', (event) => {
                    panels.forEach((details) => {
                        if (!details.contains(event.target)) details.open = false;
                    });
                });
            }
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
