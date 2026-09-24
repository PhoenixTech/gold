<template>
    <section class="checkout-panel">
        <header class="panel-head">
            <h5 v-if="deliveryType === 'pickup'">{{ t('gallery-pickup-details', 'مشخصات تحویل حضوری') }}</h5>
            <h5 v-else>{{ t('address-and-transport', 'آدرس و شیوه ارسال') }}</h5>
        </header>

        <div v-if="deliveryType === 'pickup'" class="gallery-pickup-box mb-4">
            <div class="p-3 p-md-4 rounded-3 border bg-light">
                <div class="d-flex align-items-start gap-3">
                    <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                        <i class="ri-store-3-line fs-4"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="fw-bold mb-1 text-dark">{{ t('gallery-pickup', 'تحویل حضوری در گالری') }}</h6>
                        <p class="text-muted fs-13 mb-3">{{ t('gallery-pickup-desc', 'تحویل حضوری سفارش در شوروم و گالری') }}</p>

                        <div class="bg-white p-3 rounded-2 border mb-3">
                            <span class="text-secondary fs-12 fw-bold d-block mb-1">
                                <i class="ri-map-pin-line text-primary me-1"></i>
                                {{ t('gallery-address', 'نشانی گالری') }}:
                            </span>
                            <p class="mb-0 text-dark fs-14 fw-medium">{{ galleryAddress || 'تهران' }}</p>
                        </div>

                        <div class="p-2 px-3 rounded-2 bg-white border d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <span class="text-muted fs-12">{{ t('buyer-details', 'مشخصات تحویل‌گیرنده') }}:</span>
                            <div class="d-flex align-items-center gap-2">
                                <strong class="text-dark fs-13">{{ userName }}</strong>
                                <span class="badge bg-secondary-subtle text-secondary" dir="ltr">{{ userMobile }}</span>
                            </div>
                        </div>

                        <small class="text-muted d-block mt-2 fs-12">
                            <i class="ri-information-line text-info me-1"></i>
                            {{ t('gallery-pickup-notice', 'پس از آماده‌سازی سفارش، می‌توانید با همراه داشتن کارت ملی به نشانی فوق مراجعه نمایید.') }}
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="address-delivery-box">
            <h4>{{ t('sent-to', 'ارسال به') }}</h4>
            <div v-if="!localAddresses.length" class="inline-address auth-form">
                <p class="hint">{{ t('no-address', 'آدرسی ثبت نشده است.') }}</p>
                <label>
                    {{ t('address', 'آدرس') }}
                    <textarea :value="profileAddress" @input="$emit('update:profileAddress', $event.target.value)" rows="3"></textarea>
                </label>
                <button type="button" class="btn-secondary-cta" :disabled="authBusy" @click="$emit('add-address-quick')">
                    {{ t('add-address', 'افزودن آدرس') }}
                </button>
            </div>
            <div v-for="adr in localAddresses" :key="adr.id" class="choice" :class="{ selected: selectedAddressId == adr.id }">
                <label>
                    <input type="radio" name="address_id" :value="adr.id" :checked="selectedAddressId == adr.id" @change="$emit('update:selectedAddressId', adr.id)">
                    <span>{{ adr.address }}</span>
                </label>
            </div>

            <div v-if="selectedAddress" class="mt-3">
                <div v-if="selectedAddress.is_tehran" class="alert alert-success border border-success-subtle shadow-sm d-flex align-items-center gap-2 p-3 rounded-3 mb-0">
                    <i class="ri-checkbox-circle-fill text-success fs-4"></i>
                    <div class="fs-13 text-success-emphasis">
                        <strong>{{ t('tehran-delivery-notice', 'سفارش شما ظرف ۴۸ ساعت کاری ارسال خواهد شد.') }}</strong>
                    </div>
                </div>
                <div v-else class="alert alert-danger border border-danger-subtle shadow-sm d-flex align-items-start gap-2 p-3 rounded-3 mb-0">
                    <i class="ri-error-warning-fill text-danger fs-4 flex-shrink-0"></i>
                    <div class="fs-13 text-danger-emphasis">
                        <strong>{{ t('province-restriction-notice', 'ارسال مستقیم به این استان در حال حاضر مقدور نمی‌باشد. جهت ثبت سفارش می‌توانید تحویل حضوری در گالری را انتخاب کرده یا آدرس تحویل دیگری در تهران ثبت نمایید.') }}</strong>
                    </div>
                </div>
            </div>

            <h4 class="mt-4">{{ t('transport', 'ارسال') }}</h4>
            <div v-for="trs in transports" :key="trs.id" class="choice choice-transport" :class="{ selected: transportIndex == trs.id }">
                <label>
                    <input type="radio" name="transport_id" :value="trs.id" :checked="transportIndex == trs.id" @change="$emit('update:transportIndex', trs.id)">
                    <span>
                        <strong>{{ trs.title }}</strong>
                        <small v-if="trs.description">{{ trs.description }}</small>
                    </span>
                    <em>{{ priceing(trs.price) }}</em>
                </label>
            </div>

            <div class="third-party-box mt-4 p-3 rounded-3 border bg-light">
                <label class="d-flex align-items-center gap-2 cursor-pointer mb-0">
                    <input type="checkbox" :checked="isThirdParty" @change="$emit('update:isThirdParty', $event.target.checked)" class="form-check-input mt-0">
                    <span class="fw-bold fs-14 text-dark">{{ t('third-party-recipient', 'سفارش به شخص دیگری تحویل داده شود') }}</span>
                </label>
                <input type="hidden" name="is_third_party" :value="isThirdParty ? '1' : '0'">

                <div v-if="isThirdParty" class="third-party-form mt-3 pt-3 border-top">
                    <div class="alert alert-info border border-info-subtle p-2 mb-3 rounded-2 fs-12 d-flex align-items-start gap-2">
                        <i class="ri-shield-user-line text-info fs-5 flex-shrink-0"></i>
                        <span>{{ t('recipient-privacy-notice', 'اطلاعات گیرنده صرفاً جهت امنیت و احراز هویت تحویل سفارش دریافت می‌شود و به عنوان اطلاعات حساب ذخیره نخواهد شد.') }}</span>
                    </div>
                    <div class="row g-2">
                        <div class="col-12 col-md-4">
                            <label class="form-label fs-12 fw-medium mb-1 text-muted">{{ t('recipient-name', 'نام و نام خانوادگی گیرنده') }}</label>
                            <input type="text" name="recipient_name" :value="recipientName" @input="$emit('update:recipientName', $event.target.value)" class="form-control form-control-sm" :placeholder="t('recipient-name-ph', 'نام کامل گیرنده')">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fs-12 fw-medium mb-1 text-muted">{{ t('recipient-mobile', 'شماره موبایل گیرنده') }}</label>
                            <input type="tel" name="recipient_mobile" :value="recipientMobile" @input="$emit('update:recipientMobile', $event.target.value)" class="form-control form-control-sm" dir="ltr" maxlength="11" placeholder="09xxxxxxxxx">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fs-12 fw-medium mb-1 text-muted">{{ t('recipient-national-id', 'کد ملی گیرنده') }}</label>
                            <input type="text" name="recipient_national_id" :value="recipientNationalId" @input="$emit('update:recipientNationalId', $event.target.value)" class="form-control form-control-sm" dir="ltr" maxlength="10" placeholder="۱۰ رقمی">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <button type="button" class="btn-ghost mt panel-back" @click="$emit('prev')">{{ t('back', 'بازگشت') }}</button>
    </section>
</template>

<script>
export default {
    name: 'CartDeliveryDetailsStep',
    props: {
        deliveryType: { type: String, default: 'address' },
        galleryAddress: { type: String, default: '' },
        userName: { type: String, default: '' },
        userMobile: { type: String, default: '' },
        localAddresses: { type: Array, default: () => [] },
        selectedAddressId: { type: [Number, String], default: null },
        selectedAddress: { type: Object, default: null },
        transports: { type: Array, default: () => [] },
        transportIndex: { type: [Number, String], default: null },
        isThirdParty: { type: Boolean, default: false },
        recipientName: { type: String, default: '' },
        recipientMobile: { type: String, default: '' },
        recipientNationalId: { type: String, default: '' },
        profileAddress: { type: String, default: '' },
        authBusy: { type: Boolean, default: false },
        priceing: { type: Function, required: true },
        t: { type: Function, required: true },
    },
    emits: [
        'update:selectedAddressId',
        'update:transportIndex',
        'update:isThirdParty',
        'update:recipientName',
        'update:recipientMobile',
        'update:recipientNationalId',
        'update:profileAddress',
        'add-address-quick',
        'prev',
    ],
}
</script>
