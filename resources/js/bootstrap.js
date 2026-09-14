import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

/**
 * Load Axios HTTP library for handling backend API requests.
 */
import axios from 'axios';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Initialize Bootstrap 5 Tooltips across the document.
 */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
        const tooltip = new bootstrap.Tooltip(el);
        el.addEventListener('mouseleave', () => tooltip.hide());
    });
});
