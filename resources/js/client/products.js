// Products Listing & Single Product JS
import Lightbox from 'bs5-lightbox';
import { tns } from "tiny-slider/src/tiny-slider";

window.addEventListener('load', function () {
    document.querySelectorAll('#product-list-view nav .pagination .page-link')?.forEach(function (el) {
        const href = el.getAttribute('href');
        if (href && !href.includes('#product-list-view')) {
            el.setAttribute('href', href + '#product-list-view');
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    try {
        for (const el of document.querySelectorAll('.light-box')) {
            el.addEventListener('click', Lightbox.initialize);
        }

        if (document.querySelector('#aria-img-slider')) {
            tns({
                container: '#aria-img-slider',
                items: 3,
                autoplay: true,
                autoplayButton: false,
                controls: false,
                autoplayHoverPause: true,
                mouseDrag: true,
                gutter: 5,
                slideBy: 1,
                autoplayTimeout: 5000,
            });
        }

        if (document.querySelector('#rel-products')) {
            tns({
                container: '#rel-products',
                items: 3,
                autoplay: true,
                autoplayButton: false,
                controls: false,
                autoplayHoverPause: true,
                mouseDrag: true,
                gutter: 5,
                slideBy: 1,
                autoplayTimeout: 5000,
                responsive: {
                    560: { items: 1 },
                    768: { items: 2 },
                    1000: { items: 4 },
                    1400: { items: 5 },
                }
            });
        }

        document.querySelectorAll('#aria-img-slider a')?.forEach(function (el) {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                const mainImgA = document.querySelector('#aria-main-img');
                const mainImg = document.querySelector('#aria-main-img img');
                const thumbImg = el.querySelector('img');
                if (mainImgA && mainImg && thumbImg) {
                    mainImgA.setAttribute('href', el.getAttribute('href'));
                    mainImg.setAttribute('src', thumbImg.getAttribute('src'));
                }
            });
        });
    } catch (e) {
        console.error(e);
    }
});
