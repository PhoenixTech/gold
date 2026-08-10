<template>
    <div id="qPreview">
        <div v-if="q.weight != null" class="meta">
            <span class="ms-2">{{ weightLabel }}</span>
            <div class="ms-2">{{ formatWeight(q.weight) }}</div>
        </div>
        <div v-if="q.code" class="meta">
            <span class="ms-2">{{ codeLabel }}</span>
            <div class="ms-2">{{ q.code }}</div>
        </div>
        <div v-for="meta in (q.meta || [])" class="meta">
            <span v-if="meta.type != 'color'" class="ms-2">
                {{ meta.label }}
            </span>
            <div v-html="meta.human_value" class="ms-2"></div>
        </div>
    </div>
</template>

<script>
export default {
    name: "qPreview",
    components: {},
    data: () => {
        return {}
    },
    props: {
        q: {
            required: true,
        },
        weightLabel: {
            type: String,
            default: 'Weight',
        },
        codeLabel: {
            type: String,
            default: 'Code',
        },
    },
    mounted() {
    },
    computed: {},
    methods: {
        formatWeight(weight) {
            return Number(weight).toLocaleString(undefined, {maximumFractionDigits: 3}) + ' g';
        },
    }
}
</script>

<style scoped>
.meta {
    display: inline-flex;

    div {
        display: inline-block;
        padding: 3px 0;
    }

    span {
        background: var(--xshop-secondary);
        color: var(--xshop-diff2);
        border-radius: var(--xshop-border-radius);
        display: inline-block;
        padding: 2px 3px;
        height: 1.6em;
    }
}
</style>
