// Client metrics ping and general UI enhancements

window.addEventListener('load', function () {
    const API_COOKIE_NAME = 'last_api_call';
    const COOKIE_EXPIRY_MINUTES = 59;

    function setCookie(name, value, minutes) {
        let expires = '';
        if (minutes) {
            const date = new Date();
            date.setTime(date.getTime() + (minutes * 60 * 1000));
            expires = '; expires=' + date.toUTCString();
        }
        document.cookie = `${name}=${value || ''}${expires}; path=/`;
    }

    function getCookie(name) {
        const nameEQ = name + '=';
        const ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }

    function canSendData() {
        const lastCall = getCookie(API_COOKIE_NAME);
        if (!lastCall) return true;
        const lastCallTime = new Date(parseInt(lastCall, 10));
        const currentTime = new Date();
        const diffMinutes = (currentTime - lastCallTime) / (1000 * 60);
        return diffMinutes >= COOKIE_EXPIRY_MINUTES;
    }

    // Safe API display URL check
    const apiUrlInput = document.querySelector('#api-display-url');
    if (apiUrlInput && apiUrlInput.value && canSendData()) {
        axios.post(apiUrlInput.value, {
            display: `${window.screen.availWidth}x${window.screen.availHeight}`,
        }).then(() => {
            setCookie(API_COOKIE_NAME, new Date().getTime(), COOKIE_EXPIRY_MINUTES);
        }).catch((err) => {
            console.error('Display metrics error:', err);
        });
    }

    // Initialize Bootstrap tooltips
    if (window.bootstrap?.Tooltip) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
            new window.bootstrap.Tooltip(el);
        });
    }

    // Add table class to rich text content tables
    document.querySelectorAll('.content table').forEach((table) => {
        table.classList.add('table');
    });
});
