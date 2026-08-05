document.addEventListener('DOMContentLoaded', function () {
    if (document.querySelectorAll('.tab-control a').length > 0) {
        document.querySelectorAll('.tab-control a')?.forEach(function (el) {

            el.addEventListener('click', function () {
                try {
                    document.querySelector('.tab-control a.active')?.classList.remove('active');
                    this.classList.add('active');
                    const targetContent = document.querySelector(this.getAttribute('href'));
                    if (targetContent) {
                        document.querySelector('.tab.active,.tab-content.active')?.classList.remove('active');
                        targetContent.classList.add('active');
                    }
                } catch (e) {
                }

            });
        });
    }

    function activateTabFromHash() {
        const hash = window.location.hash;
        if (hash && hash.startsWith('#')) {
            try {
                const tabLink = document.querySelector(`.tab-control a[href="${hash}"]`);
                if (tabLink && !tabLink.classList.contains('active')) {
                    tabLink.click();
                }
            } catch (e) {
            }
        }
    }

    activateTabFromHash();

    window.addEventListener('hashchange', function () {
        activateTabFromHash();
    });
});

