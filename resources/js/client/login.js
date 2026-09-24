import axios from 'axios';

function isValidMobile(p) {
    return /^(\+|[0-9])[0-9]{9,14}$/.test(p);
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelector('#send-auth-code')?.addEventListener('click', async function () {
        try {
            const url = this.getAttribute('data-route');
            const tel = document.querySelector('#tel')?.value || '';
            if (tel.length < 11 || !isValidMobile(tel)) {
                window.$toast?.error('Invalid mobile');
                return;
            }

            const resp = await axios.get(url + '?tel=' + encodeURIComponent(tel));
            if (resp.data.OK) {
                window.$toast?.success(resp.data.message);
                document.querySelector('#tel')?.setAttribute('readonly', '');
                const notSend = document.querySelector('.not-send');
                const sent = document.querySelector('.sent');
                if (notSend) notSend.style.display = 'block';
                if (sent) sent.style.display = 'none';
            } else {
                window.$toast?.error(resp.data.message);
            }
        } catch (e) {
            window.$toast?.error(e.message);
        }
    });

    document.querySelector('#send-auth-check')?.addEventListener('click', async function () {
        try {
            const url = this.getAttribute('data-route');
            const tel = document.querySelector('#tel')?.value || '';
            const code = document.querySelector('#auth')?.value || '';
            if (tel.length < 11 || !isValidMobile(tel)) {
                window.$toast?.error('Invalid mobile');
                return;
            }
            if (code.length !== 5) {
                window.$toast?.error('Invalid code');
                return;
            }

            const resp = await axios.get(url + '?tel=' + encodeURIComponent(tel) + '&code=' + encodeURIComponent(code));
            if (resp.data.OK) {
                window.$toast?.success(resp.data.message);
                setTimeout(() => {
                    window.location.href = resp.data.redirect || this.getAttribute('data-profile');
                }, 1200);
            } else {
                window.$toast?.error(resp.data.message);
            }
        } catch (e) {
            window.$toast?.error(e.message);
        }
    });
});
