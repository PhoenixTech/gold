<template>
    <section class="checkout-panel">
        <header class="panel-head">
            <h5>{{ t('delivery-type-title', 'انتخاب شیوه تحویل') }}</h5>
        </header>

        <div class="delivery-method-selector mb-4">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <button
                        type="button"
                        class="btn w-100 p-3 text-start border rounded-3 d-flex align-items-center gap-3 h-100"
                        :class="modelValue === 'address' ? 'btn-primary text-white shadow-sm' : 'btn-outline-secondary bg-white text-dark'"
                        @click="$emit('update:modelValue', 'address')"
                    >
                        <div class="rounded-circle p-2 d-flex align-items-center justify-content-center flex-shrink-0" :class="modelValue === 'address' ? 'bg-white text-primary' : 'bg-light text-muted'" style="width: 48px; height: 48px;">
                            <i class="ri-truck-line fs-3"></i>
                        </div>
                        <div class="flex-grow-1">
                            <strong class="fs-15 d-block mb-1">{{ t('delivery-to-address', 'ارسال به آدرس') }}</strong>
                            <small class="d-block" :class="modelValue === 'address' ? 'text-white-50' : 'text-muted'">{{ t('express-courier-tehran', 'ارسال با پیک اختصاصی در تهران') }}</small>
                        </div>
                        <i v-if="modelValue === 'address'" class="ri-checkbox-circle-fill fs-4 flex-shrink-0"></i>
                    </button>
                </div>
                <div class="col-12 col-md-6">
                    <button
                        type="button"
                        class="btn w-100 p-3 text-start border rounded-3 d-flex align-items-center gap-3 h-100"
                        :class="modelValue === 'pickup' ? 'btn-primary text-white shadow-sm' : 'btn-outline-secondary bg-white text-dark'"
                        @click="$emit('update:modelValue', 'pickup')"
                    >
                        <div class="rounded-circle p-2 d-flex align-items-center justify-content-center flex-shrink-0" :class="modelValue === 'pickup' ? 'bg-white text-primary' : 'bg-light text-muted'" style="width: 48px; height: 48px;">
                            <i class="ri-store-2-line fs-3"></i>
                        </div>
                        <div class="flex-grow-1">
                            <strong class="fs-15 d-block mb-1">{{ t('gallery-pickup', 'تحویل حضوری') }}</strong>
                            <small class="d-block" :class="modelValue === 'pickup' ? 'text-white-50' : 'text-muted'">{{ t('gallery-pickup-direct', 'تحویل مستقیم در شووروم گالری') }}</small>
                        </div>
                        <i v-if="modelValue === 'pickup'" class="ri-checkbox-circle-fill fs-4 flex-shrink-0"></i>
                    </button>
                </div>
            </div>
        </div>
        <input type="hidden" name="delivery_type" :value="modelValue">

        <button type="button" class="btn-ghost mt panel-back" @click="$emit('prev')">{{ t('back', 'بازگشت') }}</button>
    </section>
</template>

<script>
export default {
    name: 'CartDeliveryTypeStep',
    props: {
        modelValue: { type: String, default: 'address' },
        t: { type: Function, required: true },
    },
    emits: ['update:modelValue', 'prev'],
}
</script>
