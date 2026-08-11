document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('avisa-menu-btn');
    const closeBtn = document.getElementById('avisa-close-btn');
    const sidebar = document.getElementById('avisa-sidebar');
    const backdrop = document.getElementById('avisa-backdrop');
    if (!btn || !sidebar) {
        return;
    }
    const profileAlert = document.getElementById('avisa-alert-profile');
    const receiptAlerts = document.querySelectorAll('.avisa-receipt-alert');

    function updateAlertVisibility(targetHash) {
        const hash = targetHash || window.location.hash || '#summary';
        if (profileAlert) {
            profileAlert.style.setProperty('display', (hash === '#profile' || hash === '#addresses') ? 'none' : 'flex', 'important');
        }
        receiptAlerts.forEach(function (alert) {
            alert.style.setProperty('display', hash === '#card-payment' ? 'none' : 'flex', 'important');
        });
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
                if (!this.closest('.tab-control')) {
                    const targetTab = document.querySelector(`.tab-control a[href="${href}"]`);
                    if (targetTab) {
                        targetTab.click();
                    }
                }
            }
        });
    });

    const root = document.getElementById('AvisaCustomer');
    if (root && root.getAttribute('data-profile-incomplete') === 'true') {
        if (!window.location.hash) {
            const profileTab = document.querySelector('#avisa-tabs a[href="#profile"]');
            if (profileTab) {
                profileTab.click();
            }
        }
    }

    updateAlertVisibility(window.location.hash);

    window.addEventListener('hashchange', function () {
        updateAlertVisibility(window.location.hash);
    });
});

