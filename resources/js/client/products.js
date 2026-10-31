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
        const sliderContainer = document.querySelector('#aria-img-slider');

        if (sliderContainer) {
            tns({
                container: '#aria-img-slider',
                items: 3,
                autoplay: false,
                controls: false,
                nav: false,
                mouseDrag: true,
                gutter: 8,
                slideBy: 1,
            });

            sliderContainer.querySelectorAll('a')?.forEach(function (el) {
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
        }

        const commentBtn = document.querySelector('.comment-btn');
        const commentsCollapse = document.querySelector('#comments-collapse');
        if (commentBtn && commentsCollapse && window.bootstrap) {
            commentBtn.addEventListener('click', function () {
                window.bootstrap.Collapse.getOrCreateInstance(commentsCollapse).show();
            });
        }

        const replyBtns = document.querySelectorAll('.comment-reply');
        const parentIdInput = document.querySelector('#parent_id');
        const replyBadge = document.querySelector('#replying-to-badge');
        const replyName = document.querySelector('#reply-to-name');
        const cancelReplyBtn = document.querySelector('#cancel-reply-btn');
        const commentMessage = document.querySelector('#comment-message');

        if (replyBtns.length && parentIdInput) {
            replyBtns.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const id = btn.getAttribute('data-id');
                    const name = btn.getAttribute('data-name');
                    parentIdInput.value = id;
                    if (replyBadge && replyName) {
                        replyName.textContent = name;
                        replyBadge.classList.remove('d-none');
                        replyBadge.classList.add('d-flex');
                    }
                    if (commentsCollapse && window.bootstrap) {
                        window.bootstrap.Collapse.getOrCreateInstance(commentsCollapse).show();
                    }
                    if (commentMessage) {
                        commentMessage.focus();
                        commentMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                });
            });

            cancelReplyBtn?.addEventListener('click', function () {
                parentIdInput.value = '';
                if (replyBadge) {
                    replyBadge.classList.add('d-none');
                    replyBadge.classList.remove('d-flex');
                }
            });
        }
    } catch (e) {
        console.error(e);
    }
});
