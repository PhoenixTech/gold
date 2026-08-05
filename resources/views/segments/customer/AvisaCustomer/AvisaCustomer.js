document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('avisa-menu-btn');
    const closeBtn = document.getElementById('avisa-close-btn');
    const sidebar = document.getElementById('avisa-sidebar');
    const backdrop = document.getElementById('avisa-backdrop');
    if (!btn || !sidebar) {
        return;
    }
    const profileAlert = document.getElementById('avisa-alert-profile');

    function updateAlertVisibility(targetHash) {
        const hash = targetHash || window.location.hash || '#summary';
        if (profileAlert) {
            profileAlert.style.setProperty('display', (hash === '#profile' || hash === '#addresses') ? 'none' : 'flex', 'important');
        }
    }

    const close = function () {
        if (sidebar) sidebar.classList.remove('open');
        if (backdrop) backdrop.classList.remove('open');
    };

    if (btn && sidebar) {
        btn.addEventListener('click', function () {
            sidebar.classList.toggle('open');
            if (backdrop) backdrop.classList.toggle('open');
        });
    }
    if (backdrop) backdrop.addEventListener('click', close);
    if (closeBtn) closeBtn.addEventListener('click', close);

    const tabLinks = document.querySelectorAll('.tab-control a, .avisa-alert-action');
    tabLinks.forEach(function (a) {
        a.addEventListener('click', function () {
            close();
            const href = this.getAttribute('href');
            if (href && href.startsWith('#')) {
                updateAlertVisibility(href);
            }
        });
    });

    const root = document.getElementById('AvisaCustomer');
    if (root && root.getAttribute('data-profile-incomplete') === 'true') {
        if (!window.location.hash || window.location.hash === '#summary') {
            const profileTab = document.querySelector('#avisa-tabs a[href="#profile"]');
            if (profileTab) {
                profileTab.click();
            }
        }
    }

    updateAlertVisibility(window.location.hash);
});
