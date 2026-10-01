// Customer Dashboard JS

document.addEventListener('DOMContentLoaded', function () {
    const customerRoot = document.getElementById('AvisaCustomer');
    if (!customerRoot) return;

    window.addEventListener('customer:addresses-updated', function (event) {
        const addressCount = Number(event.detail?.count ?? 0);
        const addressAlert = document.getElementById('avisa-alert-address');
        const addressMarker = customerRoot.querySelector('[data-attention="addresses"]');

        if (addressCount === 0 && !addressAlert) {
            window.location.reload();
            return;
        }

        if (addressCount > 0) {
            addressAlert?.remove();
            addressMarker?.remove();
        }

        const addressCountBadge = customerRoot.querySelector('[data-profile-address-count]');
        if (addressCountBadge) {
            addressCountBadge.textContent = new Intl.NumberFormat(document.documentElement.lang || 'fa-IR').format(addressCount);
        }

        customerRoot.dataset.profileIncomplete = customerRoot.dataset.profileNameMissing === 'true' || addressCount === 0 ? 'true' : 'false';
    });

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
