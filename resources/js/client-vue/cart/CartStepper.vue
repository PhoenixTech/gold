<template>
    <nav class="checkout-progress" aria-label="checkout steps">
        <button
            v-for="(step, i) in steps"
            :key="step.key"
            type="button"
            class="progress-item"
            :class="{ active: index === i, done: index > i }"
            :aria-current="index === i ? 'step' : undefined"
            @click="$emit('go-to', i)"
        >
            <span class="num">
                <i v-if="index > i" class="ri-check-line"></i>
                <i v-else :class="step.icon"></i>
            </span>
            <span class="label">
                <span class="d-none d-md-inline">{{ step.label }}</span>
                <span class="d-inline d-md-none">{{ step.shortLabel || step.label }}</span>
            </span>
        </button>
    </nav>
</template>

<script>
export default {
    name: 'CartStepper',
    props: {
        steps: { type: Array, required: true },
        index: { type: Number, required: true },
    },
    emits: ['go-to'],
}
</script>
