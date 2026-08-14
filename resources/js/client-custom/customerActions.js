window.addEventListener('load', function () {

    const favInput = document.querySelector('#api-fav-toggle');
    const compInput = document.querySelector('#api-compare-toggle');
    const favUrl = favInput ? favInput.value : '/product/fav/toggle/';
    const compUrl = compInput ? compInput.value : '/product/compare/toggle/';

    document.querySelectorAll('.fav-btn')?.forEach(function (el) {
        el.addEventListener('click', async function () {
            try {
                let resp = await axios.get(favUrl + this.getAttribute('data-slug'));
                if (resp.data.OK) {
                    this.setAttribute('data-is-fav', resp.data.data);
                    window.$toast?.success(resp.data.message);
                } else {
                    window.$toast?.error(resp.data.message || "خطا در ثبت علاقه‌مندی");
                }
            } catch (err) {
                const msg = err.response?.data?.message || "خطا در برقراری ارتباط";
                window.$toast?.error(msg);
            }
        });
    });

    document.querySelectorAll('.compare-btn')?.forEach(function (el) {
        el.addEventListener('click', async function () {
            try {
                let resp = await axios.get(compUrl + this.getAttribute('data-slug'));
                if (resp.data.OK) {
                    window.$toast?.success(resp.data.message);
                } else {
                    window.$toast?.error(resp.data.message || "خطا در افزودن به مقایسه");
                }
            } catch (err) {
                const msg = err.response?.data?.message || "خطا در برقراری ارتباط";
                window.$toast?.error(msg);
            }
        });
    });

    document.querySelectorAll('.add-to-card')?.forEach(function (el) {
        el.addEventListener('click', async function (e) {
            e.preventDefault();
            const btn = this;
            const originalHtml = btn.innerHTML;
            btn.classList.add('disabled');

            try {
                let resp = await axios.get(btn.getAttribute('href'));
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
                btn.classList.remove('disabled');
            }
        });
    });

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
