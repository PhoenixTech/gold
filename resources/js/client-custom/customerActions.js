import axios from 'axios';

window.axios = axios;

window.addEventListener('load', function () {

    function makeActionUrl(selector, fallback, slug) {
        const input = document.querySelector(selector);
        const base = (input && input.value ? input.value : fallback).replace(/\/+$/, '');
        return base + '/' + encodeURIComponent(slug);
    }

    async function copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            try {
                await navigator.clipboard.writeText(text);
                return true;
            } catch (e) {
                // Fallback below
            }
        }
        try {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.top = '-9999px';
            textarea.style.left = '-9999px';
            textarea.setAttribute('readonly', '');
            document.body.appendChild(textarea);
            textarea.focus();
            textarea.select();
            const success = document.execCommand('copy');
            document.body.removeChild(textarea);
            return success;
        } catch (err) {
            return false;
        }
    }

    async function handleProductToggle({ slug, btnSelector, actionSelector, fallbackUrl, attrName, successFallback }) {
        try {
            const url = makeActionUrl(actionSelector, fallbackUrl, slug);
            const resp = await axios.get(url);
            if (resp.data && resp.data.OK) {
                const newVal = String(resp.data.data);
                document.querySelectorAll(`${btnSelector}[data-slug="${slug}"]`)?.forEach(b => {
                    b.setAttribute(attrName, newVal);
                });
                window.$toast?.success(resp.data.message || successFallback);
            } else {
                window.$toast?.error(resp.data?.message || "خطا در پردازش درخواست");
            }
        } catch (err) {
            const status = err.response?.status;
            const isAuthErr = status === 401 || status === 403;
            const msg = err.response?.data?.message || (isAuthErr ? "لطفا ابتدا وارد حساب کاربری خود شوید" : "خطا در برقراری ارتباط با سرور");
            if (isAuthErr) {
                window.$toast?.warning(msg);
            } else {
                window.$toast?.error(msg);
            }
        }
    }

    function renderCommentAlert(form, type, message) {
        const iconClass = type === 'success' ? 'ri-checkbox-circle-fill text-success' : 'ri-error-warning-fill text-danger';
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show comment-feedback-alert my-3 d-flex align-items-center gap-2`;
        alertDiv.innerHTML = `
            <i class="${iconClass} fs-5"></i>
            <div>${message}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        form.prepend(alertDiv);
        if (type === 'success') {
            alertDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    // Delegated Global Click Listener
    document.addEventListener('click', async function (e) {
        // --- Share Button ---
        const shareBtn = e.target.closest('.share-btn');
        if (shareBtn) {
            e.preventDefault();
            e.stopPropagation();

            const shareUrl = shareBtn.getAttribute('data-url') || window.location.href;
            const shareTitle = shareBtn.getAttribute('data-title') || document.title;

            if (navigator.share) {
                try {
                    await navigator.share({ title: shareTitle, url: shareUrl });
                    window.$toast?.success("اشتراک‌گذاری انجام شد");
                    return;
                } catch (err) {
                    if (err.name === 'AbortError') return;
                }
            }

            const copied = await copyToClipboard(shareUrl);
            if (copied) {
                window.$toast?.success("لینک محصول با موفقیت در کلیپ‌بورد کپی شد");
            } else {
                window.$toast?.info(shareUrl);
            }
            return;
        }

        // --- Like / Favorite Button ---
        const favBtn = e.target.closest('.fav-btn');
        if (favBtn) {
            e.preventDefault();
            e.stopPropagation();
            const slug = favBtn.getAttribute('data-slug');
            if (slug) {
                await handleProductToggle({
                    slug,
                    btnSelector: '.fav-btn',
                    actionSelector: '#api-fav-toggle',
                    fallbackUrl: '/product/fav/toggle',
                    attrName: 'data-is-fav',
                    successFallback: 'علاقه‌مندی به‌روزرسانی شد',
                });
            }
            return;
        }

        // --- Bookmark Button ---
        const bookmarkBtn = e.target.closest('.bookmark-btn');
        if (bookmarkBtn) {
            e.preventDefault();
            e.stopPropagation();
            const slug = bookmarkBtn.getAttribute('data-slug');
            if (slug) {
                await handleProductToggle({
                    slug,
                    btnSelector: '.bookmark-btn',
                    actionSelector: '#api-bookmark-toggle',
                    fallbackUrl: '/product/bookmark/toggle',
                    attrName: 'data-is-bookmarked',
                    successFallback: 'نشان‌شده‌ها به‌روزرسانی شد',
                });
            }
            return;
        }

        // --- Compare Button ---
        const compBtn = e.target.closest('.compare-btn');
        if (compBtn) {
            e.preventDefault();
            e.stopPropagation();
            const slug = compBtn.getAttribute('data-slug');
            if (!slug) return;

            try {
                const url = makeActionUrl('#api-compare-toggle', '/product/compare/toggle', slug);
                const resp = await axios.get(url);
                if (resp.data && resp.data.OK) {
                    window.$toast?.success(resp.data.message);
                } else {
                    window.$toast?.error(resp.data?.message || "خطا در افزودن به مقایسه");
                }
            } catch (err) {
                window.$toast?.error(err.response?.data?.message || "خطا در افزودن به مقایسه");
            }
            return;
        }

        // --- Add to Cart Button ---
        const cartBtn = e.target.closest('.add-to-card');
        if (cartBtn && !cartBtn.classList.contains('disabled')) {
            e.preventDefault();
            e.stopPropagation();

            const targetUrl = cartBtn.getAttribute('href');
            if (!targetUrl) return;

            cartBtn.classList.add('disabled');

            try {
                const resp = await axios.get(targetUrl);
                if (resp.data.OK) {
                    window.$toast?.success(resp.data.message);
                    document.querySelectorAll('.card-count')?.forEach(function (el2) {
                        el2.innerText = resp.data.data.count;
                    });
                } else {
                    window.$toast?.error(resp.data.message || "خطا در افزودن به سبد خرید");
                }
            } catch (err) {
                const errData = err.response?.data;
                const msg = errData?.message || "خطا در افزودن به سبد خرید";
                if (errData?.data?.redirect) {
                    window.$toast?.warning(msg);
                    setTimeout(() => {
                        window.location.href = errData.data.redirect;
                    }, 1200);
                } else {
                    window.$toast?.warning(msg);
                }
            } finally {
                cartBtn.classList.remove('disabled');
            }
        }
    });

    // --- Comment Form Submission Handler ---
    document.addEventListener('submit', async function (e) {
        const commentForm = e.target.closest('#comment-form');
        if (!commentForm) return;

        e.preventDefault();

        const submitBtn = commentForm.querySelector('button[type="submit"]');
        const originalBtnContent = submitBtn ? submitBtn.innerHTML : '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> در حال ارسال...';
        }

        commentForm.querySelectorAll('.comment-feedback-alert')?.forEach(a => a.remove());

        const targetUrl = commentForm.getAttribute('action') || commentForm.querySelector('.safe-url')?.getAttribute('data-url') || '/comment/submit';
        const formData = new FormData(commentForm);

        try {
            const resp = await axios.post(targetUrl, formData);
            if (resp.data.OK) {
                const successMsg = resp.data.message || "دیدگاه شما با موفقیت ثبت شد و پس از بررسی نمایش داده خواهد شد.";
                window.$toast?.success(successMsg);

                const messageInput = commentForm.querySelector('textarea[name="message"]');
                if (messageInput) messageInput.value = '';

                renderCommentAlert(commentForm, 'success', successMsg);
            } else {
                const errorMsg = resp.data.message || "خطا در ثبت دیدگاه";
                window.$toast?.error(errorMsg);
                renderCommentAlert(commentForm, 'danger', errorMsg);
            }
        } catch (err) {
            let errorMsg = err.response?.data?.message || "خطا در برقراری ارتباط با سرور";
            if (err.response?.data?.errors) {
                const firstErr = Object.values(err.response.data.errors)[0];
                if (Array.isArray(firstErr)) {
                    errorMsg = firstErr[0];
                }
            }
            window.$toast?.error(errorMsg);
            renderCommentAlert(commentForm, 'danger', errorMsg);
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnContent;
            }
        }
    });

    // --- Product Rating Form ---
    document.querySelector('#rating-form')?.addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);

        axios.post(this.getAttribute('data-url'), formData)
            .then(response => {
                if (response.data.OK) {
                    window.$toast?.success(response.data.message);
                } else {
                    window.$toast?.error(response.data.error || response.data.message);
                }
            })
            .catch(error => {
                const msg = error.response?.data?.message || error.message;
                window.$toast?.error(msg);
            });
    });

});
