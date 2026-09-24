<template>
    <section class="checkout-panel">
        <header class="panel-head">
            <h5>{{ t('invoice-details-title', 'بررسی سفارش و جزئیات فاکتور') }}</h5>
        </header>

        <div class="review-box">
            <div class="review-box-header">
                <span class="review-title">
                    <i class="ri-map-pin-user-line"></i>
                    <strong>{{ t('delivery-info', 'مشخصات تحویل گیرنده') }}</strong>
                </span>
                <button type="button" class="btn-link-action" @click="$emit('edit-step', 'delivery-details')">
                    <i class="ri-edit-line"></i>
                    {{ t('edit', 'ویرایش') }}
                </button>
            </div>
            <div class="review-box-body">
                <template v-if="deliveryType === 'pickup'">
                    <div class="review-row">
                        <i class="ri-store-line text-primary"></i>
                        <strong>{{ t('gallery-pickup', 'تحویل حضوری در گالری') }}</strong>
                    </div>
                    <div class="review-row">
                        <i class="ri-map-pin-2-line text-muted"></i>
                        <span>{{ galleryAddress || 'تهران' }}</span>
                    </div>
                    <div class="review-row" v-if="userName || userMobile">
                        <strong class="text-dark">{{ userName }}</strong>
                        <span class="review-badge" dir="ltr">{{ userMobile }}</span>
                    </div>
                </template>
                <template v-else>
                    <template v-if="isThirdParty">
                        <div class="review-row">
                            <span class="badge bg-info-subtle text-info">{{ t('third-party-recipient', 'تحویل‌گیرنده شخص دیگر') }}</span>
                        </div>
                        <div class="review-row">
                            <strong class="text-dark">{{ recipientName }}</strong>
                            <span class="review-badge" dir="ltr">{{ recipientMobile }}</span>
                            <span v-if="recipientNationalId" class="badge bg-light text-secondary border font-monospace ms-2">{{ recipientNationalId }}</span>
                        </div>
                    </template>
                    <template v-else>
                        <div class="review-row" v-if="userName || userMobile">
                            <strong class="text-dark">{{ userName }}</strong>
                            <span class="review-badge" dir="ltr">{{ userMobile }}</span>
                        </div>
                    </template>
                    <div class="review-row" v-if="selectedAddress">
                        <i class="ri-map-pin-2-line text-muted"></i>
                        <span>{{ selectedAddress.address }}</span>
                    </div>
                    <div class="review-row transport-row" v-if="selectedTransport">
                        <i class="ri-truck-line text-primary"></i>
                        <span>{{ selectedTransport.title }}</span>
                        <span class="text-muted">({{ selectedTransport.price > 0 ? priceing(selectedTransport.price) : t('free', 'رایگان') }})</span>
                    </div>
                </template>
            </div>
        </div>

        <div class="review-items-box mb-4">
            <div class="review-box-header mb-2">
                <span class="review-title">
                    <i class="ri-shopping-bag-3-line"></i>
                    <strong>{{ t('selected-products', 'محصولات انتخاب شده') }}</strong>
                </span>
                <button type="button" class="btn-link-action" @click="$emit('edit-step', 'cart')">
                    <i class="ri-edit-line"></i>
                    {{ t('edit', 'ویرایش') }}
                </button>
            </div>
            <ul class="piece-list">
                <li v-for="(item, i) in lines" :key="'rev-' + item.id + '-' + i" class="piece-row py-2">
                    <a :href="productLink + item.slug" class="piece-img-wrap" style="width: 48px; height: 48px;">
                        <img :src="item.image" :alt="item.name" class="piece-img" style="width: 48px; height: 48px; object-fit: cover; border-radius: .5rem;">
                    </a>
                    <div class="piece-body">
                        <a class="piece-name fs-13" :href="productLink + item.slug">{{ item.name }}</a>
                        <small v-if="item.q" class="text-muted fs-11 d-block">
                            {{ t('weight', 'وزن') }}: {{ item.q.weight }}
                            <template v-if="item.q.code"> · {{ t('code', 'کد') }}: {{ item.q.code }}</template>
                        </small>
                    </div>
                    <div class="text-end">
                        <strong class="piece-price fs-13">{{ priceing(pricez[i]) }}</strong>
                    </div>
                </li>
            </ul>
        </div>

        <div class="discount-box">
            <template v-if="!discount">
                <button type="button" class="discount-toggle" @click="discountCollapsed = !discountCollapsed">
                    <div class="discount-toggle-label">
                        <i class="ri-coupon-3-line"></i>
                        <span>{{ t('have-discount', 'کد تخفیف دارید؟') }}</span>
                    </div>
                    <i :class="discountCollapsed ? 'ri-arrow-down-s-line' : 'ri-arrow-up-s-line'"></i>
                </button>
                <div v-show="!discountCollapsed" class="discount-form">
                    <div class="discount-input-wrap">
                        <input type="text"
                               :placeholder="t('enter-discount-code', 'کد تخفیف را وارد کنید')"
                               v-model="code"
                               @keyup.enter.prevent="discountCheck">
                        <button type="button" class="btn-discount-apply" :disabled="discountLoading || !code.trim()" @click="discountCheck">
                            <span v-if="discountLoading" class="spinner-border spinner-border-sm me-1" role="status"></span>
                            <span>{{ t('apply-discount', 'اعمال کد') }}</span>
                        </button>
                    </div>
                </div>
            </template>
            <template v-else>
                <div class="discount-applied-card">
                    <div class="discount-applied-info">
                        <i class="ri-checkbox-circle-fill text-success"></i>
                        <div>
                            <strong class="discount-human-text">{{ discountHuman }}</strong>
                            <span class="discount-code-tag">{{ code }}</span>
                        </div>
                    </div>
                    <button type="button" class="btn-remove-discount" @click="removeDiscount" :title="t('remove-discount', 'حذف کد تخفیف')">
                        <i class="ri-delete-bin-line"></i>
                        <span>{{ t('remove', 'حذف') }}</span>
                    </button>
                </div>
                <input type="hidden" name="discount_id" :value="discount.id">
            </template>
        </div>

        <div class="order-notes-box">
            <label class="order-notes-label">
                <i class="ri-file-text-line"></i>
                <span>{{ t('extra-desc', 'توضیحات سفارش') }}</span>
                <span class="badge-optional">{{ t('optional', 'اختیاری') }}</span>
            </label>
            <textarea rows="2" class="full" name="desc" :placeholder="t('order-notes-hint', 'یادداشت اختیاری درباره نحوه ارسال یا بسته‌بندی...')"></textarea>
        </div>

        <button type="button" class="btn-ghost mt panel-back" @click="$emit('prev')">{{ t('back', 'بازگشت') }}</button>
    </section>
</template>

<script>
export default {
    name: 'CartReviewStep',
    props: {
        deliveryType: { type: String, default: 'address' },
        galleryAddress: { type: String, default: '' },
        userName: { type: String, default: '' },
        userMobile: { type: String, default: '' },
        selectedAddress: { type: Object, default: null },
        selectedTransport: { type: Object, default: null },
        isThirdParty: { type: Boolean, default: false },
        recipientName: { type: String, default: '' },
        recipientMobile: { type: String, default: '' },
        recipientNationalId: { type: String, default: '' },
        lines: { type: Array, default: () => [] },
        pricez: { type: Array, default: () => [] },
        productLink: { type: String, default: '' },
        discountLink: { type: String, default: '' },
        discount: { type: Object, default: null },
        priceing: { type: Function, required: true },
        t: { type: Function, required: true },
    },
    emits: ['discount-applied', 'discount-removed', 'edit-step', 'prev'],
    data: () => ({
        code: '',
        discountCollapsed: true,
        discountLoading: false,
        discountHuman: '',
    }),
    methods: {
        async discountCheck() {
            const rawCode = (this.code || '').trim();
            if (!rawCode) {
                window.$toast?.warning?.(this.t('enter-discount-code', 'لطفاً کد تخفیف را وارد کنید'));
                return;
            }
            this.discountLoading = true;
            try {
                const resp = await axios.get(this.discountLink + encodeURIComponent(rawCode));
                if (!resp.data.OK) {
                    window.$toast?.error?.(resp.data.err || this.t('discount-invalid', 'کد تخفیف معتبر نیست'));
                } else {
                    window.$toast?.success?.(resp.data.msg || this.t('discount-applied', 'کد تخفیف اعمال شد'));
                    this.discountHuman = resp.data.human;
                    this.discountCollapsed = true;
                    this.$emit('discount-applied', resp.data.data);
                }
            } catch (e) {
                window.$toast?.error?.(e.response?.data?.message || e.message);
            } finally {
                this.discountLoading = false;
            }
        },
        removeDiscount() {
            this.discountHuman = '';
            this.code = '';
            this.discountCollapsed = true;
            this.$emit('discount-removed');
        },
    },
}
</script>
