<template>
    <section class="checkout-panel">
        <header class="panel-head">
            <h5>{{ t('payment-details', 'اطلاعات پرداخت') }}</h5>
        </header>

        <div class="payment-method-box">
            <input type="hidden" name="payment_method" value="card">
            <div class="pay-option active">
                <i class="ri-bank-card-line pay-icon"></i>
                <div>
                    <strong>{{ t('card-pay', 'کارت به کارت') }}</strong>
                    <small>{{ t('card-pay-hint', 'واریز به کارت و انتظار تایید فروشگاه') }}</small>
                </div>
            </div>

            <div class="bank-box">
                <div class="bank-box-header">
                    <div class="bank-box-title">
                        <i class="ri-bank-line"></i>
                        <h5>{{ t('bank-info', 'اطلاعات کارت‌به‌کارت') }}</h5>
                    </div>
                    <span v-if="bankName" class="bank-tag">{{ bankName }}</span>
                </div>

                <div v-if="bankAccountName" class="bank-row">
                    <span>{{ t('account-name', 'به‌نام') }}</span>
                    <strong>{{ bankAccountName }}</strong>
                </div>
                <div v-if="bankCardNumber" class="bank-row bank-row-highlight">
                    <span>{{ t('card-number', 'شماره کارت') }}</span>
                    <strong dir="ltr" class="font-monospace">{{ formatCardNumber(bankCardNumber) }}</strong>
                    <button type="button" class="copy-btn" @click="copyText(bankCardNumber, 'card')">
                        <i :class="copiedKey === 'card' ? 'ri-check-line text-success' : 'ri-file-copy-line'"></i>
                        <span>{{ copiedKey === 'card' ? t('copied', 'کپی شد') : t('copy', 'کپی') }}</span>
                    </button>
                </div>
                <div v-if="bankSheba" class="bank-row">
                    <span>{{ t('sheba', 'شبا') }}</span>
                    <strong dir="ltr" class="font-monospace fs-12">{{ bankSheba }}</strong>
                    <button type="button" class="copy-btn" @click="copyText(bankSheba, 'sheba')">
                        <i :class="copiedKey === 'sheba' ? 'ri-check-line text-success' : 'ri-file-copy-line'"></i>
                        <span>{{ copiedKey === 'sheba' ? t('copied', 'کپی شد') : t('copy', 'کپی') }}</span>
                    </button>
                </div>
                <div v-if="bankAccountNumber" class="bank-row">
                    <span>{{ t('account-number', 'شماره حساب') }}</span>
                    <strong dir="ltr" class="font-monospace">{{ bankAccountNumber }}</strong>
                    <button type="button" class="copy-btn" @click="copyText(bankAccountNumber, 'account')">
                        <i :class="copiedKey === 'account' ? 'ri-check-line text-success' : 'ri-file-copy-line'"></i>
                        <span>{{ copiedKey === 'account' ? t('copied', 'کپی شد') : t('copy', 'کپی') }}</span>
                    </button>
                </div>
                <p class="muted">
                    <i class="ri-information-line"></i>
                    {{ t('card-wait-hint', 'پس از ثبت سفارش، مبلغ را واریز کنید تا سفارش تایید شود.') }}
                </p>
            </div>
        </div>

        <p v-if="!canSubmitOrder" class="warn">
            <template v-if="!loggedIn || (!customerName && !profileForm.name) || (!customer?.mobile && !profileForm.mobile)">
                {{ t('plz', 'لطفا وارد شوید یا اطلاعات ضروری را تکمیل کنید') }}
            </template>
            <template v-else-if="deliveryType === 'address' && selectedAddress && !selectedAddress.is_tehran">
                {{ t('province-restriction-notice', 'ارسال مستقیم به این استان در حال حاضر مقدور نمی‌باشد.') }}
            </template>
            <template v-else-if="deliveryType === 'address' && isThirdParty && (!recipientName || !recipientMobile || !recipientNationalId)">
                {{ t('recipient-name-required', 'لطفا اطلاعات گیرنده را کامل وارد کنید') }}
            </template>
            <template v-else>
                {{ t('plz', 'لطفا وارد شوید یا اطلاعات ضروری را تکمیل کنید') }}
            </template>
        </p>
        <button type="button" class="btn-ghost mt panel-back" @click="$emit('prev')">{{ t('back', 'بازگشت') }}</button>
    </section>
</template>

<script>
export default {
    name: 'CartPaymentStep',
    props: {
        bankName: { type: String, default: '' },
        bankAccountName: { type: String, default: '' },
        bankCardNumber: { type: String, default: '' },
        bankSheba: { type: String, default: '' },
        bankAccountNumber: { type: String, default: '' },
        canSubmitOrder: { type: Boolean, default: false },
        loggedIn: { type: Boolean, default: false },
        customerName: { type: String, default: '' },
        profileForm: { type: Object, default: () => ({}) },
        customer: { type: Object, default: () => ({}) },
        deliveryType: { type: String, default: 'address' },
        selectedAddress: { type: Object, default: null },
        isThirdParty: { type: Boolean, default: false },
        recipientName: { type: String, default: '' },
        recipientMobile: { type: String, default: '' },
        recipientNationalId: { type: String, default: '' },
        t: { type: Function, required: true },
    },
    emits: ['prev'],
    data: () => ({
        copiedKey: null,
        copyTimer: null,
    }),
    beforeUnmount() {
        if (this.copyTimer) {
            clearTimeout(this.copyTimer);
        }
    },
    methods: {
        formatCardNumber(num) {
            if (!num) return '';
            const cleaned = String(num).replace(/\s+/g, '');
            return cleaned.replace(/(\d{4})(?=\d)/g, '$1-');
        },
        async copyText(value, key) {
            if (!value) return;
            const cleanText = String(value).replace(/\s/g, '');
            let copied = false;
            if (navigator.clipboard && window.isSecureContext) {
                try {
                    await navigator.clipboard.writeText(cleanText);
                    copied = true;
                } catch (e) {
                    copied = false;
                }
            }
            if (!copied) {
                try {
                    const textarea = document.createElement('textarea');
                    textarea.value = cleanText;
                    textarea.style.position = 'fixed';
                    textarea.style.top = '-9999px';
                    textarea.style.left = '-9999px';
                    textarea.setAttribute('readonly', '');
                    document.body.appendChild(textarea);
                    textarea.focus();
                    textarea.select();
                    copied = document.execCommand('copy');
                    document.body.removeChild(textarea);
                } catch (err) {
                    copied = false;
                }
            }

            if (copied) {
                this.copiedKey = key;
                window.$toast?.success?.(this.t('copied', 'کپی شد'));
                if (this.copyTimer) {
                    clearTimeout(this.copyTimer);
                }
                this.copyTimer = setTimeout(() => {
                    if (this.copiedKey === key) {
                        this.copiedKey = null;
                    }
                }, 1800);
            } else {
                window.$toast?.error?.(this.t('copy-failed', 'کپی نشد'));
            }
        },
    },
}
</script>
