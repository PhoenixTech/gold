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
                    <input v-model="auth.code" type="text" dir="ltr" maxlength="6" placeholder="------">
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
                <div class="row g-2">
                    <div class="col-12 col-md-6">
                        <label>
                            {{ t('state', 'استان') }}
                            <select v-model="auth.state_id" @change="onAuthStateChange">
                                <option :value="null" disabled>{{ t('select-state', 'انتخاب استان') }}</option>
                                <option v-for="s in statesList" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </label>
                    </div>
                    <div class="col-12 col-md-6">
                        <label>
                            {{ t('city', 'شهر') }}
                            <select v-model="auth.city_id" :disabled="!citiesList.length || loadingCities">
                                <option :value="null" disabled>{{ loadingCities ? t('loading', 'در حال بارگذاری...') : t('select-city', 'انتخاب شهر') }}</option>
                                <option v-for="c in citiesList" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </label>
                    </div>
                </div>
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
            <div class="row g-2">
                <div class="col-12 col-md-6">
                    <label>
                        {{ t('name', 'نام') }}
                        <input v-model="profileForm.name" type="text" :placeholder="t('name-ph', 'نام و نام خانوادگی')">
                    </label>
                </div>
                <div class="col-12 col-md-6">
                    <label>
                        {{ t('mobile', 'موبایل') }}
                        <input v-model="profileForm.mobile" type="tel" dir="ltr" placeholder="09xxxxxxxxx" maxlength="11">
                    </label>
                </div>
            </div>

            <div class="row g-2">
                <div class="col-12 col-md-6">
                    <label>
                        {{ t('state', 'استان') }}
                        <select v-model="profileForm.state_id" @change="onStateChange">
                            <option :value="null" disabled>{{ t('select-state', 'انتخاب استان') }}</option>
                            <option v-for="s in statesList" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </label>
                </div>
                <div class="col-12 col-md-6">
                    <label>
                        {{ t('city', 'شهر') }}
                        <select v-model="profileForm.city_id" :disabled="!citiesList.length || loadingCities">
                            <option :value="null" disabled>{{ loadingCities ? t('loading', 'در حال بارگذاری...') : t('select-city', 'انتخاب شهر') }}</option>
                            <option v-for="c in citiesList" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </label>
                </div>
            </div>
            <label>
                {{ t('address', 'آدرس') }}
                <textarea v-model="profileForm.address" rows="3" :placeholder="t('address-ph', 'آدرس کامل تحویل')"></textarea>
            </label>
            <div class="row g-2">
                <div class="col-12 col-md-6">
                    <label>
                        {{ t('post-code', 'کد پستی') }}
                        <input v-model="profileForm.zip" type="text" dir="ltr" maxlength="10" :placeholder="t('post-code-ph', 'کد پستی ۱۰ رقمی (اختیاری)')">
                    </label>
                </div>
            </div>

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
        states: { type: Array, default: () => [] },
        stateLink: { type: String, default: '' },
        citiesLink: { type: String, default: '' },
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
        statesList: [],
        citiesList: [],
        loadingCities: false,
        auth: {
            name: '',
            mobile: '',
            email: '',
            password: '',
            state_id: null,
            city_id: null,
            address: '',
            zip: '',
            code: '',
        },
    }),
    watch: {
        states: {
            immediate: true,
            handler(val) {
                if (val && val.length) {
                    this.statesList = [...val];
                }
            },
        },
        'profileForm.state_id'(newVal) {
            if (newVal) {
                this.loadCities(newVal);
            } else {
                this.citiesList = [];
            }
        },
    },
    async mounted() {
        if (this.states && this.states.length) {
            this.statesList = [...this.states];
        } else if (this.stateLink) {
            await this.loadStates();
        }
        if (this.profileForm.state_id) {
            await this.loadCities(this.profileForm.state_id);
        }
    },
    methods: {
        async loadStates() {
            if (!this.stateLink) return;
            try {
                const resp = await axios.get(this.stateLink);
                this.statesList = resp.data.data || resp.data || [];
            } catch (e) {
                this.statesList = [];
            }
        },
        async loadCities(stateId) {
            if (!stateId || !this.citiesLink) {
                this.citiesList = [];
                return;
            }
            this.loadingCities = true;
            try {
                const resp = await axios.get(`${this.citiesLink}/${stateId}`);
                this.citiesList = resp.data.data || resp.data || [];
            } catch (e) {
                this.citiesList = [];
            } finally {
                this.loadingCities = false;
            }
        },
        onStateChange() {
            this.profileForm.city_id = null;
            if (this.profileForm.state_id) {
                this.loadCities(this.profileForm.state_id);
            } else {
                this.citiesList = [];
            }
        },
        onAuthStateChange() {
            this.auth.city_id = null;
            if (this.auth.state_id) {
                this.loadCities(this.auth.state_id);
            } else {
                this.citiesList = [];
            }
        },
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
                const payload = {
                    name: this.auth.name,
                    mobile: this.auth.mobile,
                    email: this.auth.email,
                    address: this.auth.address,
                    embed: 1,
                };
                if (this.auth.state_id) {
                    payload.state_id = this.auth.state_id;
                }
                if (this.auth.city_id) {
                    payload.city_id = this.auth.city_id;
                }
                const resp = await axios.post(this.signUpNowUrl, payload, {
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
        async completeProfile() {
            if (!this.profileForm.name || this.profileForm.name.trim().length < 2) {
                window.$toast?.error?.(this.t('name-required', 'نام و نام خانوادگی را وارد کنید'));
                return;
            }
            if (!/^09\d{9}$/.test(this.profileForm.mobile?.trim() || '')) {
                window.$toast?.error?.(this.t('mobile-invalid', 'فرمت شماره موبایل معتبر نیست'));
                return;
            }
            if (this.deliveryType !== 'pickup' || !this.localAddresses.length || this.profileForm.address) {
                if (!this.profileForm.state_id) {
                    window.$toast?.error?.(this.t('state-required', 'انتخاب استان الزامی است'));
                    return;
                }
                if (!this.profileForm.city_id) {
                    window.$toast?.error?.(this.t('city-required', 'انتخاب شهر الزامی است'));
                    return;
                }
                if (!this.profileForm.address || this.profileForm.address.trim().length < 5) {
                    window.$toast?.error?.(this.t('address-required', 'نشانی الزامی است'));
                    return;
                }
            }
            this.authBusy = true;
            try {
                const payload = {
                    name: this.profileForm.name.trim(),
                    mobile: this.profileForm.mobile.trim(),
                    for_pickup: this.deliveryType === 'pickup' ? 1 : 0,
                };
                if (this.profileForm.address) {
                    payload.address = this.profileForm.address.trim();
                    payload.state_id = this.profileForm.state_id;
                    payload.city_id = this.profileForm.city_id;
                    if (this.profileForm.zip) {
                        payload.zip = this.profileForm.zip.trim();
                    }
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
