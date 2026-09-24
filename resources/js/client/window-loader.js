window.addEventListener('load', function () {
    const apiUrlInput = document.querySelector('#api-display-url');
    if (apiUrlInput && apiUrlInput.value) {
        const lastCall = localStorage.getItem('last_api_call');
        const now = Date.now();
        if (!lastCall || (now - Number(lastCall)) >= 59 * 60 * 1000) {
            axios.post(apiUrlInput.value, {
                display: `${window.screen.availWidth}x${window.screen.availHeight}`,
            }).then(() => {
                localStorage.setItem('last_api_call', String(now));
            }).catch((err) => {
                console.error('Display metrics error:', err);
            });
        }
    }

    if (window.bootstrap?.Tooltip) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
            new window.bootstrap.Tooltip(el);
        });
    }

    document.querySelectorAll('.content table').forEach((table) => {
        table.classList.add('table');
    });
});
