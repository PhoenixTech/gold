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
                <span>{{ quoteRemaining <= 0 ? t('quote-expired', 'در حال بروزرسانی...') : t('quote-remaining', 'زمان باقی‌مانده') }}</span>
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

                    <button type="button" class="btn-ghost mt panel-back" @click="prev">{{ t('back', 'بازگشت') }}</button>
                </section>

                <section v-show="currentKey === 'delivery'" class="checkout-panel">
                    <header class="panel-head">
                        <h5>{{ t('transport', 'ارسال و تحویل') }}</h5>
                    </header>

                    <div class="delivery-method-selector mb-4">
                        <div class="row g-2">
                            <div class="col-6">
                                <button
                                    type="button"
                                    class="btn w-100 py-3 text-center border rounded-3 d-flex flex-column align-items-center justify-content-center gap-2"
                                    :class="deliveryType === 'address' ? 'btn-primary text-white shadow-sm' : 'btn-outline-secondary bg-white text-dark'"
                                    @click="setDeliveryType('address')"
                                >
                                    <i class="ri-truck-line fs-4"></i>
                                    <strong class="fs-14">{{ t('delivery-to-address', 'ارسال به آدرس') }}</strong>
                                </button>
                            </div>
                            <div class="col-6">
                                <button
                                    type="button"
                                    class="btn w-100 py-3 text-center border rounded-3 d-flex flex-column align-items-center justify-content-center gap-2"
                                    :class="deliveryType === 'pickup' ? 'btn-primary text-white shadow-sm' : 'btn-outline-secondary bg-white text-dark'"
                                    @click="setDeliveryType('pickup')"
                                >
                                    <i class="ri-store-2-line fs-4"></i>
                                    <strong class="fs-14">{{ t('gallery-pickup', 'تحویل حضوری') }}</strong>
                                </button>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="delivery_type" :value="deliveryType">

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
                                            <strong class="text-dark fs-13">{{ customerName || profileForm.name }}</strong>
                                            <span class="badge bg-secondary-subtle text-secondary" dir="ltr">{{ customer?.mobile || profileForm.mobile }}</span>
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

                        <div class="third-party-box mt-4 p-3 rounded-3 border bg-light">
                            <label class="d-flex align-items-center gap-2 cursor-pointer mb-0">
                                <input type="checkbox" v-model="isThirdParty" class="form-check-input mt-0">
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
                                        <input type="text" name="recipient_name" v-model="recipientName" class="form-control form-control-sm" :placeholder="t('recipient-name-ph', 'نام کامل گیرنده')">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label fs-12 fw-medium mb-1 text-muted">{{ t('recipient-mobile', 'شماره موبایل گیرنده') }}</label>
                                        <input type="tel" name="recipient_mobile" v-model="recipientMobile" class="form-control form-control-sm" dir="ltr" maxlength="11" placeholder="09xxxxxxxxx">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label fs-12 fw-medium mb-1 text-muted">{{ t('recipient-national-id', 'کد ملی گیرنده') }}</label>
                                        <input type="text" name="recipient_national_id" v-model="recipientNationalId" class="form-control form-control-sm" dir="ltr" maxlength="10" placeholder="۱۰ رقمی">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn-ghost mt panel-back" @click="prev">{{ t('back', 'بازگشت') }}</button>
                </section>

                <section v-show="currentKey === 'payment'" class="checkout-panel">
                    <header class="panel-head">
                        <h5>{{ t('order-review', 'بررسی نهایی و پرداخت') }}</h5>
                    </header>

                    <div class="review-box">
                        <div class="review-box-header">
                            <span class="review-title">
                                <i class="ri-map-pin-user-line"></i>
                                <strong>{{ t('delivery-info', 'مشخصات تحویل گیرنده') }}</strong>
                            </span>
                            <button type="button" class="btn-link-action" @click="goToStep('delivery')">
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
                                <div class="review-row" v-if="customerName || profileForm.name || customer?.mobile || profileForm.mobile">
                                    <strong class="text-dark">{{ customerName || profileForm.name }}</strong>
                                    <span class="review-badge" dir="ltr">{{ customer?.mobile || profileForm.mobile }}</span>
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
                                    <div class="review-row" v-if="customerName || profileForm.name || customer?.mobile || profileForm.mobile">
                                        <strong class="text-dark">{{ customerName || profileForm.name }}</strong>
                                        <span class="review-badge" dir="ltr">{{ customer?.mobile || profileForm.mobile }}</span>
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

                    <div class="discount-box">
                        <template v-if="discount == null">
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
                                        <strong class="discount-human-text">{{ discount_human }}</strong>
                                        <span class="discount-code-tag">{{ code }}</span>
                                    </div>
                                </div>
                                <button type="button" class="btn-remove-discount" @click="removeDiscount" :title="t('remove-discount', 'حذف کد تخفیف')">
                                    <i class="ri-delete-bin-line"></i>
                                    <span>{{ t('remove', 'حذف') }}</span>
                                </button>
                            </div>
                            <input type="hidden" name="discount_id" :value="discount_id">
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
                    <button type="button" class="btn-ghost mt panel-back" @click="prev">{{ t('back', 'بازگشت') }}</button>
                </section>
            </div>

            <aside class="checkout-aside">
                <p class="aside-label">{{ t('payable', 'قابل پرداخت') }}</p>
                <p class="aside-total">{{ priceing(displayTotal) }}</p>
                <ul class="aside-lines">
                    <li><span>{{ t('products-total', 'جمع کالاها') }}</span><span>{{ priceing(total) }}</span></li>
                    <li v-if="discountAmount > 0" class="discount-line"><span>{{ t('discount', 'تخفیف') }}</span><span>- {{ priceing(discountAmount) }}</span></li>
                    <li v-if="currentKey !== 'cart' && currentKey !== 'account'"><span>{{ t('transport', 'ارسال') }}</span><span>{{ transportPrice > 0 ? priceing(transportPrice) : t('free', 'رایگان') }}</span></li>
                </ul>
                <slot></slot>
                <button v-if="currentKey !== 'payment'" type="button" class="btn-primary-cta wide aside-cta" @click="next">
                    {{ t('continue', 'ادامه') }}
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
                {{ t('continue', 'ادامه') }}
            </button>
            <button v-else-if="canSubmitOrder" type="submit" class="btn-primary-cta">
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
        discountCollapsed: true,
        discountLoading: false,
        paymentMethod: 'card',
        quoteExpiresAt: 0,
        quoteMinutes: 30,
        nowTs: Math.floor(Date.now() / 1000),
        quoteTimer: null,
        onVisibilityChange: null,
        copiedKey: null,
        copyTimer: null,
        offlinePaymentHours: 3,
        loggedIn: false,
        profileCompleteLocal: false,
        canPayLocal: false,
        localAddresses: [],
        deliveryType: 'address',
        galleryAddress: '',
        productsUrl: '',
        isThirdParty: false,
        recipientName: '',
        recipientMobile: '',
        recipientNationalId: '',
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
        if (this.copyTimer) {
            clearTimeout(this.copyTimer);
        }
    },
    computed: {
        needsAccount() {
            if (!this.loggedIn) return true;
            const hasName = !!(this.customerName || this.profileForm.name);
            const hasMobile = !!(this.customer?.mobile || this.profileForm.mobile);
            if (!hasName || !hasMobile) return true;
            if (this.deliveryType === 'pickup') return false;
            return !this.localAddresses.length;
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
            if (this.currentKey === 'cart' || this.currentKey === 'account') {
                return this.total;
            }
            if (this.currentKey === 'delivery') {
                return this.total + this.transportPrice;
            }
            return this.totalWithTransportDiscount;
        },
        canSubmitOrder() {
            if (!this.loggedIn) return false;
            const hasName = !!(this.customerName || this.profileForm.name);
            const hasMobile = !!(this.customer?.mobile || this.profileForm.mobile);
            if (!hasName || !hasMobile) return false;

            if (this.deliveryType === 'pickup') {
                return true;
            }

            if (!this.selectedAddressId || !this.selectedAddress?.is_tehran) {
                return false;
            }

            if (this.isThirdParty) {
                const nameValid = !!(this.recipientName && this.recipientName.trim().length >= 2);
                const mobileValid = /^09\d{9}$/.test(this.recipientMobile?.trim() || '');
                const idValid = /^\d{10}$/.test(this.recipientNationalId?.trim() || '');
                return nameValid && mobileValid && idValid;
            }

            return true;
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
            this.loggedIn = this.isLoggedIn;
            this.profileCompleteLocal = this.profileComplete;
            this.canPayLocal = this.canPay;
            this.localAddresses = Array.isArray(this.addresses) ? [...this.addresses] : [];
            this.transport_index = this.defTransport;
            this.deliveryType = (this.localAddresses && this.localAddresses.length > 0) ? 'address' : 'pickup';
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
        setDeliveryType(type) {
            this.deliveryType = type;
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
                const hasName = !!(this.customerName || this.profileForm.name);
                const hasMobile = !!(this.customer?.mobile || this.profileForm.mobile);
                if (!this.loggedIn || !hasName || !hasMobile) {
                    window.$toast?.warning?.(this.t('complete-profile', 'لطفا نام و شماره موبایل را تکمیل کنید'));
                    return;
                }
                if (this.deliveryType !== 'pickup' && !this.localAddresses.length) {
                    window.$toast?.warning?.(this.t('complete-profile', 'لطفا آدرس را تکمیل کنید'));
                    return;
                }
                this.index = this.steps.findIndex(s => s.key === 'delivery');
                return;
            }
            if (this.currentKey === 'delivery') {
                if (this.deliveryType === 'pickup') {
                    this.index = this.steps.findIndex(s => s.key === 'payment');
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
                if (this.isThirdParty) {
                    if (!this.recipientName || this.recipientName.trim().length < 2) {
                        window.$toast?.error?.(this.t('recipient-name-required', 'نام و نام خانوادگی گیرنده را وارد کنید'));
                        return;
                    }
                    if (!/^09\d{9}$/.test(this.recipientMobile?.trim() || '')) {
                        window.$toast?.error?.(this.t('mobile-invalid', 'فرمت شماره موبایل گیرنده نامعتبر است'));
                        return;
                    }
                    if (!/^\d{10}$/.test(this.recipientNationalId?.trim() || '')) {
                        window.$toast?.error?.(this.t('national-id-invalid', 'کد ملی گیرنده باید ۱۰ رقم باشد'));
                        return;
                    }
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
            if (this.localAddresses.length > 0 && !this.selectedAddressId) {
                this.selectedAddressId = this.localAddresses[0].id;
                this.deliveryType = 'address';
            }
            this.customerName = data.customer?.name || '';
            this.profileForm.name = data.customer?.name || this.profileForm.name;
            this.profileForm.mobile = data.customer?.mobile || this.profileForm.mobile;
            if (this.profileCompleteLocal || (this.deliveryType === 'pickup' && (this.customerName || this.profileForm.name) && (data.customer?.mobile || this.profileForm.mobile))) {
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
                    for_pickup: this.deliveryType === 'pickup' ? 1 : 0,
                };
                if (!this.localAddresses.length && this.profileForm.address) {
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
                    this.discount_id = resp.data.data.id;
                    this.discount_human = resp.data.human;
                    this.discount = resp.data.data;
                    this.discountCollapsed = true;
                }
            } catch (e) {
                window.$toast?.error?.(e.response?.data?.message || e.message);
            } finally {
                this.discountLoading = false;
            }
        },
        removeDiscount() {
            this.discount = null;
            this.discount_id = null;
            this.discount_human = '';
            this.code = '';
            this.discountCollapsed = true;
        },
        formatCardNumber(num) {
            if (!num) return '';
            const cleaned = String(num).replace(/\s+/g, '');
            return cleaned.replace(/(\d{4})(?=\d)/g, '$1-');
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
        async copyText(value, key) {
            if (!value) {
                return;
            }
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

.review-box {
    border: 1px solid var(--ck-line);
    border-radius: .75rem;
    background: #fff;
    margin-bottom: 1rem;
    overflow: hidden;
}

.review-box-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: .75rem 1rem;
    background: var(--ck-soft);
    border-bottom: 1px solid var(--ck-line);
}

.review-title {
    display: flex;
    align-items: center;
    gap: .5rem;
    font-size: .88rem;
    color: var(--ck-ink);
}

.review-title i {
    color: var(--ck-accent);
    font-size: 1.1rem;
}

.btn-link-action {
    display: inline-flex;
    align-items: center;
    gap: .25rem;
    background: none;
    border: none;
    color: var(--ck-accent);
    font-size: .82rem;
    font-weight: 600;
    cursor: pointer;
    padding: 0;
}

.btn-link-action:hover {
    text-decoration: underline;
}

.review-box-body {
    padding: .85rem 1rem;
    display: grid;
    gap: .5rem;
    font-size: .88rem;
}

.review-row {
    display: flex;
    align-items: center;
    gap: .5rem;
    color: var(--ck-ink);
}

.review-badge {
    background: var(--ck-soft);
    border: 1px solid var(--ck-line);
    padding: .15rem .45rem;
    border-radius: .35rem;
    font-size: .78rem;
    color: var(--ck-muted);
}

.transport-row {
    padding-top: .5rem;
    margin-top: .25rem;
    border-top: 1px dashed var(--ck-line);
}

.discount-box {
    margin-bottom: 1rem;
}

.discount-toggle {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #fff;
    border: 1px solid var(--ck-line);
    border-radius: .75rem;
    padding: .75rem 1rem;
    font-weight: 600;
    font-size: .9rem;
    color: var(--ck-ink);
    cursor: pointer;
    transition: all .2s ease;
}

.discount-toggle:hover {
    border-color: var(--ck-accent);
    background: color-mix(in srgb, var(--ck-accent) 4%, white);
}

.discount-toggle-label {
    display: flex;
    align-items: center;
    gap: .5rem;
}

.discount-toggle-label i {
    color: var(--ck-accent);
    font-size: 1.15rem;
}

.discount-form {
    margin-top: .5rem;
    background: #fff;
    border: 1px solid var(--ck-line);
    border-radius: .75rem;
    padding: .75rem;
}

.discount-input-wrap {
    display: flex;
    gap: .5rem;
}

.discount-input-wrap input {
    flex: 1;
    border: 1px solid var(--ck-line);
    border-radius: .65rem;
    padding: .65rem .85rem;
    font-size: .88rem;
    background: #fff;
}

.btn-discount-apply {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: var(--ck-accent);
    color: #fff;
    border: 0;
    border-radius: .65rem;
    padding: .65rem 1.15rem;
    font-weight: 600;
    font-size: .88rem;
    cursor: pointer;
    transition: opacity .2s;
    white-space: nowrap;
}

.btn-discount-apply:disabled {
    opacity: .6;
    cursor: not-allowed;
}

.discount-applied-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: color-mix(in srgb, var(--xshop-success) 8%, white);
    border: 1px solid color-mix(in srgb, var(--xshop-success) 30%, white);
    border-radius: .75rem;
    padding: .75rem 1rem;
}

.discount-applied-info {
    display: flex;
    align-items: center;
    gap: .65rem;
}

.discount-applied-info i {
    font-size: 1.25rem;
}

.discount-human-text {
    font-size: .88rem;
    color: var(--ck-ink);
}

.discount-code-tag {
    display: block;
    font-size: .75rem;
    color: var(--ck-muted);
}

.btn-remove-discount {
    display: inline-flex;
    align-items: center;
    gap: .25rem;
    background: transparent;
    border: 1px solid color-mix(in srgb, var(--xshop-danger) 40%, white);
    color: var(--xshop-danger);
    border-radius: .5rem;
    padding: .35rem .65rem;
    font-size: .78rem;
    font-weight: 600;
    cursor: pointer;
    transition: all .2s ease;
}

.btn-remove-discount:hover {
    background: var(--xshop-danger);
    color: #fff;
}

.order-notes-box {
    margin-bottom: 1rem;
}

.order-notes-label {
    display: flex;
    align-items: center;
    gap: .35rem;
    font-size: .88rem;
    font-weight: 600;
    color: var(--ck-ink);
    margin-bottom: .45rem;
}

.order-notes-label i {
    color: var(--ck-muted);
}

.badge-optional {
    margin-inline-start: auto;
    background: var(--ck-soft);
    border: 1px solid var(--ck-line);
    color: var(--ck-muted);
    font-size: .72rem;
    font-weight: normal;
    padding: .1rem .45rem;
    border-radius: .35rem;
}

.pay-option {
    display: flex;
    gap: .75rem;
    align-items: flex-start;
    border: 1px solid color-mix(in srgb, var(--ck-accent) 45%, white);
    border-radius: .75rem;
    padding: .85rem 1rem;
    margin: .5rem 0 .75rem;
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

.bank-box-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: .75rem;
    padding-bottom: .5rem;
    border-bottom: 1px solid var(--ck-line);
}

.bank-box-title {
    display: flex;
    align-items: center;
    gap: .4rem;
}

.bank-box-title i {
    color: var(--ck-accent);
    font-size: 1.1rem;
}

.bank-box-title h5 {
    margin: 0;
    font-size: .95rem;
}

.bank-tag {
    background: color-mix(in srgb, var(--ck-accent) 15%, white);
    border: 1px solid color-mix(in srgb, var(--ck-accent) 40%, white);
    color: var(--ck-deep);
    font-size: .75rem;
    font-weight: 700;
    padding: .2rem .55rem;
    border-radius: .4rem;
}

.bank-row {
    display: grid;
    grid-template-columns: 7.5rem 1fr auto;
    gap: .5rem;
    align-items: center;
    padding: .45rem 0;
    border-bottom: 1px dashed var(--ck-line);
    font-size: .9rem;
}

.bank-row-highlight {
    background: #fff;
    padding: .5rem .65rem;
    border-radius: .5rem;
    border-bottom: 0;
    margin: .35rem 0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
}

.bank-row span { color: var(--ck-muted); }
.bank-row strong { overflow-wrap: anywhere; }

.copy-btn {
    display: inline-flex;
    align-items: center;
    gap: .25rem;
    border: 1px solid var(--ck-line);
    background: #fff;
    border-radius: .5rem;
    padding: .25rem .55rem;
    font-size: .75rem;
    font-weight: 600;
    cursor: pointer;
}

.discount-line {
    color: var(--xshop-danger);
    font-weight: 600;
}

.muted { color: var(--ck-muted); font-size: .88rem; margin: .75rem 0 0; display: flex; align-items: center; gap: .35rem; }
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
        margin-bottom: .85rem;
        border-radius: .5rem;
        background: linear-gradient(180deg, var(--xshop-gold-100), var(--ck-paper));
        box-shadow: 0 1px 4px rgba(61, 46, 20, 0.08);
        overflow: hidden;
    }
    .assay-copy {
        padding: .6rem .75rem .5rem;
    }
    .assay-kicker {
        font-size: .68rem;
        margin-bottom: .2rem;
    }
    .assay-copy p {
        font-size: .76rem;
        line-height: 1.45;
    }
    .assay-path {
        margin-top: .35rem;
        gap: .25rem .6rem;
        font-size: .7rem;
    }
    .quote-timer {
        border-inline-start: 0;
        border-top: 1px dashed var(--xshop-gold-300);
        min-width: 0;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        padding: .4rem .75rem;
    }
    .quote-timer span {
        font-size: .68rem;
    }
    .quote-timer em {
        font-size: 1.15rem;
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
