// Scoped WordPress-like panel sidebar controller
const DESKTOP_BREAKPOINT = 992;
let flyoutDismissBound = false;

const isDesktop = () => window.innerWidth >= DESKTOP_BREAKPOINT;

const flyoutDismiss = (e) => {
    if (e.target.closest('aside') || e.target.closest('#sidebar-panel')) {
        return;
    }
    document.querySelector('#panel')?.classList.remove('sided');
    document.querySelector('main')?.classList.remove('blured');
    document.removeEventListener('click', flyoutDismiss);
    flyoutDismissBound = false;
};

const showFlyout = (href) => {
    if (!href || href === '#' || !href.startsWith('#')) return;
    const source = document.querySelector(href);
    const sidebarPanel = document.querySelector('#sidebar-panel');
    const panel = document.querySelector('#panel');
    const main = document.querySelector('main');

    if (!source || !sidebarPanel || !panel) return;

    sidebarPanel.innerHTML = source.outerHTML;
    panel.classList.add('sided');
    main?.classList.add('blured');

    if (!flyoutDismissBound) {
        flyoutDismissBound = true;
        setTimeout(() => {
            document.addEventListener('click', flyoutDismiss);
        }, 50);
    }
};

const toggleAccordion = (li) => {
    const wasOpen = li.classList.contains('open');
    li.parentElement.querySelectorAll(':scope > li.open').forEach((item) => {
        item.classList.remove('open');
    });
    if (!wasOpen) {
        li.classList.add('open');
    }
};

export function initNavbar() {
    const nav = document.querySelector('#panel-navbar');
    if (!nav) return;

    // Toggle submenu on click
    nav.querySelectorAll(':scope > ul > li > a').forEach((el) => {
        el.addEventListener('click', function (e) {
            const href = (this.getAttribute('href') || '').trim();
            if (href.startsWith('#') && href.length > 1) {
                e.preventDefault();
                const li = this.parentElement;
                if (isDesktop()) {
                    toggleAccordion(li);
                } else {
                    showFlyout(href);
                }
            }
        });
    });

    // Highlight current URL
    const path = window.location.pathname;
    nav.querySelectorAll('a[href]').forEach((el) => {
        const href = (el.getAttribute('href') || '').trim();
        if (href.startsWith('#')) return;
        try {
            const target = new URL(href, window.location.origin);
            if (target.pathname === path) {
                el.classList.add('active');
            }
        } catch {
            // Ignore malformed URLs
        }
    });

    nav.querySelectorAll(':scope > ul > li').forEach((li) => {
        if (li.querySelector('ul a.active')) {
            li.classList.add('open', 'active-group');
        }
    });

    // Handle viewport resizing
    window.addEventListener('resize', () => {
        if (isDesktop()) {
            document.querySelector('#panel')?.classList.remove('sided');
            document.querySelector('main')?.classList.remove('blured');
            document.removeEventListener('click', flyoutDismiss);
            flyoutDismissBound = false;
        }
    });
}
