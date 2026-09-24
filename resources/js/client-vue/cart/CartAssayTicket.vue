<template>
    <div class="assay-ticket" role="status" :class="{ urgent: remaining <= 120 }">
        <div class="assay-copy">
            <span class="assay-kicker">{{ t('live-price-title', 'قیمت طلا لحظه‌ای است') }}</span>
            <p>{{ t('live-price-hint', 'قیمت هر قطعه با نرخ روز طلا محاسبه می‌شود و برای صدور فاکتور ۳۰ دقیقه اعتبار دارد.') }}</p>
            <ol class="assay-path">
                <li class="now">{{ t('quote-step', 'صدور فاکتور') }} · {{ minutes }} {{ t('minutes-short', 'دقیقه') }}</li>
                <li>{{ t('pay-step', 'کارت‌به‌کارت و رسید') }} · {{ hours }} {{ t('hours-short', 'ساعت') }}</li>
            </ol>
        </div>
        <div class="quote-timer" :class="{ urgent: remaining <= 120 }">
            <span>{{ remaining <= 0 ? t('quote-expired', 'در حال بروزرسانی...') : t('quote-remaining', 'زمان باقی‌مانده') }}</span>
            <em>{{ countdown }}</em>
        </div>
    </div>
</template>

<script>
export default {
    name: 'CartAssayTicket',
    props: {
        remaining: { type: Number, default: 0 },
        minutes: { type: Number, default: 30 },
        hours: { type: Number, default: 3 },
        countdown: { type: String, default: '00:00' },
        t: { type: Function, required: true },
    },
}
</script>
