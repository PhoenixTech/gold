document.addEventListener('DOMContentLoaded', function () {
    const mobileDrawerEl = document.querySelector('#mobileNavDrawer');
    if (mobileDrawerEl) {
        mobileDrawerEl.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                const offcanvasInstance = window.bootstrap?.Offcanvas?.getInstance(mobileDrawerEl);
                if (offcanvasInstance) {
                    offcanvasInstance.hide();
                }
            });
        });
    }

    const zarMenu = document.getElementById('zar-menu');
    const openZar1 = document.getElementById('open-zar-1');
    const openZar2 = document.getElementById('open-zar-2');
    const closeZar = document.getElementById('close-zar-btn');

    function openZar(e) {
        if (!zarMenu) return;
        zarMenu.classList.add('is-open');
        zarMenu.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        if (e && (e.currentTarget === openZar2 || (e.target && e.target.closest('#open-zar-2')))) {
            setTimeout(function () {
                const searchInput = document.getElementById('zar-search-input');
                if (searchInput) {
                    searchInput.focus();
                }
            }, 120);
        }
    }

    function closeZarMenu() {
        if (!zarMenu) return;
        zarMenu.classList.remove('is-open');
        zarMenu.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    function handleZarKeydown(e) {
        if (e.key === 'Escape' && zarMenu && zarMenu.classList.contains('is-open')) {
            closeZarMenu();
        }
    }

    if (openZar1) {
        openZar1.addEventListener('click', openZar);
    }
    if (openZar2) {
        openZar2.addEventListener('click', openZar);
    }
    if (closeZar) {
        closeZar.addEventListener('click', closeZarMenu);
    }
    if (zarMenu) {
        zarMenu.addEventListener('click', function (e) {
            const panel = zarMenu.querySelector('.zar-drawer-panel');
            if (panel && !panel.contains(e.target)) {
                closeZarMenu();
            }
        });
    }
    document.addEventListener('keydown', handleZarKeydown);
});
