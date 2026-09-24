function resolveSafeUrl(form) {
    const safeUrlEl = form.querySelector('.safe-url');
    const url = safeUrlEl?.getAttribute('data-url');
    if (url) {
        form.setAttribute('action', url);
    }
}

document.addEventListener('submit', (e) => {
    const form = e.target.closest('.safe-form');
    if (form) {
        resolveSafeUrl(form);
    }
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.safe-form').forEach(resolveSafeUrl);
});
