<template>
    <div id="quantities-add-to-card" class="d-flex flex-column gap-2 mb-3">
        <template v-for="(q,i) in qz">
            <div
                :key="i"
                :class="['quantity-piece-card d-flex align-items-center justify-content-between p-3 rounded-3 transition-all', selected === i ? 'selected-piece' : '']"
                v-if="q.count > 0"
                @click="select(i)"
            >
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div class="radio-indicator rounded-circle border d-flex align-items-center justify-content-center">
                        <span class="radio-inner rounded-circle" v-if="selected === i"></span>
                    </div>

                    <div class="piece-specs d-flex align-items-center gap-2 fs-13">
                        <span class="text-muted">{{ weightLabel }}:</span>
                        <strong class="text-dark">{{ formatWeight(q) }}</strong>

                        <span v-if="q.code" class="ms-2 d-inline-flex align-items-center gap-1">
                            <span class="text-muted">{{ codeLabel }}:</span>
                            <strong class="text-dark">{{ q.code }}</strong>
                        </span>
                    </div>

                    <template v-for="(v,k) in data2object(q.data)">
                        <div v-if="shouldShowProp(k)" :key="k" class="piece-custom-prop fs-13">
                            <div v-if="props[k] && props[k].type == 'color'" class="d-flex align-items-center gap-1">
                                <span class="q-color rounded-circle border shadow-xs" :style="`background-color:${v}`"></span>
                            </div>
                            <div v-else-if="props[k] && (props[k].type == 'select' || props[k].type == 'singlemulti' || props[k].type == 'multi')" class="d-flex align-items-center gap-1">
                                <span class="text-muted">{{ props[k].label }}:</span>
                                <strong class="text-dark">{{ props[k].data[v] }}</strong>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="piece-price text-end">
                    <template v-if="discount != null">
                        <div class="fs-15 fw-bold text-dark">{{ calcDiscount(q.price) }}</div>
                        <del class="text-muted fs-12">{{ commafy(q.price.toString()) }} {{ currency }}</del>
                    </template>
                    <template v-else>
                        <span class="fs-15 fw-bold text-dark">{{ commafy(q.price.toString()) }} {{ currency }}</span>
                    </template>
                </div>
            </div>
        </template>

        <button type="button" class="btn btn-primary btn-lg rounded-pill w-100 fw-bold py-2.5 mt-2 d-flex align-items-center justify-content-center gap-2 shadow-sm" @click="add2card">
            <i class="ri-shopping-bag-3-line fs-20"></i>
            <span>{{ translate['add-to-card'] || 'Add to Cart' }}</span>
        </button>
    </div>
</template>

<script>
function commafy(num) {
    if (typeof num !== 'string') {
        return '';
    }
    let str = uncommafy(num.toString()).split('.');
    if (str[0].length >= 4) {
        str[0] = str[0].replace(/(\d)(?=(\d{3})+$)/g, '$1,');
    }
    if (str[1] && str[1].length >= 4) {
        str[1] = str[1].replace(/(\d{3})/g, '$1 ');
    }
    return str.join('.');
}

function uncommafy(txt) {
    return txt.split(',').join('');
}

export default {
    name: "quantities-add-to-card",
    data: () => ({
        selected: null,
    }),
    props: {
        qz: { default: () => [] },
        props: { default: () => ({}) },
        currency: { default: '$' },
        cardLink: { default: '' },
        discount: { default: null },
        translate: { default: () => ({}) }
    },
    computed: {
        weightLabel() {
            return this.translate['weight'] || 'Weight';
        },
        codeLabel() {
            return this.translate['code'] || 'Code';
        },
    },
    methods: {
        formatWeight(q) {
            const weight = q.weight != null ? q.weight : (this.data2object(q.data)?.weight ?? null);
            if (weight == null || weight === '') {
                return '—';
            }
            const unit = this.translate['gram'] || 'گرم';
            return Number(weight).toLocaleString(undefined, {maximumFractionDigits: 3}) + ' ' + unit;
        },
        shouldShowProp(key) {
            return key !== 'weight' && key !== 'code' && this.props && this.props[key];
        },
        select(i) {
            this.selected = i;
            const priceEl = document.querySelector('#price');
            if (priceEl && this.qz[i]) {
                priceEl.innerText = commafy(this.qz[i].price.toString()) + ' ' + this.currency;
            }
            let index = this.qz[i]?.image;
            if (index != null && document.querySelector(`#hidden-images a:nth-child(${index + 1})`)) {
                document.querySelector('#preview a')?.setAttribute('href', document.querySelector(`#hidden-images a:nth-child(${index + 1})`).getAttribute('href'));
                document.querySelector('#preview img')?.setAttribute('src', document.querySelector(`#hidden-images a:nth-child(${index + 1}) img`).getAttribute('src'));
            }
        },
        async add2card() {
            if (this.selected == null) {
                window.$toast.warning(this.translate['select-piece-first'] || 'لطفاً یک مورد را انتخاب کنید');
                return;
            }

            try {
                let resp = await axios.get(this.cardLink + '?quantity=' + this.qz[this.selected].id);
                if (resp.data.OK || resp.data.success) {
                    window.$toast.success(resp.data.message);
                    document.querySelectorAll('.card-count')?.forEach(function (el2) {
                        el2.innerText = resp.data.data.count;
                    });
                } else {
                    window.$toast.error(resp.data.message || "Error!");
                }
            } catch (e) {
                window.$toast.error("Failed to add to cart");
            }
        },
        calcDiscount(price) {
            if (this.discount == null) {
                return '-';
            }
            if (this.discount.type == 'PERCENT') {
                return commafy(parseInt(((100 - this.discount.amount) * price) / 100).toString()) + ' ' + this.currency;
            } else {
                return commafy((price - this.discount.amount).toString()) + ' ' + this.currency;
            }
        },
        data2object(data) {
            if (data && typeof data === 'object') {
                return data;
            }
            try {
                return JSON.parse(data);
            } catch {
                return {};
            }
        },
        commafy: commafy,
    },
    mounted() {
        const first = (Array.isArray(this.qz) ? this.qz : []).findIndex((q) => Number(q.count) > 0);
        if (first >= 0) {
            this.select(first);
        }
    },
}
</script>

<style scoped>
.quantity-piece-card {
    border: 1px solid rgba(0, 0, 0, 0.08);
    background-color: #ffffff;
    cursor: pointer;
    transition: all 0.2s ease;
}

.quantity-piece-card:hover {
    border-color: rgba(219, 154, 0, 0.5);
    background-color: rgba(219, 154, 0, 0.02);
}

.selected-piece {
    border: 2px solid var(--xshop-primary) !important;
    background-color: rgba(219, 154, 0, 0.05) !important;
}

.radio-indicator {
    width: 18px;
    height: 18px;
    border-color: #cbd5e1 !important;
    background: #ffffff;
}

.selected-piece .radio-indicator {
    border-color: var(--xshop-primary) !important;
}

.radio-inner {
    width: 8px;
    height: 8px;
    background-color: var(--xshop-primary);
}

.q-color {
    width: 18px;
    height: 18px;
}
</style>
