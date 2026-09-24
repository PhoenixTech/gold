<template>
    <div id="card" class="checkout-card">
        <cart-assay-ticket
            :remaining="quoteRemaining"
            :minutes="quoteMinutes"
            :hours="offlinePaymentHours"
            :countdown="quoteCountdown"
            :t="t"
        />

        <cart-stepper
            :steps="steps"
            :index="index"
            @go-to="goTo"
        />

        <div class="checkout-layout">
            <div class="checkout-main">
                <section v-show="currentKey === 'cart'" class="checkout-panel">
                    <header class="panel-head d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="mb-0">{{ t('cart', 'سبد خرید') }}</h5>
                            <span class="panel-count">{{ lines.length }} {{ t('pieces', 'قطعه') }}</span>
                        </div>
                        <a v-if="productsUrl" :href="productsUrl" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 text-decoration-none">
                            <i class="ri-arrow-right-line"></i>
                            <span>{{ t('continue-shopping', 'ادامه خرید') }}</span>
                        </a>
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

                <cart-auth-step
                    v-show="currentKey === 'account'"
                    :logged-in="loggedIn"
                    :sms-sign="smsSign"
                    :profile-form="profileForm"
                    :local-addresses="localAddresses"
                    :delivery-type="deliveryType"
                    :send-sms-url="sendSmsUrl"
                    :check-auth-url="checkAuthUrl"
                    :sign-in-do-url="signInDoUrl"
                    :sign-up-now-url="signUpNowUrl"
                    :complete-profile-url="completeProfileUrl"
                    :t="t"
                    @auth-success="applyAuthSuccess"
                    @prev="prev"
                />

                <cart-delivery-type-step
                    v-show="currentKey === 'delivery-type'"
                    v-model="deliveryType"
                    :t="t"
                    @prev="prev"
                />

                <cart-delivery-details-step
                    v-show="currentKey === 'delivery-details'"
                    :delivery-type="deliveryType"
                    :gallery-address="galleryAddress"
                    :user-name="userName"
                    :user-mobile="userMobile"
                    :local-addresses="localAddresses"
                    v-model:selected-address-id="selectedAddressId"
                    :selected-address="selectedAddress"
                    :transports="transports"
                    v-model:transport-index="transport_index"
                    v-model:is-third-party="isThirdParty"
                    v-model:recipient-name="recipientName"
                    v-model:recipient-mobile="recipientMobile"
                    v-model:recipient-national-id="recipientNationalId"
                    v-model:profile-address="profileForm.address"
                    :auth-busy="authBusy"
                    :priceing="priceing"
                    :t="t"
                    @add-address-quick="addAddressQuick"
                    @prev="prev"
                />

                <cart-review-step
                    v-show="currentKey === 'review'"
                    :delivery-type="deliveryType"
                    :gallery-address="galleryAddress"
                    :user-name="userName"
                    :user-mobile="userMobile"
                    :selected-address="selectedAddress"
                    :selected-transport="selectedTransport"
                    :is-third-party="isThirdParty"
                    :recipient-name="recipientName"
                    :recipient-mobile="recipientMobile"
                    :recipient-national-id="recipientNationalId"
                    :lines="lines"
                    :pricez="pricez"
                    :product-link="productLink"
                    :discount-link="discountLink"
                    :discount="discount"
                    :priceing="priceing"
                    :t="t"
                    @discount-applied="discount = $event"
                    @discount-removed="discount = null"
                    @edit-step="goToStep"
                    @prev="prev"
                />

                <cart-payment-step
                    v-show="currentKey === 'payment'"
                    :bank-name="bankName"
                    :bank-account-name="bankAccountName"
                    :bank-card-number="bankCardNumber"
                    :bank-sheba="bankSheba"
                    :bank-account-number="bankAccountNumber"
                    :can-submit-order="canSubmitOrder"
                    :logged-in="loggedIn"
                    :customer-name="userName"
                    :profile-form="profileForm"
                    :customer="customer"
                    :delivery-type="deliveryType"
                    :selected-address="selectedAddress"
                    :is-third-party="isThirdParty"
                    :recipient-name="recipientName"
                    :recipient-mobile="recipientMobile"
                    :recipient-national-id="recipientNationalId"
                    :t="t"
                    @prev="prev"
                />
            </div>

            <aside class="checkout-aside">
                <p class="aside-label">{{ t('payable', 'قابل پرداخت') }}</p>
                <p class="aside-total">{{ priceing(displayTotal) }}</p>
                <ul class="aside-lines">
                    <li><span>{{ t('products-total', 'جمع کالاها') }}</span><span>{{ priceing(total) }}</span></li>
                    <li v-if="discountAmount > 0" class="discount-line"><span>{{ t('discount', 'تخفیف') }}</span><span>- {{ priceing(discountAmount) }}</span></li>
                    <li v-if="currentKey !== 'cart' && currentKey !== 'account' && currentKey !== 'delivery-type'"><span>{{ t('transport', 'ارسال') }}</span><span>{{ transportPrice > 0 ? priceing(transportPrice) : t('free', 'رایگان') }}</span></li>
                </ul>
                <slot></slot>
                <button v-if="currentKey !== 'payment'" type="button" class="btn-primary-cta wide aside-cta" @click="next">
                    {{ currentKey === 'review' ? t('continue-to-payment', 'ادامه به پرداخت') : t('continue', 'ادامه') }}
                </button>
                <button v-else-if="canSubmitOrder" type="submit" class="btn-primary-cta wide aside-cta">
                    {{ t('register-order', 'ثبت سفارش') }}
                </button>
                <button v-if="index > 0" type="button" class="btn-ghost wide mt aside-cta" @click="prev">
                    {{ t('back', 'بازگشت') }}
                </button>
                <a v-if="productsUrl && currentKey === 'cart'" :href="productsUrl" class="btn btn-outline-secondary wide mt-2 aside-cta text-decoration-none d-flex align-items-center justify-content-center gap-2">
                    <i class="ri-arrow-right-line"></i>
                    <span>{{ t('continue-shopping', 'ادامه خرید') }}</span>
                </a>
                <p class="aside-note">{{ t('aside-note', 'بعد از صدور فاکتور ۳ ساعت برای واریز فرصت دارید.') }}</p>
            </aside>
        </div>

        <div class="checkout-dock">
            <div class="dock-meta">
                <small :class="{ urgent: quoteRemaining <= 120 }">{{ quoteCountdown }}</small>
                <strong>{{ priceing(displayTotal) }}</strong>
            </div>
            <button v-if="currentKey !== 'payment'" type="button" class="btn-primary-cta" @click="next">
                {{ currentKey === 'review' ? t('continue-to-payment', 'ادامه به پرداخت') : t('continue', 'ادامه') }}
            </button>
            <button v-else-if="canSubmitOrder" type="submit" class="btn-primary-cta">
                {{ t('register-order', 'ثبت سفارش') }}
            </button>
        </div>
    </div>
</template>

<script>
import QPreview from "./Qpreview.vue";
import CartAssayTicket from "./cart/CartAssayTicket.vue";
import CartStepper from "./cart/CartStepper.vue";
import CartAuthStep from "./cart/CartAuthStep.vue";
import CartDeliveryTypeStep from "./cart/CartDeliveryTypeStep.vue";
import CartDeliveryDetailsStep from "./cart/CartDeliveryDetailsStep.vue";
import CartReviewStep from "./cart/CartReviewStep.vue";
import CartPaymentStep from "./cart/CartPaymentStep.vue";

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
    components: {
        QPreview,
        CartAssayTicket,
        CartStepper,
        CartAuthStep,
        CartDeliveryTypeStep,
        CartDeliveryDetailsStep,
        CartReviewStep,
        CartPaymentStep,
    },
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
        discount: null,
        quoteExpiresAt: 0,
        quoteMinutes: 30,
        nowTs: Math.floor(Date.now() / 1000),
        quoteTimer: null,
        onVisibilityChange: null,
        offlinePaymentHours: 3,
        loggedIn: false,
        localAddresses: [],
        deliveryType: 'address',
        galleryAddress: '',
        productsUrl: '',
        isThirdParty: false,
        recipientName: '',
        recipientMobile: '',
        recipientNationalId: '',
        authBusy: false,
        profileForm: {
            name: '',
            mobile: '',
            address: '',
        },
        productLink: '',
        cardLink: '',
        discountLink: '',
        signInDoUrl: '',
        signUpNowUrl: '',
        sendSmsUrl: '',
        checkAuthUrl: '',
        completeProfileUrl: '',
        smsSign: false,
        customer: {},
        symbol: '$',
        transports: [],
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
        const checkExpiry = () => {
            this.nowTs = Math.floor(Date.now() / 1000);
            if (this.quoteExpiresAt > 0 && this.quoteRemaining <= 0) {
                if (this.quoteTimer) {
                    clearInterval(this.quoteTimer);
                    this.quoteTimer = null;
                }
                window.location.reload();
            }
        };
        checkExpiry();
        this.quoteTimer = setInterval(checkExpiry, 1000);
        this.onVisibilityChange = () => {
            if (!document.hidden) {
                checkExpiry();
            }
        };
        document.addEventListener('visibilitychange', this.onVisibilityChange);
    },
    beforeUnmount() {
        if (this.quoteTimer) {
            clearInterval(this.quoteTimer);
        }
        if (this.onVisibilityChange) {
            document.removeEventListener('visibilitychange', this.onVisibilityChange);
        }
    },
    computed: {
        userName() {
            return this.profileForm.name || this.customer?.name || '';
        },
        userMobile() {
            return this.profileForm.mobile || this.customer?.mobile || '';
        },
        isRecipientValid() {
            if (!this.isThirdParty) return true;
            const nameValid = !!(this.recipientName && this.recipientName.trim().length >= 2);
            const mobileValid = /^09\d{9}$/.test(this.recipientMobile?.trim() || '');
            const idValid = /^\d{10}$/.test(this.recipientNationalId?.trim() || '');
            return nameValid && mobileValid && idValid;
        },
        needsAccount() {
            if (!this.loggedIn) return true;
            if (!this.userName || !this.userMobile) return true;
            if (this.deliveryType === 'pickup') return false;
            return !this.localAddresses.length;
        },
        steps() {
            const list = [
                {
                    key: 'cart',
                    label: this.t('cart', 'سبد خرید'),
                    shortLabel: this.t('cart-short', 'سبد'),
                    icon: 'ri-shopping-bag-3-line',
                },
            ];
            if (this.needsAccount) {
                list.push({
                    key: 'account',
                    label: this.t('account', 'حساب کاربری'),
                    shortLabel: this.t('account-short', 'حساب'),
                    icon: 'ri-user-3-line',
                });
            }
            list.push({
                key: 'delivery-type',
                label: this.t('delivery-type', 'روش تحویل'),
                shortLabel: this.t('delivery-type-short', 'روش'),
                icon: 'ri-truck-line',
            });
            list.push({
                key: 'delivery-details',
                label: this.t('delivery-details', 'مشخصات تحویل'),
                shortLabel: this.t('delivery-details-short', 'مشخصات'),
                icon: 'ri-map-pin-line',
            });
            list.push({
                key: 'review',
                label: this.t('invoice-details', 'پیش‌فاکتور'),
                shortLabel: this.t('invoice-short', 'فاکتور'),
                icon: 'ri-file-list-3-line',
            });
            list.push({
                key: 'payment',
                label: this.t('payment', 'پرداخت'),
                shortLabel: this.t('payment-short', 'پرداخت'),
                icon: 'ri-bank-card-line',
            });
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
            if (this.quoteRemaining <= 0) {
                return '00:00';
            }
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
            if (this.deliveryType === 'pickup') {
                return 0;
            }
            for (const trs of (this.transports || [])) {
                if (trs.id == this.transport_index) {
                    return Number(trs.price || 0);
                }
            }
            return 0;
        },
        selectedAddress() {
            return (this.localAddresses || []).find(a => a.id == this.selectedAddressId) || null;
        },
        selectedTransport() {
            return (this.transports || []).find(t => t.id == this.transport_index) || null;
        },
        discountAmount() {
            if (!this.discount) return 0;
            if (this.discount.type === 'PERCENT') {
                return Math.round((Number(this.discount.amount || 0) * this.total) / 100);
            }
            return Math.min(this.total, Number(this.discount.amount || 0));
        },
        productsTotalAfterDiscount() {
            return Math.max(0, this.total - this.discountAmount);
        },
        totalWithTransportDiscount() {
            return this.productsTotalAfterDiscount + this.transportPrice;
        },
        displayTotal() {
            if (this.currentKey === 'cart' || this.currentKey === 'account' || this.currentKey === 'delivery-type') {
                return this.total;
            }
            if (this.currentKey === 'delivery-details') {
                return this.total + this.transportPrice;
            }
            return this.totalWithTransportDiscount;
        },
        canSubmitOrder() {
            if (!this.loggedIn || !this.userName || !this.userMobile) return false;
            if (this.deliveryType === 'pickup') return true;
            if (!this.selectedAddressId || !this.selectedAddress?.is_tehran) return false;
            return this.isRecipientValid;
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
            this.productsUrl = data.productsUrl || '';
            this.galleryAddress = data.galleryAddress || '';
            this.cardLink = data.cardLink || '';
            this.discountLink = data.discountLink || '';
            this.signInDoUrl = data.signInDoUrl || '';
            this.signUpNowUrl = data.signUpNowUrl || '';
            this.sendSmsUrl = data.sendSmsUrl || '';
            this.checkAuthUrl = data.checkAuthUrl || '';
            this.completeProfileUrl = data.completeProfileUrl || '';
            this.smsSign = !!data.smsSign;
            this.loggedIn = !!data.isLoggedIn;
            this.symbol = data.symbol || '$';
            this.bankName = data.bankName || '';
            this.bankCardNumber = data.bankCardNumber || '';
            this.bankAccountNumber = data.bankAccountNumber || '';
            this.bankSheba = data.bankSheba || '';
            this.bankAccountName = data.bankAccountName || '';
            this.transport_index = data.defTransport ?? null;
            this.items = Array.isArray(data.items) ? data.items : (data.items?.data || []);
            this.qs = Array.isArray(data.qs) ? data.qs : [];
            this.localAddresses = Array.isArray(data.addresses) ? [...data.addresses] : [];
            this.transports = Array.isArray(data.transports) ? data.transports : (data.transports?.data || []);
            this.customer = data.customer || {};
            this.translate = data.translate || {};
            if (data.quoteRemaining !== undefined && data.quoteRemaining !== null) {
                const rem = Number(data.quoteRemaining);
                this.quoteExpiresAt = rem > 0 ? this.nowTs + rem : (this.nowTs - 1);
            } else {
                this.quoteExpiresAt = Number(data.quoteExpiresAt) || 0;
            }
            this.quoteMinutes = Number(data.quoteMinutes) || 30;
            this.offlinePaymentHours = Number(data.offlinePaymentHours) || 3;
        },
        bootCartLines() {
            this.deliveryType = (this.localAddresses && this.localAddresses.length > 0) ? 'address' : 'pickup';
            this.selectedAddressId = this.localAddresses?.[0]?.id ?? null;
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
                const nextKey = this.needsAccount ? 'account' : 'delivery-type';
                this.index = this.steps.findIndex(s => s.key === nextKey);
                return;
            }
            if (this.currentKey === 'account') {
                if (!this.loggedIn || !this.userName || !this.userMobile) {
                    window.$toast?.warning?.(this.t('complete-profile', 'لطفا نام و شماره موبایل را تکمیل کنید'));
                    return;
                }
                if (this.deliveryType !== 'pickup' && !this.localAddresses.length) {
                    window.$toast?.warning?.(this.t('complete-profile', 'لطفا آدرس را تکمیل کنید'));
                    return;
                }
                this.index = this.steps.findIndex(s => s.key === 'delivery-type');
                return;
            }
            if (this.currentKey === 'delivery-type') {
                if (!['address', 'pickup'].includes(this.deliveryType)) {
                    this.deliveryType = 'address';
                }
                this.index = this.steps.findIndex(s => s.key === 'delivery-details');
                return;
            }
            if (this.currentKey === 'delivery-details') {
                if (this.deliveryType === 'pickup') {
                    this.index = this.steps.findIndex(s => s.key === 'review');
                    return;
                }
                if (!this.selectedAddressId) {
                    window.$toast?.error?.(this.t('select-address', 'یک آدرس را انتخاب کنید'));
                    return;
                }
                if (this.selectedAddress && !this.selectedAddress.is_tehran) {
                    window.$toast?.error?.(this.t('province-restriction-notice', 'ارسال مستقیم به این استان در حال حاضر مقدور نمی‌باشد. جهت ثبت سفارش می‌توانید تحویل حضوری در گالری را انتخاب کرده یا آدرس تحویل دیگری در تهران ثبت نمایید.'));
                    return;
                }
                if (!this.transport_index) {
                    window.$toast?.error?.(this.t('select-transport', 'روش ارسال را انتخاب کنید'));
                    return;
                }
                if (!this.isRecipientValid) {
                    if (!this.recipientName || this.recipientName.trim().length < 2) {
                        window.$toast?.error?.(this.t('recipient-name-required', 'نام و نام خانوادگی گیرنده را وارد کنید'));
                    } else if (!/^09\d{9}$/.test(this.recipientMobile?.trim() || '')) {
                        window.$toast?.error?.(this.t('mobile-invalid', 'فرمت شماره موبایل گیرنده نامعتبر است'));
                    } else {
                        window.$toast?.error?.(this.t('national-id-invalid', 'کد ملی گیرنده باید ۱۰ رقم باشد'));
                    }
                    return;
                }
                this.index = this.steps.findIndex(s => s.key === 'review');
                return;
            }
            if (this.currentKey === 'review') {
                this.index = this.steps.findIndex(s => s.key === 'payment');
                return;
            }
        },
        prev() {
            if (this.index > 0) {
                this.index -= 1;
            }
        },
        applyAuthSuccess(data) {
            this.loggedIn = true;
            this.localAddresses = data.addresses || [];
            if (this.localAddresses.length > 0 && !this.selectedAddressId) {
                this.selectedAddressId = this.localAddresses[0].id;
                this.deliveryType = 'address';
            }
            this.profileForm.name = data.customer?.name || this.profileForm.name;
            this.profileForm.mobile = data.customer?.mobile || this.profileForm.mobile;
            const profileComplete = !!data.profile_complete;
            if (profileComplete || (this.deliveryType === 'pickup' && this.userName && (data.customer?.mobile || this.profileForm.mobile))) {
                this.index = this.steps.findIndex(s => s.key === 'delivery-type');
            } else {
                this.index = this.steps.findIndex(s => s.key === 'account');
            }
        },
        async addAddressQuick() {
            if (!this.profileForm.address) return;
            this.authBusy = true;
            try {
                const resp = await axios.post(this.completeProfileUrl, {
                    name: this.userName || 'مشتری',
                    mobile: this.userMobile || '',
                    address: this.profileForm.address,
                    for_pickup: 0,
                }, {
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
        goToStep(key) {
            const idx = this.steps.findIndex(s => s.key === key);
            if (idx !== -1) {
                this.index = idx;
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
