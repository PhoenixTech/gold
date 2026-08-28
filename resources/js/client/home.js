// Homepage Category Tabs JS
function initHomepageTabs() {
    const tabButtons = document.querySelectorAll('#wtf-main-btns .main-dir');
    if (!tabButtons.length) return;

    tabButtons.forEach(function (el) {
        el.addEventListener('click', function (e) {
            e.preventDefault();

            tabButtons.forEach(function (btn) {
                btn.classList.remove('active');
            });
            this.classList.add('active');

            const sections = document.querySelectorAll('.wtf-section');
            sections.forEach(function (sec) {
                sec.style.display = 'none';
            });

            const targetId = this.getAttribute('data-id');
            if (targetId) {
                const target = document.querySelector(targetId);
                if (target) {
                    target.style.display = 'block';
                }
            }
        });
    });
}

// Homepage Section Scroll Reveal Animation
function initScrollReveal() {
    const revealElements = document.querySelectorAll('.reveal-on-scroll');
    if (!revealElements.length) return;

    // Immediately reveal anything near or above the viewport on initial load
    revealElements.forEach(function (el) {
        const rect = el.getBoundingClientRect();
        if (rect.top <= (window.innerHeight || document.documentElement.clientHeight) + 50) {
            el.classList.add('is-revealed');
        }
    });

    if ((window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) || !('IntersectionObserver' in window)) {
        revealElements.forEach(function (el) {
            el.classList.add('is-revealed');
        });
        return;
    }

    const observer = new IntersectionObserver(function (entries, obs) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-revealed');
                obs.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.05,
        rootMargin: '0px 0px 80px 0px'
    });

    revealElements.forEach(function (el) {
        if (!el.classList.contains('is-revealed')) {
            observer.observe(el);
        }
    });
}

function initHomePage() {
    initHomepageTabs();
    initScrollReveal();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initHomePage);
} else {
    initHomePage();
}
