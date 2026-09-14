// Instant action resolution for safe forms with anti-bot protection

function applySafeUrls() {
    document.querySelectorAll('.safe-form').forEach((form) => {
        const safeUrlEl = form.querySelector('.safe-url');
        const url = safeUrlEl?.getAttribute('data-url');
        if (url) {
            form.setAttribute('action', url);
        }
    });
}

// Apply immediately on DOMContentLoaded
document.addEventListener('DOMContentLoaded', applySafeUrls);

// Fallback on form submit delegation
document.addEventListener('submit', (e) => {
    const form = e.target.closest('.safe-form');
    if (form) {
        const safeUrlEl = form.querySelector('.safe-url');
        const url = safeUrlEl?.getAttribute('data-url');
        if (url) {
            form.setAttribute('action', url);
        }
    }
});
