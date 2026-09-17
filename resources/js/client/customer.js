// Customer Dashboard JS

document.addEventListener('DOMContentLoaded', function () {
    const customerRoot = document.getElementById('AvisaCustomer');
    if (!customerRoot) return;

    const profileAlert = document.getElementById('avisa-alert-profile');
    const receiptAlerts = document.querySelectorAll('.avisa-receipt-alert');

    function updateAlertVisibility(targetHash) {
        const hash = targetHash || window.location.hash || '#summary';
        if (profileAlert) {
            const isEditing = (hash === '#profile' || hash === '#profile-edit' || hash === '#addresses');
            profileAlert.style.setProperty('display', isEditing ? 'none' : 'flex', 'important');
        }
        receiptAlerts.forEach(function (alert) {
            alert.style.setProperty('display', hash === '#card-payment' ? 'none' : 'flex', 'important');
        });
    }

    function updateBottomNav(targetHash) {
        const hash = targetHash || window.location.hash || '#summary';
        const target = (hash === '#invoices' || hash === '#active-orders') ? '#invoices' : (hash === '#likes' ? '#likes' : '#summary');
        document.querySelectorAll('.avisa-bottom-nav-item[data-tab-target]').forEach(function (el) {
            el.classList.toggle('active', el.getAttribute('data-tab-target') === target);
        });
    }

    function switchTab(targetHash, pushHistory = true) {
        const hash = targetHash || '#summary';
        if (!hash.startsWith('#') || hash.length < 2) return;

        const targetPane = document.querySelector(hash);
        if (!targetPane || !targetPane.classList.contains('tab')) return;

        document.querySelectorAll('#AvisaCustomer .tab').forEach(function (pane) {
            pane.classList.remove('active');
        });

        targetPane.classList.add('active');
        updateAlertVisibility(hash);
        updateBottomNav(hash);

        if (pushHistory) {
            if (window.history && window.history.pushState) {
                window.history.pushState(null, null, hash);
            } else {
                window.location.hash = hash;
            }
        }

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Attach click handlers to all in-page tab triggers
    const tabTriggers = document.querySelectorAll('#AvisaCustomer a[href^="#"]');
    tabTriggers.forEach(function (trigger) {
        trigger.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href && href.startsWith('#') && href.length > 1) {
                const target = document.querySelector(href);
                if (target && target.classList.contains('tab')) {
                    e.preventDefault();
                    switchTab(href, true);
                }
            }
        });
    });

    // Handle hash change from browser forward/backward buttons
    window.addEventListener('hashchange', function () {
        const hash = window.location.hash;
        if (hash) {
            switchTab(hash, false);
        } else {
            switchTab('#summary', false);
        }
    });

    // Initial load from URL hash
    const initialHash = window.location.hash || '#summary';
    switchTab(initialHash, false);
});
