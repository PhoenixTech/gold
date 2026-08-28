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
});
