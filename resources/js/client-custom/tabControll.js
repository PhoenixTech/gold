document.addEventListener('DOMContentLoaded', function () {
    const tabLinks = document.querySelectorAll('.tab-control a');
    if (tabLinks.length > 0) {
        tabLinks.forEach(function (el) {
            el.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (href && href.startsWith('#') && href.length > 1) {
                    try {
                        const targetContent = document.querySelector(href);
                        if (targetContent) {
                            e.preventDefault();
                            document.querySelectorAll('.tab-control a.active').forEach(function (link) {
                                link.classList.remove('active');
                            });
                            this.classList.add('active');

                            document.querySelectorAll('.tab, .tab-content').forEach(function (pane) {
                                pane.classList.remove('active');
                            });
                            targetContent.classList.add('active');

                            if (history.pushState) {
                                history.pushState(null, null, href);
                            } else {
                                window.location.hash = href;
                            }
                        }
                    } catch (err) {
                    }
                }
            });
        });
    }

    function activateTabFromHash() {
        const hash = window.location.hash;
        if (hash && hash.startsWith('#') && hash.length > 1) {
            try {
                const tabLink = document.querySelector(`.tab-control a[href="${hash}"]`);
                if (tabLink) {
                    tabLink.click();
                }
            } catch (err) {
            }
        }
    }

    activateTabFromHash();

    window.addEventListener('hashchange', function () {
        activateTabFromHash();
    });
});
