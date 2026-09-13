// Header & Mobile Nav JS
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

    // Zar Menu Drawer Toggle
    const zarMenu = document.getElementById('zar-menu');
    const openZar1 = document.getElementById('open-zar-1');
    const openZar2 = document.getElementById('open-zar-2');
    const closeZar = document.getElementById('close-zar-btn');

    function openZar() {
        if (zarMenu) {
            zarMenu.style.display = 'block';
            setTimeout(function () {
                document.addEventListener('click', handleZarOutsideClick);
            }, 50);
        }
    }

    function closeZarMenu() {
        if (zarMenu) {
            zarMenu.style.display = 'none';
            document.removeEventListener('click', handleZarOutsideClick);
        }
    }

    function handleZarOutsideClick(e) {
        const zarUl = document.querySelector('#zar-menu ul');
        if (zarUl && !zarUl.contains(e.target) && !e.target.closest('#open-zar-1') && !e.target.closest('#open-zar-2')) {
            closeZarMenu();
        }
    }

    if (openZar1) openZar1.addEventListener('click', openZar);
    if (openZar2) openZar2.addEventListener('click', openZar);
    if (closeZar) closeZar.addEventListener('click', closeZarMenu);
});
