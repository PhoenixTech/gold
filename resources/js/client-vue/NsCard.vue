<template>
    <div id="card" class="checkout-card">
        <div class="assay-ticket" role="status" :class="{ urgent: quoteRemaining <= 120 }">
            <div class="assay-copy">
                <span class="assay-kicker">{{ t('live-price-title', 'قیمت طلا لحظه‌ای است') }}</span>
                <p>{{ t('live-price-hint', 'قیمت هر قطعه با نرخ روز طلا محاسبه می‌شود و برای صدور فاکتور ۳۰ دقیقه اعتبار دارد.') }}</p>
                <ol class="assay-path">
                    <li class="now">{{ t('quote-step', 'صدور فاکتور') }} · {{ quoteMinutes }} {{ t('minutes-short', 'دقیقه') }}</li>
                    <li>{{ t('pay-step', 'کارت‌به‌کارت و رسید') }} · {{ offlinePaymentHours }} {{ t('hours-short', 'ساعت') }}</li>
                </ol>
            </div>
            <div class="quote-timer" :class="{ urgent: quoteRemaining <= 120 }">
                <span>{{ t('quote-remaining', 'زمان باقی‌مانده') }}</span>
                <em>{{ quoteCountdown }}</em>
            </div>
        </div>

        <nav class="checkout-progress" aria-label="checkout steps">
            <button
                v-for="(step, i) in steps"
                :key="step.key"
                type="button"
                class="progress-item"
                :class="{ active: index === i, done: index > i }"
                :aria-current="index === i ? 'step' : undefined"
                @click="goTo(i)"
            >
                <span class="num">
                    <i v-if="index > i" class="ri-check-line"></i>
                    <template v-else>{{ i + 1 }}</template>
                </span>
                <span class="label">{{ step.label }}</span>
            </button>
        </nav>

        <div class="checkout-layout">
            <div class="checkout-main">
                <!-- Cart -->
                <section v-show="currentKey === 'cart'" class="checkout-panel">
                    <header class="panel-head">
                        <h5>{{ t('cart', 'سبد خرید') }}</h5>
                        <span class="panel-count">{{ lines.length }} {{ t('pieces', 'قطعه') }}</span>
                    </header>
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

                            <a :href="productLink + item.slug" class="piece-img-wrap">
                                <img :src="item.image" :alt="item.name" class="piece-img">
                            </a>
                            <div class="piece-body">
                                <a class="piece-name" :href="productLink + item.slug">{{ item.name }}</a>
                                <template v-if="item.q">
                                    <q-preview
                                        :q="item.q"
                                        :weight-label="t('weight', 'وزن')"
                                        :code-label="t('code', 'کد')"
                                    ></q-preview>
                                </template>
                                <p v-else-if="item.qz && item.qz.length > 0" class="piece-missing">
                                    {{ t('piece-missing', 'قطعه انتخاب نشده') }}
                                    —
                                    <a :href="productLink + item.slug">{{ t('choose-piece', 'انتخاب قطعه') }}</a>
                                </p>
                                <div class="piece-price-row">
                                    <span>{{ t('live-piece-price', 'قیمت لحظه‌ای') }}</span>
                                    <strong class="piece-price">{{ priceing(pricez[i]) }}</strong>
                                </div>
                            </div>
                            <a class="piece-remove" :href="cardLink + item.slug" :title="t('remove', 'حذف')">
                                <i class="ri-close-line"></i>
                            </a>
                        </li>
                    </ul>
                </section>

                <!-- Account -->
                <section v-show="currentKey === 'account'" class="checkout-panel">
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

                    <button type="button" class="btn-ghost mt panel-back" @click="prev">{{ t('back', 'بازگشت') }}</button>
                </section>

                <!-- Delivery -->
                <section v-show="currentKey === 'delivery'" class="checkout-panel">
                    <header class="panel-head">
                        <h5>{{ t('transport', 'ارسال') }}</h5>
                    </header>

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
                    <div v-for="adr in localAddresses" :key="adr.id" class="choice" :class="{ selected: selectedAddressId == adr.id }">
                        <label>
                            <input type="radio" name="address_id" :value="adr.id" v-model="selectedAddressId">
                            <span>{{ adr.address }}</span>
                        </label>
                    </div>

                    <h4 class="mt">{{ t('transport', 'ارسال') }}</h4>
                    <div v-for="trs in transports" :key="trs.id" class="choice choice-transport" :class="{ selected: transport_index == trs.id }">
                        <label>
                            <input type="radio" name="transport_id" :value="trs.id" v-model="transport_index">
                            <span>
                                <strong>{{ trs.title }}</strong>
                                <small v-if="trs.description">{{ trs.description }}</small>
                            </span>
                            <em>{{ priceing(trs.price) }}</em>
                        </label>
                    </div>

                    <button type="button" class="btn-ghost mt panel-back" @click="prev">{{ t('back', 'بازگشت') }}</button>
                </section>

                <!-- Payment -->
                <section v-show="currentKey === 'payment'" class="checkout-panel">
                    <header class="panel-head">
                        <h5>{{ t('payment', 'پرداخت') }}</h5>
                    </header>

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

                    <input type="hidden" name="payment_method" value="card">
                    <div class="pay-option active">
                        <i class="ri-bank-card-line pay-icon"></i>
                        <div>
                            <strong>{{ t('card-pay', 'کارت به کارت') }}</strong>
                            <small>{{ t('card-pay-hint', 'واریز به کارت و انتظار تایید فروشگاه') }}</small>
                        </div>
                    </div>

                    <div class="bank-box">
                        <h5>{{ t('bank-info', 'اطلاعات کارت‌به‌کارت') }}</h5>
                        <div v-if="bankName" class="bank-row">
                            <span>{{ t('bank-name', 'بانک') }}</span>
                            <strong>{{ bankName }}</strong>
                        </div>
                        <div v-if="bankAccountName" class="bank-row">
                            <span>{{ t('account-name', 'به‌نام') }}</span>
                            <strong>{{ bankAccountName }}</strong>
                        </div>
                        <div v-if="bankCardNumber" class="bank-row">
                            <span>{{ t('card-number', 'شماره کارت') }}</span>
                            <strong dir="ltr">{{ bankCardNumber }}</strong>
                            <button type="button" class="copy-btn" @click="copyText(bankCardNumber, 'card')">
                                {{ copiedKey === 'card' ? t('copied', 'کپی شد') : t('copy', 'کپی') }}
                            </button>
                        </div>
                        <div v-if="bankAccountNumber" class="bank-row">
                            <span>{{ t('account-number', 'شماره حساب') }}</span>
                            <strong dir="ltr">{{ bankAccountNumber }}</strong>
                            <button type="button" class="copy-btn" @click="copyText(bankAccountNumber, 'account')">
                                {{ copiedKey === 'account' ? t('copied', 'کپی شد') : t('copy', 'کپی') }}
                            </button>
                        </div>
                        <div v-if="bankSheba" class="bank-row">
                            <span>{{ t('sheba', 'شبا') }}</span>
                            <strong dir="ltr">{{ bankSheba }}</strong>
                            <button type="button" class="copy-btn" @click="copyText(bankSheba, 'sheba')">
                                {{ copiedKey === 'sheba' ? t('copied', 'کپی شد') : t('copy', 'کپی') }}
                            </button>
                        </div>
                        <p class="muted">{{ t('card-wait-hint', 'پس از ثبت سفارش، مبلغ را واریز کنید تا سفارش تایید شود.') }}</p>
                    </div>

                    <p v-if="!canPayLocal" class="warn">{{ t('plz', 'لطفا وارد شوید یا اطلاعات ضروری را تکمیل کنید') }}</p>
                    <button type="button" class="btn-ghost mt panel-back" @click="prev">{{ t('back', 'بازگشت') }}</button>
                </section>
            </div>

            <aside class="checkout-aside">
                <p class="aside-label">{{ t('payable', 'قابل پرداخت') }}</p>
                <p class="aside-total">{{ priceing(displayTotal) }}</p>
                <p class="aside-timer" :class="{ urgent: quoteRemaining <= 120 }">
                    <i class="ri-time-line"></i>
                    {{ t('quote-remaining', 'زمان باقی‌مانده') }}
                    <b dir="ltr">{{ quoteCountdown }}</b>
                </p>
                <ul class="aside-lines">
                    <li><span>{{ t('products-total', 'جمع کالاها') }}</span><span>{{ priceing(productsTotalAfterDiscount) }}</span></li>
                    <li v-if="currentKey !== 'cart' && currentKey !== 'account'"><span>{{ t('transport', 'ارسال') }}</span><span>{{ priceing(transportPrice) }}</span></li>
                </ul>
                <slot></slot>
                <button v-if="currentKey !== 'payment'" type="button" class="btn-primary-cta wide aside-cta" @click="next">
                    {{ t('continue', 'ادامه') }}
                </button>
                <button v-else-if="canPayLocal" type="submit" class="btn-primary-cta wide aside-cta">
                    {{ t('register-order', 'ثبت سفارش') }}
                </button>
                <button v-if="index > 0" type="button" class="btn-ghost wide mt aside-cta" @click="prev">
                    {{ t('back', 'بازگشت') }}
                </button>
                <p class="aside-note">{{ t('aside-note', 'بعد از صدور فاکتور ۳ ساعت برای واریز فرصت دارید.') }}</p>
            </aside>
        </div>

        <div class="checkout-dock">
            <div class="dock-meta">
                <small :class="{ urgent: quoteRemaining <= 120 }">{{ quoteCountdown }}</small>
                <strong>{{ priceing(displayTotal) }}</strong>
            </div>
            <button v-if="currentKey !== 'payment'" type="button" class="btn-primary-cta" @click="next">
                {{ t('continue', 'ادامه') }}
            </button>
            <button v-else-if="canPayLocal" type="submit" class="btn-primary-cta">
                {{ t('register-order', 'ثبت سفارش') }}
            </button>
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
        paymentMethod: 'card',
        quoteExpiresAt: 0,
        quoteMinutes: 30,
        nowTs: Math.floor(Date.now() / 1000),
        quoteTimer: null,
        copiedKey: null,
        copyTimer: null,
        offlinePaymentHours: 3,
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
        this.quoteTimer = setInterval(() => {
            this.nowTs = Math.floor(Date.now() / 1000);
            if (this.quoteExpiresAt > 0 && this.quoteRemaining <= 0) {
                clearInterval(this.quoteTimer);
                this.quoteTimer = null;
                window.location.reload();
            }
        }, 1000);
    },
    beforeUnmount() {
        if (this.quoteTimer) {
            clearInterval(this.quoteTimer);
        }
        if (this.copyTimer) {
            clearTimeout(this.copyTimer);
        }
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
        quoteRemaining() {
            if (!this.quoteExpiresAt) {
                return 0;
            }
            return Math.max(0, this.quoteExpiresAt - this.nowTs);
        },
        quoteCountdown() {
            const seconds = this.quoteRemaining;
            const minutes = Math.floor(seconds / 60);
            const rest = seconds % 60;
            return String(minutes).padStart(2, '0') + ':' + String(rest).padStart(2, '0');
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
            this.quoteExpiresAt = Number(data.quoteExpiresAt) || 0;
            this.quoteMinutes = Number(data.quoteMinutes) || 30;
            this.offlinePaymentHours = Number(data.offlinePaymentHours) || 3;
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
        async copyText(value, key) {
            if (!value) {
                return;
            }
            try {
                await navigator.clipboard.writeText(String(value).replace(/\s/g, ''));
                this.copiedKey = key;
                if (this.copyTimer) {
                    clearTimeout(this.copyTimer);
                }
                this.copyTimer = setTimeout(() => {
                    if (this.copiedKey === key) {
                        this.copiedKey = null;
                    }
                }, 1800);
            } catch (e) {
                window.$toast?.error?.(this.t('copy-failed', 'کپی نشد'));
            }
        },
    },
}
</script>

<style scoped>
.checkout-card {
    --ck-ink: var(--xshop-gray-900);
    --ck-muted: var(--xshop-gray-500);
    --ck-line: rgba(61, 46, 20, 0.12);
    --ck-soft: var(--xshop-gold-50);
    --ck-paper: var(--xshop-gold-50);
    --ck-accent: var(--xshop-primary);
    --ck-deep: var(--xshop-gold-900);
    color: var(--ck-ink);
    padding-bottom: 0;
}

.assay-ticket {
    display: flex;
    align-items: stretch;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
    background:
        radial-gradient(circle at 0 50%, var(--ck-paper) 7px, transparent 8px),
        radial-gradient(circle at 100% 50%, var(--ck-paper) 7px, transparent 8px),
        linear-gradient(180deg, var(--xshop-gold-100), var(--ck-paper));
    border: 1px solid var(--xshop-gold-300);
    box-shadow: var(--xshop-shadow-lg);
}

.assay-ticket.urgent {
    border-color: var(--xshop-gold-400);
}

.assay-copy {
    padding: 1rem 1.15rem 1.05rem;
    min-width: 0;
}

.assay-kicker {
    display: inline-block;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .08em;
    color: var(--ck-accent);
    margin-bottom: .35rem;
}

.assay-copy p {
    margin: 0;
    color: var(--ck-muted);
    font-size: .88rem;
    line-height: 1.65;
}

.assay-path {
    display: flex;
    flex-wrap: wrap;
    gap: .45rem .75rem;
    list-style: none;
    margin: .7rem 0 0;
    padding: 0;
    font-size: .78rem;
}

.assay-path li {
    color: var(--ck-muted);
}

.assay-path .now {
    color: var(--ck-deep);
    font-weight: 700;
}

.quote-timer {
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
    text-align: center;
    min-width: 7.2rem;
    padding: .85rem 1rem;
    background: color-mix(in srgb, var(--ck-accent) 10%, white);
    border-inline-start: 1px dashed var(--xshop-gold-300);
}

.quote-timer span {
    display: block;
    font-size: .7rem;
    color: var(--ck-muted);
}

.quote-timer em {
    font-style: normal;
    font-size: 1.55rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    letter-spacing: .06em;
    direction: ltr;
    display: inline-block;
    color: var(--ck-deep);
}

.quote-timer.urgent {
    background: var(--xshop-gold-100);
    color: var(--xshop-danger);
}

.quote-timer.urgent em {
    color: var(--xshop-danger);
}

.checkout-progress {
    display: flex;
    gap: .15rem;
    margin-bottom: 1.35rem;
    overflow-x: auto;
}

.progress-item {
    flex: 1;
    min-width: 5.2rem;
    border: 0;
    background: transparent;
    display: flex;
    align-items: center;
    gap: .5rem;
    padding: .4rem .2rem .55rem;
    color: var(--ck-muted);
    border-bottom: 2px solid var(--ck-line);
}

.progress-item .num {
    width: 1.55rem;
    height: 1.55rem;
    border-radius: 999px;
    border: 1px solid var(--ck-line);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: .78rem;
}

.progress-item.active {
    color: var(--ck-deep);
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

.progress-item:focus-visible,
.btn-primary-cta:focus-visible,
.btn-secondary-cta:focus-visible,
.btn-ghost:focus-visible,
.copy-btn:focus-visible {
    outline: 2px solid var(--ck-accent);
    outline-offset: 2px;
}

.checkout-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 300px;
    gap: 1.5rem;
    align-items: start;
}

.checkout-panel {
    background: #fff;
    border: 1px solid var(--ck-line);
    border-radius: 1rem;
    padding: 1.2rem 1.3rem 1.4rem;
}

.panel-head {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: .75rem;
    margin-bottom: 1rem;
}

.panel-head h5 {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 700;
}

.panel-count {
    font-size: .82rem;
    color: var(--ck-muted);
}

.checkout-panel h4 {
    font-size: .92rem;
    margin: 1rem 0 .65rem;
    font-weight: 600;
}

.piece-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    gap: .8rem;
}

.piece-row {
    display: grid;
    grid-template-columns: 88px 1fr auto;
    gap: .9rem;
    align-items: start;
    padding: .8rem;
    background: var(--ck-soft);
    border: 1px solid transparent;
    border-radius: .9rem;
}

.piece-img-wrap {
    display: block;
}

.piece-img {
    width: 88px;
    height: 88px;
    object-fit: cover;
    border-radius: .7rem;
    background: #fff;
}

.piece-name {
    display: block;
    font-weight: 700;
    color: inherit;
    text-decoration: none;
    margin-bottom: .35rem;
}

.piece-price-row {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: .75rem;
    margin-top: .55rem;
    padding-top: .45rem;
    border-top: 1px dashed var(--ck-line);
}

.piece-price-row span {
    font-size: .75rem;
    color: var(--ck-muted);
}

.piece-price {
    font-size: 1.02rem;
}

.piece-missing {
    margin: 0;
    color: var(--xshop-danger);
    font-size: .9rem;
}

.piece-remove {
    color: var(--ck-muted);
    font-size: 1.2rem;
    line-height: 1;
    width: 2rem;
    height: 2rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
}

.piece-remove:hover {
    background: #fff;
    color: var(--ck-ink);
}

.checkout-aside {
    position: sticky;
    top: 1rem;
    background: linear-gradient(165deg, var(--ck-deep) 0%, color-mix(in srgb, var(--ck-accent) 55%, var(--xshop-gray-900)) 100%);
    color: #fff;
    border-radius: 1rem;
    padding: 1.2rem 1.2rem 1.1rem;
}

.aside-label {
    margin: 0;
    opacity: .8;
    font-size: .8rem;
}

.aside-total {
    margin: .15rem 0 .7rem;
    font-size: 1.55rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}

.aside-timer {
    display: flex;
    align-items: center;
    gap: .35rem;
    margin: 0 0 1rem;
    padding: .45rem .6rem;
    border-radius: .6rem;
    background: rgba(255,255,255,.1);
    font-size: .82rem;
}

.aside-timer b {
    margin-inline-start: auto;
    font-variant-numeric: tabular-nums;
}

.aside-timer.urgent {
    background: rgba(255, 237, 213, .2);
}

.aside-lines {
    list-style: none;
    margin: 0 0 1rem;
    padding: 0;
    font-size: .88rem;
}

.aside-lines li {
    display: flex;
    justify-content: space-between;
    gap: .5rem;
    opacity: .9;
    margin-bottom: .35rem;
}

.aside-note {
    margin: .85rem 0 0;
    font-size: .75rem;
    line-height: 1.6;
    opacity: .78;
}

.btn-primary-cta,
.btn-secondary-cta,
.btn-ghost {
    border: 0;
    border-radius: .75rem;
    padding: .8rem 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-primary-cta {
    background: #db9a00;
    background: linear-gradient(135deg, #f59e0b 0%, #db9a00 100%);
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(219, 154, 0, 0.35);
}

.btn-primary-cta:hover {
    background: #c78b00;
    background: linear-gradient(135deg, #d97706 0%, #c78b00 100%);
    color: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(219, 154, 0, 0.45);
}

.checkout-aside .btn-primary-cta {
    background: #db9a00;
    background: linear-gradient(135deg, #f59e0b 0%, #db9a00 100%);
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
}

.checkout-aside .btn-primary-cta:hover {
    background: #c78b00;
    background: linear-gradient(135deg, #d97706 0%, #c78b00 100%);
    color: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.35);
}

.checkout-aside .btn-ghost {
    border-color: rgba(255,255,255,.35);
    color: #fff;
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

.btn-primary-cta:disabled {
    opacity: .55;
    cursor: not-allowed;
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
    box-shadow: var(--xshop-shadow-2xs);
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
    background: #fff;
}

.choice.selected {
    border-color: var(--ck-accent);
    background: color-mix(in srgb, var(--ck-accent) 7%, white);
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

.discount-row {
    display: flex;
    gap: .5rem;
}

.pay-option {
    display: flex;
    gap: .75rem;
    align-items: flex-start;
    border: 1px solid color-mix(in srgb, var(--ck-accent) 45%, white);
    border-radius: .75rem;
    padding: .85rem 1rem;
    margin: 1rem 0 .75rem;
    background: color-mix(in srgb, var(--ck-accent) 8%, white);
}

.pay-icon {
    font-size: 1.35rem;
    color: var(--ck-accent);
}

.pay-option small {
    display: block;
    color: var(--ck-muted);
}

.bank-box {
    margin: .35rem 0 1rem;
    padding: 1rem;
    border-radius: .75rem;
    background: var(--ck-soft);
    border: 1px solid var(--xshop-gold-300);
}

.bank-box h5 { margin: 0 0 .75rem; }

.bank-row {
    display: grid;
    grid-template-columns: 7.5rem 1fr auto;
    gap: .5rem;
    align-items: center;
    padding: .45rem 0;
    border-bottom: 1px dashed var(--ck-line);
    font-size: .9rem;
}

.bank-row span { color: var(--ck-muted); }
.bank-row strong { overflow-wrap: anywhere; }

.copy-btn {
    border: 1px solid var(--ck-line);
    background: #fff;
    border-radius: .5rem;
    padding: .25rem .55rem;
    font-size: .75rem;
    font-weight: 600;
    cursor: pointer;
}

.muted { color: var(--ck-muted); font-size: .88rem; margin: .75rem 0 0; }
.ok-msg { color: var(--xshop-success); }
.warn { color: var(--xshop-danger); }

.checkout-dock {
    display: none;
}

@media (max-width: 900px) {
    .checkout-card {
        padding-bottom: .25rem;
    }
    .assay-ticket {
        flex-direction: column;
        gap: 0;
    }
    .quote-timer {
        border-inline-start: 0;
        border-top: 1px dashed var(--xshop-gold-300);
        min-width: 0;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
    }
    .checkout-layout {
        grid-template-columns: 1fr;
    }
    .checkout-aside {
        position: static;
        display: none;
    }
    .panel-back {
        display: none;
    }
    .piece-row {
        grid-template-columns: 72px 1fr auto;
    }
    .piece-img {
        width: 72px;
        height: 72px;
    }
    .bank-row {
        grid-template-columns: 1fr auto;
    }
    .bank-row span {
        grid-column: 1 / -1;
    }
    .checkout-dock {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        position: sticky;
        bottom: .75rem;
        z-index: 20;
        margin-top: 1rem;
        padding: .7rem .8rem;
        background: var(--ck-deep);
        color: #fff;
        border-radius: 1rem;
        box-shadow: var(--xshop-shadow-lg);
    }
    .dock-meta {
        display: grid;
        min-width: 0;
    }
    .dock-meta small {
        font-size: .72rem;
        opacity: .8;
        font-variant-numeric: tabular-nums;
        direction: ltr;
        display: inline-block;
    }
    .dock-meta small.urgent {
        color: var(--xshop-gold-400);
    }
    .dock-meta strong {
        font-size: 1.05rem;
        font-variant-numeric: tabular-nums;
    }
    .checkout-dock .btn-primary-cta {
        flex-shrink: 0;
        background: #db9a00;
        background: linear-gradient(135deg, #f59e0b 0%, #db9a00 100%);
        color: #fff;
        padding: .7rem 1.1rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    }
}

@media (prefers-reduced-motion: reduce) {
    * {
        animation: none !important;
        transition: none !important;
    }
}
</style>
