// WordPress-like panel sidebar behaviour.
//
// Desktop (>= 992px): the sidebar shows an icon + label per item and
// clicking a group expands/collapses its submenu inline (accordion).
// Small screens: the sidebar collapses to an icon rail and clicking an
// item opens its submenu in a flyout panel (#sidebar-panel).
const SIDEBAR_PANEL = '#sidebar-panel';
const DESKTOP_BREAKPOINT = 992;

let flyoutDismissBound = false;

const isDesktop = function () {
    return window.innerWidth >= DESKTOP_BREAKPOINT;
};

const flyoutDismiss = function (e) {
    if (e.target.closest('aside') || e.target.closest('#sidebar-panel')) {
        return;
    }
    document.querySelector('#panel')?.classList.remove('sided');
    document.querySelector('main')?.classList.remove('blured');
    document.removeEventListener('click', flyoutDismiss);
    flyoutDismissBound = false;
};

const showFlyout = function (href) {
    const source = document.querySelector(href);
    if (!source) {
        return;
    }
    document.querySelector('#sidebar-panel').innerHTML = source.outerHTML;
    document.querySelector('#panel').classList.add('sided');
    document.querySelector('main').classList.add('blured');
    if (!flyoutDismissBound) {
        flyoutDismissBound = true;
        setTimeout(function () {
            document.addEventListener('click', flyoutDismiss);
        }, 50);
    }
};

// Classic accordion: only one sibling group stays open at a time.
const toggleAccordion = function (li) {
    const wasOpen = li.classList.contains('open');
    li.parentElement.querySelectorAll(':scope > li.open').forEach(function (item) {
        item.classList.remove('open');
    });
    if (!wasOpen) {
        li.classList.add('open');
    }
};

window.addEventListener('load', function () {

    try {

        const nav = document.querySelector('#panel-navbar');
        if (!nav) {
            return;
        }

        // Clicking a top-level group toggles its submenu.
        nav.querySelectorAll(':scope > ul > li > a').forEach(function (el) {
            el.addEventListener('click', function (e) {
                const href = (this.getAttribute('href') || '').trim();
                if (href[0] === '#') {
                    e.preventDefault();
                    const li = this.parentElement;
                    if (isDesktop()) {
                        toggleAccordion(li);
                    } else {
                        showFlyout(href);
                    }
                }
                // Real links navigate normally.
            });
        });

        // Highlight the item matching the current URL and open its group.
        const path = window.location.pathname;
        nav.querySelectorAll('a[href]').forEach(function (el) {
            const href = el.getAttribute('href').trim();
            if (href[0] === '#') {
                return;
            }
            try {
                const target = new URL(href, window.location.origin);
                if (target.pathname === path) {
                    el.classList.add('active');
                }
            } catch (err) {
                // ignore malformed hrefs
            }
        });

        nav.querySelectorAll(':scope > ul > li').forEach(function (li) {
            if (li.querySelector('ul a.active')) {
                li.classList.add('open', 'active-group');
            }
        });

        // When resizing back to desktop, close any open flyout.
        window.addEventListener('resize', function () {
            if (isDesktop()) {
                document.querySelector('#panel')?.classList.remove('sided');
                document.querySelector('main')?.classList.remove('blured');
                document.removeEventListener('click', flyoutDismiss);
                flyoutDismissBound = false;
            }
        });

    } catch (e) {
        console.log(e.message);
    }

});

// Dedicated dropdown click handler for admin dashboard navbar & general dropdowns
document.addEventListener('click', function (e) {
    const dropdownToggle = e.target.closest('[data-bs-toggle="dropdown"]');
    if (dropdownToggle) {
        const parent = dropdownToggle.closest('.dropdown, .nav-item.dropdown');
        if (parent) {
            const menu = parent.querySelector('.dropdown-menu');
            if (menu) {
                e.preventDefault();
                e.stopPropagation();

                const willOpen = !menu.classList.contains('show');

                // Close other open dropdowns
                document.querySelectorAll('.dropdown-menu.show').forEach(function (m) {
                    if (m !== menu) m.classList.remove('show');
                });
                document.querySelectorAll('[data-bs-toggle="dropdown"].show').forEach(function (t) {
                    if (t !== dropdownToggle) {
                        t.classList.remove('show');
                        t.setAttribute('aria-expanded', 'false');
                    }
                });

                if (willOpen) {
                    menu.classList.add('show');
                    dropdownToggle.classList.add('show');
                    dropdownToggle.setAttribute('aria-expanded', 'true');
                } else {
                    menu.classList.remove('show');
                    dropdownToggle.classList.remove('show');
                    dropdownToggle.setAttribute('aria-expanded', 'false');
                }
            }
        }
        return;
    }

    // Close open dropdown menus when clicking outside
    if (!e.target.closest('.dropdown-menu')) {
        document.querySelectorAll('.dropdown-menu.show').forEach(function (m) {
            m.classList.remove('show');
        });
        document.querySelectorAll('[data-bs-toggle="dropdown"].show').forEach(function (t) {
            t.classList.remove('show');
            t.setAttribute('aria-expanded', 'false');
        });
    }
});

// Mobile navbar toggler support for admin top navbar
document.addEventListener('click', function (e) {
    const toggler = e.target.closest('[data-bs-toggle="collapse"]');
    if (toggler) {
        const targetSelector = toggler.getAttribute('data-bs-target');
        if (targetSelector) {
            const targetEl = document.querySelector(targetSelector);
            if (targetEl) {
                e.preventDefault();
                targetEl.classList.toggle('show');
                const isExpanded = targetEl.classList.contains('show');
                toggler.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
            }
        }
    }
});
