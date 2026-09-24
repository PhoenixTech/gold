document.addEventListener('DOMContentLoaded', () => {
    function activateTab(href, push = true) {
        if (!href || !href.startsWith('#') || href.length < 2) {
            return;
        }

        const target = document.querySelector(href);
        const link = document.querySelector(`.tab-control a[href="${href}"]`);
        if (!target || !link) {
            return;
        }

        document.querySelectorAll('.tab-control a.active').forEach((el) => {
            el.classList.remove('active');
        });
        document.querySelectorAll('.tab, .tab-content').forEach((el) => {
            el.classList.remove('active');
        });

        link.classList.add('active');
        target.classList.add('active');

        if (push) {
            if (window.history?.pushState) {
                window.history.pushState(null, null, href);
            } else {
                window.location.hash = href;
            }
        }
    }

    document.addEventListener('click', (e) => {
        const link = e.target.closest('.tab-control a');
        if (!link) {
            return;
        }
        const href = link.getAttribute('href');
        if (href && href.startsWith('#') && href.length > 1) {
            e.preventDefault();
            activateTab(href, true);
        }
    });

    activateTab(window.location.hash, false);

    window.addEventListener('hashchange', () => {
        activateTab(window.location.hash, false);
    });
});
