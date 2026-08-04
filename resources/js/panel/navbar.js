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
