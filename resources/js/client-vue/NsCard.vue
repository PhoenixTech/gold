<template>
    <div id="card" class="checkout-card">
        <nav class="checkout-progress" aria-label="checkout steps">
            <button
                v-for="(step, i) in steps"
                :key="step.key"
                type="button"
                class="progress-item"
                :class="{ active: index === i, done: index > i }"
                @click="goTo(i)"
            >
                <span class="num">{{ i + 1 }}</span>
                <span class="label">{{ step.label }}</span>
            </button>
        </nav>

        <div class="checkout-layout">
            <div class="checkout-main">
                <!-- Cart -->
                <section v-show="currentKey === 'cart'" class="checkout-panel">
                    <h3>{{ t('cart', 'سبد خرید') }}</h3>
                    <ul class="piece-list">
                        <li v-for="(item, i) in lines" :key="item.id + '-' + i" class="piece-row">
                            <input type="hidden" :name="`product_id[${i}]`" :value="item.id">
                            <input type="hidden" :name="`count[${i}]`" :value="countz[i]">
                            <input
                                v-if="item.q"
                                type="hidden"
                                :name="`quantity_id[${i}]`"
                                :value="item.q.id"
                            >
                            <input v-else type="hidden" :name="`quantity_id[${i}]`" value="">

                            <img :src="item.image" :alt="item.name" class="piece-img">
                            <div class="piece-body">
                                <a class="piece-name" :href="productLink + item.slug">{{ item.name }}</a>
                                <template v-if="item.q">
                                    <q-preview
                                        :q="item.q"
                                        :weight-label="t('weight', 'وزن')"
                                        :code-label="t('code', 'کد')"
                                    ></q-preview>
                                </template>
                                <p v-else class="piece-missing">
                                    {{ t('piece-missing', 'قطعه انتخاب نشده') }}
                                    —
                                    <a :href="productLink + item.slug">{{ t('choose-piece', 'انتخاب قطعه') }}</a>
                                </p>
                                <strong class="piece-price">{{ priceing(pricez[i]) }}</strong>
                            </div>
                            <a class="piece-remove" :href="cardLink + item.slug" :title="t('remove', 'حذف')">
                                <i class="ri-close-line"></i>
                            </a>
                        </li>
                    </ul>
                    <button type="button" class="btn-primary-cta" @click="next">
                        {{ t('continue', 'ادامه') }}
                    </button>
                </section>

                <!-- Account -->
                <section v-show="currentKey === 'account'" class="checkout-panel">
                    <h3>{{ t('account', 'حساب کاربری') }}</h3>

                    <template v-if="!loggedIn">
                        <div v-if="!smsSign" class="auth-tabs">
                            <button type="button" :class="{ active: authTab === 'login' }" @click="authTab = 'login'">
                                {{ t('login', 'ورود') }}
                            </button>
                            <button type="button" :class="{ active: authTab === 'signup' }" @click="authTab = 'signup'">
                                {{ t('signup', 'ثبت‌نام') }}
                            </button>
                        </div>

                        <!-- SMS login -->
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

                        <!-- Email login -->
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

                        <!-- Email signup -->
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

                    <!-- Complete profile -->
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

                    <button type="button" class="btn-ghost mt" @click="prev">{{ t('back', 'بازگشت') }}</button>
                </section>

                <!-- Delivery -->
                <section v-show="currentKey === 'delivery'" class="checkout-panel">
                    <h3>{{ t('transport', 'ارسال') }}</h3>

                    <h4>{{ t('sent-to', 'ارسال به') }}</h4>
                    <div v-if="!localAddresses.length" class="inline-address auth-form">
                        <p class="hint">{{ t('no-address', 'آدرسی ثبت نشده است.') }}</p>
                        <label>
                            {{ t('address', 'آدرس') }}
                            <textarea v-model="profileForm.address" rows="3"></textarea>
                        </label>
                        <button type="button" class="btn-secondary-cta" :disabled="authBusy" @click="addAddressQuick">
                            {{ t('add-address', 'افزودن آدرس') }}
                        </button>
                    </div>
                    <div v-for="adr in localAddresses" :key="adr.id" class="choice">
                        <label>
                            <input type="radio" name="address_id" :value="adr.id" v-model="selectedAddressId">
                            <span>{{ adr.address }}</span>
                        </label>
                    </div>

                    <h4 class="mt">{{ t('transport', 'ارسال') }}</h4>
                    <div v-for="trs in transports" :key="trs.id" class="choice choice-transport">
                        <label>
                            <input type="radio" name="transport_id" :value="trs.id" v-model="transport_index">
                            <span>
                                <strong>{{ trs.title }}</strong>
                                <small v-if="trs.description">{{ trs.description }}</small>
                            </span>
                            <em>{{ priceing(trs.price) }}</em>
                        </label>
                    </div>

                    <div class="row-actions">
                        <button type="button" class="btn-ghost" @click="prev">{{ t('back', 'بازگشت') }}</button>
                        <button type="button" class="btn-primary-cta" @click="next">{{ t('continue', 'ادامه') }}</button>
                    </div>
                </section>

                <!-- Payment -->
                <section v-show="currentKey === 'payment'" class="checkout-panel">
                    <h3>{{ t('payment', 'پرداخت') }}</h3>

                    <div class="brief-invoice">
                        <div><span>{{ t('products-total', 'جمع کالاها') }}</span><strong>{{ priceing(productsTotalAfterDiscount) }}</strong></div>
                        <div><span>{{ t('transport', 'ارسال') }}</span><strong>{{ priceing(transportPrice) }}</strong></div>
                        <div class="total"><span>{{ t('total-price', 'مبلغ کل') }}</span><strong>{{ priceing(totalWithTransportDiscount) }}</strong></div>
                    </div>

                    <h4>{{ t('check-dis', 'بررسی تخفیف') }}</h4>
                    <div class="discount-row">
                        <input type="text" :placeholder="t('discount-code', 'کد تخفیف')"
                               :readonly="discount != null" v-model="code">
                        <button type="button" class="btn-secondary-cta" @click="discountCheck">{{ t('check', 'بررسی') }}</button>
                    </div>
                    <div v-if="discount_id != null">
                        <input type="hidden" name="discount_id" :value="discount_id">
                        <p class="ok-msg">{{ discount_human }}</p>
                    </div>

                    <h4>{{ t('extra-desc', 'توضیحات سفارش') }}</h4>
                    <textarea rows="3" class="full" name="desc" :placeholder="t('your-msg', 'پیام شما برای این سفارش...')"></textarea>

                    <h4>{{ t('pay-method', 'روش پرداخت') }}</h4>
                    <div class="pay-methods">
                        <label class="pay-option" :class="{ active: paymentMethod === 'online' }">
                            <input type="radio" name="payment_method" value="online" v-model="paymentMethod">
                            <div>
                                <strong>{{ t('online-pay', 'پرداخت آنلاین') }}</strong>
                                <small>{{ t('online-pay-hint', 'پرداخت امن از درگاه بانکی') }}</small>
                            </div>
                        </label>
                        <label class="pay-option" :class="{ active: paymentMethod === 'card' }">
                            <input type="radio" name="payment_method" value="card" v-model="paymentMethod">
                            <div>
                                <strong>{{ t('card-pay', 'کارت به کارت') }}</strong>
                                <small>{{ t('card-pay-hint', 'واریز به کارت و انتظار تایید فروشگاه') }}</small>
                            </div>
                        </label>
                    </div>

                    <div v-if="paymentMethod === 'card'" class="bank-box">
                        <h5>{{ t('bank-info', 'اطلاعات کارت‌به‌کارت') }}</h5>
                        <p v-if="bankName"><span>{{ t('bank-name', 'بانک') }}:</span> <strong>{{ bankName }}</strong></p>
                        <p v-if="bankAccountName"><span>{{ t('account-name', 'به‌نام') }}:</span> <strong>{{ bankAccountName }}</strong></p>
                        <p v-if="bankCardNumber"><span>{{ t('card-number', 'شماره کارت') }}:</span> <strong dir="ltr">{{ bankCardNumber }}</strong></p>
                        <p v-if="bankAccountNumber"><span>{{ t('account-number', 'شماره حساب') }}:</span> <strong dir="ltr">{{ bankAccountNumber }}</strong></p>
                        <p v-if="bankSheba"><span>{{ t('sheba', 'شبا') }}:</span> <strong dir="ltr">{{ bankSheba }}</strong></p>
                        <p class="muted">{{ t('card-wait-hint', 'پس از ثبت سفارش، مبلغ را واریز کنید تا سفارش تایید شود.') }}</p>
                    </div>

                    <button v-if="canPayLocal" type="submit" class="btn-primary-cta wide">
                        {{ paymentMethod === 'card' ? t('register-order', 'ثبت سفارش') : t('pay-now', 'پرداخت') }}
                    </button>
                    <p v-else class="warn">{{ t('plz', 'لطفا وارد شوید یا اطلاعات ضروری را تکمیل کنید') }}</p>

                    <button type="button" class="btn-ghost mt" @click="prev">{{ t('back', 'بازگشت') }}</button>
                </section>
            </div>

            <aside class="checkout-aside">
                <p class="aside-label">{{ t('payable', 'قابل پرداخت') }}</p>
                <p class="aside-total">{{ priceing(displayTotal) }}</p>
                <ul class="aside-lines">
                    <li><span>{{ t('products-total', 'جمع کالاها') }}</span><span>{{ priceing(total) }}</span></li>
                    <li v-if="currentKey !== 'cart'"><span>{{ t('transport', 'ارسال') }}</span><span>{{ priceing(transportPrice) }}</span></li>
                </ul>
                <slot></slot>
                <button v-if="currentKey !== 'payment'" type="button" class="btn-primary-cta wide" @click="next">
                    {{ t('continue', 'ادامه') }}
                </button>
                <button v-if="index > 0" type="button" class="btn-ghost wide mt" @click="prev">
                    {{ t('back', 'بازگشت') }}
                </button>
            </aside>
        </div>
    </div>
</template>

<script>
import QPreview from "./Qpreview.vue";

function commafy(num) {
    if (num === null || num === undefined) {
        return '';
    }
    let str = uncommafy(String(num)).split('.');
    if (str[0].length >= 4) {
        str[0] = str[0].replace(/(\d)(?=(\d{3})+$)/g, '$1,');
    }
    return str.join('.');
}

function uncommafy(txt) {
    return String(txt).split(',').join('');
}

export default {
    name: "card",
    components: {QPreview},
    props: {
        payloadB64: {type: String, default: ''},
        payload: {type: String, default: ''},
    },
    data: () => ({
        lines: [],
        countz: [],
        pricez: [],
        index: 0,
        transport_index: null,
        selectedAddressId: null,
        code: '',
        discount_id: null,
        discount_human: '',
        discount: null,
        paymentMethod: 'online',
        loggedIn: false,
        profileCompleteLocal: false,
        canPayLocal: false,
        localAddresses: [],
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
        profileForm: {
            name: '',
            mobile: '',
            address: '',
        },
        customerName: '',
        productLink: '',
        cardLink: '',
        discountLink: '',
        loginUrl: '',
        signupUrl: '',
        profileUrl: '',
        signInDoUrl: '',
        signUpNowUrl: '',
        sendSmsUrl: '',
        checkAuthUrl: '',
        completeProfileUrl: '',
        smsSign: false,
        isLoggedIn: false,
        profileComplete: false,
        customer: {},
        items: [],
        qs: [],
        symbol: '$',
        addresses: [],
        transports: [],
        canPay: false,
        defTransport: null,
        bankName: '',
        bankCardNumber: '',
        bankAccountNumber: '',
        bankSheba: '',
        bankAccountName: '',
        translate: {},
    }),
    created() {
        this.hydrateFromPayload();
    },
    mounted() {
        this.bootCartLines();
    },
    computed: {
        needsAccount() {
            return !this.loggedIn || !this.profileCompleteLocal;
        },
        steps() {
            const list = [{key: 'cart', label: this.t('cart', 'سبد')}];
            if (this.needsAccount) {
                list.push({key: 'account', label: this.t('account', 'حساب')});
            }
            list.push({key: 'delivery', label: this.t('transport', 'ارسال')});
            list.push({key: 'payment', label: this.t('payment', 'پرداخت')});
            return list;
        },
        currentKey() {
            return this.steps[this.index]?.key || 'cart';
        },
        total() {
            let sum = 0;
            for (const i in this.pricez) {
                sum += Number(this.pricez[i] || 0) * Number(this.countz[i] || 1);
            }
            return sum;
        },
        transportPrice() {
            for (const trs of (this.transports || [])) {
                if (trs.id == this.transport_index) {
                    return Number(trs.price || 0);
                }
            }
            return 0;
        },
        productsTotalAfterDiscount() {
            let sum = this.total;
            if (this.discount != null) {
                if (this.discount.type == 'PERCENT') {
                    sum = ((100 - this.discount.amount) * sum) / 100;
                } else {
                    sum -= this.discount.amount;
                }
            }
            return sum;
        },
        totalWithTransportDiscount() {
            return this.productsTotalAfterDiscount + this.transportPrice;
        },
        displayTotal() {
            if (this.currentKey === 'cart' || this.currentKey === 'account') {
                return this.total;
            }
            if (this.currentKey === 'delivery') {
                return this.total + this.transportPrice;
            }
            return this.totalWithTransportDiscount;
        },
    },
    methods: {
        hydrateFromPayload() {
            let data = {};
            try {
                let raw = '';
                if (this.payloadB64) {
                    const bin = atob(this.payloadB64);
                    raw = decodeURIComponent(Array.from(bin, (c) =>
                        '%'+c.charCodeAt(0).toString(16).padStart(2, '0')
                    ).join(''));
                } else if (this.payload) {
                    raw = this.payload;
                }
                data = raw ? JSON.parse(raw) : {};
            } catch (e) {
                console.error('ns-card payload parse failed', e);
                data = {};
            }

            this.productLink = data.productLink || '';
            this.cardLink = data.cardLink || '';
            this.discountLink = data.discountLink || '';
            this.loginUrl = data.loginUrl || '';
            this.signupUrl = data.signupUrl || '';
            this.profileUrl = data.profileUrl || '';
            this.signInDoUrl = data.signInDoUrl || '';
            this.signUpNowUrl = data.signUpNowUrl || '';
            this.sendSmsUrl = data.sendSmsUrl || '';
            this.checkAuthUrl = data.checkAuthUrl || '';
            this.completeProfileUrl = data.completeProfileUrl || '';
            this.smsSign = !!data.smsSign;
            this.isLoggedIn = !!data.isLoggedIn;
            this.profileComplete = !!data.profileComplete;
            this.canPay = !!data.canPay;
            this.symbol = data.symbol || '$';
            this.bankName = data.bankName || '';
            this.bankCardNumber = data.bankCardNumber || '';
            this.bankAccountNumber = data.bankAccountNumber || '';
            this.bankSheba = data.bankSheba || '';
            this.bankAccountName = data.bankAccountName || '';
            this.defTransport = data.defTransport ?? null;
            this.items = Array.isArray(data.items) ? data.items : (data.items?.data || []);
            this.qs = Array.isArray(data.qs) ? data.qs : [];
            this.addresses = Array.isArray(data.addresses) ? data.addresses : [];
            this.transports = Array.isArray(data.transports) ? data.transports : (data.transports?.data || []);
            this.customer = data.customer || {};
            this.translate = data.translate || {};
        },
        bootCartLines() {
            this.loggedIn = this.isLoggedIn;
            this.profileCompleteLocal = this.profileComplete;
            this.canPayLocal = this.canPay;
            this.localAddresses = Array.isArray(this.addresses) ? [...this.addresses] : [];
            this.transport_index = this.defTransport;
            this.selectedAddressId = this.localAddresses?.[0]?.id ?? null;
            this.customerName = this.customer?.name || '';
            this.profileForm.name = this.customer?.name || '';
            this.profileForm.mobile = this.customer?.mobile || '';

            const selectedIds = Array.isArray(this.qs) ? this.qs : [];
            const sourceItems = Array.isArray(this.items)
                ? this.items
                : (Array.isArray(this.items?.data) ? this.items.data : []);

            this.countz = [];
            this.pricez = [];
            this.lines = sourceItems.map((item, i) => {
                const line = {...item};
                let selected = line.q || null;
                const selectedId = line.selected_quantity_id ?? selectedIds[i] ?? null;
                const stockList = Array.isArray(line.qz)
                    ? line.qz
                    : (Array.isArray(line.qz?.data) ? line.qz.data : []);

                if (!selected && selectedId != null && selectedId !== '') {
                    selected = stockList.find(q => String(q.id) === String(selectedId)) || null;
                }

                line.q = selected;
                this.countz.push(1);
                this.pricez.push(selected?.price ?? line.price);
                return line;
            });
        },
        t(key, fallback = '') {
            return this.translate?.[key] || fallback;
        },
        goTo(i) {
            if (i === this.index) return;
            if (i < this.index) {
                this.index = i;
                return;
            }
            if (i === this.index + 1) {
                this.next();
            }
        },
        next() {
            if (this.currentKey === 'cart') {
                const missing = this.lines.some((item) => {
                    const hasStockChoices = Array.isArray(item.qz) && item.qz.length > 0;
                    return (hasStockChoices || item.selected_quantity_id != null) && !item.q;
                });
                if (missing) {
                    window.$toast?.error?.(this.t('piece-missing', 'قطعه انتخاب نشده'));
                    return;
                }
                this.index = 1;
                return;
            }
            if (this.currentKey === 'account') {
                if (!this.loggedIn || !this.profileCompleteLocal) {
                    window.$toast?.warning?.(this.t('complete-profile', 'لطفا نام، موبایل و آدرس را تکمیل کنید'));
                    return;
                }
                this.index = this.steps.findIndex(s => s.key === 'delivery');
                return;
            }
            if (this.currentKey === 'delivery') {
                if (!this.selectedAddressId) {
                    window.$toast?.error?.(this.t('select-address', 'یک آدرس را انتخاب کنید'));
                    return;
                }
                if (!this.transport_index) {
                    window.$toast?.error?.(this.t('select-transport', 'روش ارسال را انتخاب کنید'));
                    return;
                }
                this.index = this.steps.findIndex(s => s.key === 'payment');
            }
        },
        prev() {
            if (this.index > 0) {
                this.index -= 1;
            }
        },
        applyAuthSuccess(data) {
            this.loggedIn = true;
            this.profileCompleteLocal = !!data.profile_complete;
            this.canPayLocal = !!data.profile_complete;
            this.localAddresses = data.addresses || [];
            this.selectedAddressId = this.localAddresses?.[0]?.id ?? null;
            this.customerName = data.customer?.name || '';
            this.profileForm.name = data.customer?.name || this.profileForm.name;
            this.profileForm.mobile = data.customer?.mobile || this.profileForm.mobile;
            if (this.profileCompleteLocal) {
                this.index = this.steps.findIndex(s => s.key === 'delivery');
            } else {
                this.index = this.steps.findIndex(s => s.key === 'account');
            }
        },
        async sendSms() {
            if (!/^09\d{9}$/.test(this.auth.mobile)) {
                window.$toast?.error?.(this.t('mobile-invalid', 'فرمت شماره موبایل معتبر نیست'));
                return;
            }
            this.authBusy = true;
            try {
                const resp = await axios.get(this.sendSmsUrl, {params: {tel: this.auth.mobile}});
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
                    params: {tel: this.auth.mobile, code: this.auth.code},
                });
                if (resp.data.OK) {
                    window.$toast?.success?.(resp.data.message);
                    this.applyAuthSuccess(resp.data);
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
                }, {headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}});
                if (resp.data.OK) {
                    window.$toast?.success?.(resp.data.message);
                    this.applyAuthSuccess(resp.data.data || resp.data);
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
                }, {headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}});
                if (resp.data.OK) {
                    window.$toast?.success?.(resp.data.message);
                    this.applyAuthSuccess(resp.data.data || resp.data);
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
                };
                if (!this.localAddresses.length) {
                    payload.address = this.profileForm.address;
                }
                const resp = await axios.post(this.completeProfileUrl, payload, {
                    headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                });
                if (resp.data.OK) {
                    window.$toast?.success?.(resp.data.message);
                    this.applyAuthSuccess(resp.data.data || resp.data);
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
        async addAddressQuick() {
            if (!this.profileForm.name || !this.profileForm.mobile) {
                this.profileForm.name = this.profileForm.name || this.customerName || 'مشتری';
                this.profileForm.mobile = this.profileForm.mobile || this.customer?.mobile || '';
            }
            await this.completeProfile();
        },
        async discountCheck() {
            try {
                const resp = await axios.get(this.discountLink + this.code);
                if (!resp.data.OK) {
                    window.$toast.error(resp.data.err);
                } else {
                    window.$toast.success(resp.data.msg);
                    this.discount_id = resp.data.data.id;
                    this.discount_human = resp.data.human;
                    this.discount = resp.data.data;
                }
            } catch (e) {
                window.$toast.error(e.message);
            }
        },
        priceing(p) {
            if (p == null || p === undefined) {
                return '';
            }
            return commafy(p) + ' ' + this.symbol;
        },
    },
}
</script>

<style scoped>
.checkout-card {
    --ck-ink: #1c1917;
    --ck-muted: #78716c;
    --ck-line: rgba(28, 25, 23, 0.1);
    --ck-soft: #fafaf9;
    --ck-accent: var(--xshop-primary, #8a6a3b);
    color: var(--ck-ink);
}

.checkout-progress {
    display: flex;
    gap: .5rem;
    margin-bottom: 1.5rem;
    overflow-x: auto;
    padding-bottom: .25rem;
}

.progress-item {
    flex: 1;
    min-width: 5.5rem;
    border: 0;
    background: transparent;
    display: flex;
    align-items: center;
    gap: .5rem;
    padding: .35rem .25rem;
    color: var(--ck-muted);
    border-bottom: 2px solid transparent;
}

.progress-item .num {
    width: 1.6rem;
    height: 1.6rem;
    border-radius: 999px;
    border: 1px solid var(--ck-line);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: .8rem;
}

.progress-item.active {
    color: var(--ck-ink);
    border-bottom-color: var(--ck-accent);
}

.progress-item.done .num,
.progress-item.active .num {
    background: var(--ck-accent);
    border-color: var(--ck-accent);
    color: #fff;
}

.progress-item .label {
    font-size: .85rem;
    font-weight: 600;
}

.checkout-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 280px;
    gap: 1.5rem;
    align-items: start;
}

.checkout-panel {
    background: #fff;
    border: 1px solid var(--ck-line);
    border-radius: 1rem;
    padding: 1.25rem 1.35rem 1.5rem;
}

.checkout-panel h3 {
    font-size: 1.25rem;
    margin: 0 0 1rem;
    font-weight: 700;
}

.checkout-panel h4 {
    font-size: .95rem;
    margin: 1rem 0 .65rem;
    font-weight: 600;
}

.piece-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    gap: .85rem;
}

.piece-row {
    display: grid;
    grid-template-columns: 72px 1fr auto;
    gap: .85rem;
    align-items: start;
    padding: .85rem;
    background: var(--ck-soft);
    border-radius: .85rem;
}

.piece-img {
    width: 72px;
    height: 72px;
    object-fit: cover;
    border-radius: .65rem;
}

.piece-name {
    display: block;
    font-weight: 600;
    color: inherit;
    text-decoration: none;
    margin-bottom: .35rem;
}

.piece-price {
    display: block;
    margin-top: .45rem;
}

.piece-missing {
    margin: 0;
    color: #b45309;
    font-size: .9rem;
}

.piece-remove {
    color: var(--ck-muted);
    font-size: 1.25rem;
    line-height: 1;
}

.checkout-aside {
    position: sticky;
    top: 1rem;
    background: linear-gradient(160deg, color-mix(in srgb, var(--ck-accent) 92%, #111) 0%, var(--ck-accent) 100%);
    color: #fff;
    border-radius: 1rem;
    padding: 1.25rem;
}

.aside-label {
    margin: 0;
    opacity: .85;
    font-size: .85rem;
}

.aside-total {
    margin: .2rem 0 1rem;
    font-size: 1.55rem;
    font-weight: 700;
}

.aside-lines {
    list-style: none;
    margin: 0 0 1rem;
    padding: 0;
    font-size: .9rem;
}

.aside-lines li {
    display: flex;
    justify-content: space-between;
    gap: .5rem;
    opacity: .9;
    margin-bottom: .35rem;
}

.btn-primary-cta,
.btn-secondary-cta,
.btn-ghost {
    border: 0;
    border-radius: .75rem;
    padding: .8rem 1rem;
    font-weight: 600;
    cursor: pointer;
}

.btn-primary-cta {
    background: var(--ck-accent);
    color: #fff;
}

.checkout-aside .btn-primary-cta {
    background: #fff;
    color: var(--ck-ink);
}

.btn-secondary-cta {
    background: color-mix(in srgb, var(--ck-accent) 12%, white);
    color: var(--ck-ink);
    border: 1px solid var(--ck-line);
}

.btn-ghost {
    background: transparent;
    color: inherit;
    border: 1px solid currentColor;
    opacity: .85;
}

.btn-primary-cta.wide,
.btn-ghost.wide {
    width: 100%;
}

.mt { margin-top: .75rem; }

.auth-tabs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .35rem;
    margin-bottom: 1rem;
    background: var(--ck-soft);
    padding: .3rem;
    border-radius: .75rem;
}

.auth-tabs button {
    border: 0;
    background: transparent;
    border-radius: .55rem;
    padding: .55rem;
    font-weight: 600;
}

.auth-tabs button.active {
    background: #fff;
    box-shadow: 0 1px 2px rgba(0,0,0,.06);
}

.auth-form {
    display: grid;
    gap: .75rem;
}

.auth-form label {
    display: grid;
    gap: .35rem;
    font-size: .9rem;
    font-weight: 600;
}

.auth-form input,
.auth-form textarea,
.discount-row input,
textarea.full {
    width: 100%;
    border: 1px solid var(--ck-line);
    border-radius: .65rem;
    padding: .7rem .8rem;
    background: #fff;
}

.hint {
    margin: 0;
    color: var(--ck-muted);
    font-size: .92rem;
}

.choice {
    border: 1px solid var(--ck-line);
    border-radius: .75rem;
    margin-bottom: .5rem;
}

.choice label {
    display: flex;
    gap: .75rem;
    align-items: flex-start;
    padding: .85rem 1rem;
    cursor: pointer;
}

.choice-transport label {
    align-items: center;
}

.choice-transport span {
    flex: 1;
    display: grid;
    gap: .15rem;
}

.choice-transport small {
    color: var(--ck-muted);
}

.choice-transport em {
    font-style: normal;
    font-weight: 600;
    white-space: nowrap;
}

.row-actions {
    display: flex;
    gap: .65rem;
    margin-top: 1rem;
}

.brief-invoice {
    background: var(--ck-soft);
    border-radius: .75rem;
    padding: .9rem 1rem;
    display: grid;
    gap: .45rem;
    margin-bottom: .5rem;
}

.brief-invoice > div {
    display: flex;
    justify-content: space-between;
    gap: .5rem;
}

.brief-invoice .total {
    border-top: 1px solid var(--ck-line);
    padding-top: .55rem;
    font-size: 1.05rem;
}

.discount-row {
    display: flex;
    gap: .5rem;
}

.pay-methods {
    display: grid;
    gap: .65rem;
}

.pay-option {
    display: flex;
    gap: .75rem;
    align-items: flex-start;
    border: 1px solid var(--ck-line);
    border-radius: .75rem;
    padding: .85rem 1rem;
    cursor: pointer;
}

.pay-option.active {
    border-color: var(--ck-accent);
    background: color-mix(in srgb, var(--ck-accent) 8%, white);
}

.pay-option small {
    display: block;
    color: var(--ck-muted);
}

.bank-box {
    margin: .85rem 0;
    padding: 1rem;
    border-radius: .75rem;
    background: #fffbeb;
    border: 1px solid #fde68a;
}

.bank-box h5 { margin: 0 0 .5rem; }
.bank-box p { margin: .25rem 0; }
.muted { color: var(--ck-muted); font-size: .88rem; }
.ok-msg { color: #15803d; }
.warn { color: #b91c1c; }

@media (max-width: 900px) {
    .checkout-layout {
        grid-template-columns: 1fr;
    }
    .checkout-aside {
        position: static;
        order: -1;
    }
}
</style>
