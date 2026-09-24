<template>
    <section class="checkout-panel">
        <header class="panel-head">
            <h5>{{ t('account', 'حساب کاربری') }}</h5>
        </header>

        <template v-if="!loggedIn">
            <div v-if="!smsSign" class="auth-tabs">
                <button type="button" :class="{ active: authTab === 'login' }" @click="authTab = 'login'">
                    {{ t('login', 'ورود') }}
                </button>
                <button type="button" :class="{ active: authTab === 'signup' }" @click="authTab = 'signup'">
                    {{ t('signup', 'ثبت‌نام') }}
                </button>
            </div>

            <div v-if="smsSign" class="auth-form">
                <p class="hint">{{ t('sms-hint', 'با شماره موبایل وارد شوید') }}</p>
                <label>
                    {{ t('mobile', 'موبایل') }}
                    <input v-model="auth.mobile" type="tel" dir="ltr" placeholder="09xxxxxxxxx" maxlength="11">
                </label>
                <label v-if="smsSent">
                    {{ t('auth-code', 'کد تایید') }}
                    <input v-model="auth.code" type="text" dir="ltr" maxlength="5" placeholder="-----">
                </label>
                <button v-if="!smsSent" type="button" class="btn-primary-cta" :disabled="authBusy" @click="sendSms">
                    {{ t('send-code', 'ارسال کد') }}
                </button>
                <button v-else type="button" class="btn-primary-cta" :disabled="authBusy" @click="verifySms">
                    {{ t('verify-code', 'تایید و ادامه') }}
                </button>
            </div>

            <div v-else-if="authTab === 'login'" class="auth-form">
                <label>
                    {{ t('email', 'ایمیل') }}
                    <input v-model="auth.email" type="email" dir="ltr">
                </label>
                <label>
                    {{ t('password', 'رمز عبور') }}
                    <input v-model="auth.password" type="password">
                </label>
                <button type="button" class="btn-primary-cta" :disabled="authBusy" @click="emailLogin">
                    {{ t('login', 'ورود') }}
                </button>
            </div>

            <div v-else class="auth-form">
                <label>
                    {{ t('name', 'نام') }}
                    <input v-model="auth.name" type="text">
                </label>
                <label>
                    {{ t('mobile', 'موبایل') }}
                    <input v-model="auth.mobile" type="tel" dir="ltr" placeholder="09xxxxxxxxx" maxlength="11">
                </label>
                <label>
                    {{ t('email', 'ایمیل') }}
                    <input v-model="auth.email" type="email" dir="ltr">
                </label>
                <label>
                    {{ t('address', 'آدرس') }}
                    <textarea v-model="auth.address" rows="3" :placeholder="t('address-ph', 'آدرس کامل تحویل')"></textarea>
                </label>
                <button type="button" class="btn-primary-cta" :disabled="authBusy" @click="emailSignup">
                    {{ t('signup', 'ثبت‌نام') }}
                </button>
            </div>
        </template>

        <div v-else class="auth-form">
            <p class="hint">{{ t('complete-profile', 'لطفا نام، موبایل و آدرس را تکمیل کنید') }}</p>
            <label>
                {{ t('name', 'نام') }}
                <input v-model="profileForm.name" type="text">
            </label>
            <label>
                {{ t('mobile', 'موبایل') }}
                <input v-model="profileForm.mobile" type="tel" dir="ltr" placeholder="09xxxxxxxxx" maxlength="11">
            </label>
            <label v-if="!localAddresses.length">
                {{ t('address', 'آدرس') }}
                <textarea v-model="profileForm.address" rows="3" :placeholder="t('address-ph', 'آدرس کامل تحویل')"></textarea>
            </label>
            <button type="button" class="btn-primary-cta" :disabled="authBusy" @click="completeProfile">
                {{ t('save-continue', 'ذخیره و ادامه') }}
            </button>
        </div>

        <button type="button" class="btn-ghost mt panel-back" @click="$emit('prev')">{{ t('back', 'بازگشت') }}</button>
    </section>
</template>

<script>
export default {
    name: 'CartAuthStep',
    props: {
        loggedIn: { type: Boolean, default: false },
        smsSign: { type: Boolean, default: false },
        profileForm: { type: Object, required: true },
        localAddresses: { type: Array, default: () => [] },
        deliveryType: { type: String, default: 'address' },
        sendSmsUrl: { type: String, default: '' },
        checkAuthUrl: { type: String, default: '' },
        signInDoUrl: { type: String, default: '' },
        signUpNowUrl: { type: String, default: '' },
        completeProfileUrl: { type: String, default: '' },
        t: { type: Function, required: true },
    },
    emits: ['auth-success', 'prev'],
    data: () => ({
        authTab: 'login',
        smsSent: false,
        authBusy: false,
        auth: {
            name: '',
            mobile: '',
            email: '',
            password: '',
            address: '',
            code: '',
        },
    }),
    methods: {
        async sendSms() {
            if (!/^09\d{9}$/.test(this.auth.mobile)) {
                window.$toast?.error?.(this.t('mobile-invalid', 'فرمت شماره موبایل معتبر نیست'));
                return;
            }
            this.authBusy = true;
            try {
                const resp = await axios.get(this.sendSmsUrl, { params: { tel: this.auth.mobile } });
                if (resp.data.OK) {
                    this.smsSent = true;
                    window.$toast?.success?.(resp.data.message);
                } else {
                    window.$toast?.error?.(resp.data.message || resp.data.error);
                }
            } catch (e) {
                window.$toast?.error?.(e.response?.data?.message || e.message);
            } finally {
                this.authBusy = false;
            }
        },
        async verifySms() {
            this.authBusy = true;
            try {
                const resp = await axios.get(this.checkAuthUrl, {
                    params: { tel: this.auth.mobile, code: this.auth.code },
                });
                if (resp.data.OK) {
                    window.$toast?.success?.(resp.data.message);
                    this.$emit('auth-success', resp.data);
                } else {
                    window.$toast?.error?.(resp.data.message || resp.data.error);
                }
            } catch (e) {
                window.$toast?.error?.(e.response?.data?.message || e.message);
            } finally {
                this.authBusy = false;
            }
        },
        async emailLogin() {
            this.authBusy = true;
            try {
                const resp = await axios.post(this.signInDoUrl, {
                    email: this.auth.email,
                    password: this.auth.password,
                    embed: 1,
                }, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (resp.data.OK) {
                    window.$toast?.success?.(resp.data.message);
                    this.$emit('auth-success', resp.data.data || resp.data);
                } else {
                    window.$toast?.error?.(resp.data.message);
                }
            } catch (e) {
                const msg = e.response?.data?.message
                    || Object.values(e.response?.data?.errors || {})?.[0]?.[0]
                    || e.message;
                window.$toast?.error?.(msg);
            } finally {
                this.authBusy = false;
            }
        },
        async emailSignup() {
            this.authBusy = true;
            try {
                const resp = await axios.post(this.signUpNowUrl, {
                    name: this.auth.name,
                    mobile: this.auth.mobile,
                    email: this.auth.email,
                    address: this.auth.address,
                    embed: 1,
                }, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (resp.data.OK) {
                    window.$toast?.success?.(resp.data.message);
                    this.$emit('auth-success', resp.data.data || resp.data);
                } else {
                    window.$toast?.error?.(resp.data.message);
                }
            } catch (e) {
                const msg = e.response?.data?.message
                    || Object.values(e.response?.data?.errors || {})?.[0]?.[0]
                    || e.message;
                window.$toast?.error?.(msg);
            } finally {
                this.authBusy = false;
            }
        },
        async completeProfile() {
            this.authBusy = true;
            try {
                const payload = {
                    name: this.profileForm.name,
                    mobile: this.profileForm.mobile,
                    for_pickup: this.deliveryType === 'pickup' ? 1 : 0,
                };
                if (!this.localAddresses.length && this.profileForm.address) {
                    payload.address = this.profileForm.address;
                }
                const resp = await axios.post(this.completeProfileUrl, payload, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (resp.data.OK) {
                    window.$toast?.success?.(resp.data.message);
                    this.$emit('auth-success', resp.data.data || resp.data);
                } else {
                    window.$toast?.error?.(resp.data.message);
                }
            } catch (e) {
                const msg = e.response?.data?.message
                    || Object.values(e.response?.data?.errors || {})?.[0]?.[0]
                    || e.message;
                window.$toast?.error?.(msg);
            } finally {
                this.authBusy = false;
            }
        },
    },
}
</script>
