// Customer Dashboard JS (element-guarded without premature exit)

document.addEventListener('DOMContentLoaded', function () {
    const customerRoot = document.getElementById('AvisaCustomer');
    if (!customerRoot) return;

    const btn = document.getElementById('avisa-menu-btn');
    const closeBtn = document.getElementById('avisa-close-btn');
    const sidebar = document.getElementById('avisa-sidebar');
    const backdrop = document.getElementById('avisa-backdrop');
    const profileAlert = document.getElementById('avisa-alert-profile');
    const receiptAlerts = document.querySelectorAll('.avisa-receipt-alert');

    const closeSidebar = function () {
        sidebar?.classList.remove('open');
        backdrop?.classList.remove('open');
    };

    // Mobile sidebar toggle (guarded independently)
    if (btn && sidebar) {
        btn.addEventListener('click', function () {
            sidebar.classList.toggle('open');
            backdrop?.classList.toggle('open');
        });
    }
    backdrop?.addEventListener('click', closeSidebar);
    closeBtn?.addEventListener('click', closeSidebar);

    function updateAlertVisibility(targetHash) {
        const hash = targetHash || window.location.hash || '#summary';
        if (profileAlert) {
            profileAlert.style.setProperty('display', (hash === '#profile' || hash === '#addresses') ? 'none' : 'flex', 'important');
        }
        receiptAlerts.forEach(function (alert) {
            alert.style.setProperty('display', hash === '#card-payment' ? 'none' : 'flex', 'important');
        });
    }

    // Dashboard tabs & alert action links
    const tabLinks = document.querySelectorAll('.tab-control a, .avisa-alert-action');
    tabLinks.forEach(function (a) {
        a.addEventListener('click', function () {
            closeSidebar();
            const href = this.getAttribute('href');
            if (href && href.startsWith('#')) {
                updateAlertVisibility(href);
                if (!this.closest('.tab-control')) {
                    const targetTab = document.querySelector(`.tab-control a[href="${href}"]`);
                    targetTab?.click();
                }
            }
        });
    });

    // Incomplete profile auto-switch to #profile
    if (customerRoot.getAttribute('data-profile-incomplete') === 'true') {
        if (!window.location.hash) {
            const profileTab = document.querySelector('#avisa-tabs a[href="#profile"]');
            profileTab?.click();
        }
    }

    updateAlertVisibility(window.location.hash);

    window.addEventListener('hashchange', function () {
        updateAlertVisibility(window.location.hash);
    });
});
