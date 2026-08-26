<template>
    <div class="stock-items-input">
        <div class="stock-toolbar">
            <div class="stock-toolbar-copy">
                <h5 class="mb-1">{{ title }}</h5>
                <p class="text-muted small mb-0">{{ subtitle }}</p>
                <div class="stock-counts">
                    <span class="badge text-bg-light border">{{ items.length.toLocaleString('fa-IR') }}</span>
                    <span class="badge text-bg-success">{{ availableCount.toLocaleString('fa-IR') }} {{ availableLabel }}</span>
                    <span v-if="soldCount > 0" class="badge text-bg-secondary">{{ soldCount.toLocaleString('fa-IR') }} {{ soldLabel }}</span>
                </div>
            </div>
            <div class="stock-toolbar-actions">
                <div v-if="items.length > 4" class="stock-search">
                    <i class="ri-search-line"></i>
                    <input
                        type="search"
                        class="form-control form-control-sm"
                        v-model="query"
                        :placeholder="searchPlaceholder"
                    >
                </div>
                <button type="button" class="btn btn-primary btn-sm" @click="addItem">
                    <i class="ri-add-line"></i>
                    {{ addLabel }}
                </button>
            </div>
        </div>

        <div v-if="items.length === 0" class="alert alert-light border mb-0">
            {{ emptyLabel }}
        </div>

        <div v-else class="stock-list" ref="list">
            <div class="stock-table-head">
                <span>{{ skuLabel }}</span>
                <span>{{ weightLabel }}</span>
                <span>{{ priceLabel }}</span>
                <span>{{ statusLabel }}</span>
                <span></span>
                <span></span>
            </div>

            <div v-if="filteredItems.length === 0" class="stock-empty-filter">
                {{ searchPlaceholder }}
            </div>

            <div
                v-for="item in filteredItems"
                :key="item._key"
                class="stock-row"
                :class="{ 'is-sold': item.count <= 0, 'is-new': item.isNew }"
            >
                <div class="stock-row-main">
                    <input
                        type="text"
                        class="form-control form-control-sm bg-light font-monospace"
                        :value="item.code"
                        readonly
                        dir="ltr"
                    >
                    <input
                        type="number"
                        step="0.001"
                        min="0.001"
                        class="form-control form-control-sm"
                        v-model.number="item.weight"
                        :disabled="item.count <= 0"
                        @input="recalculateItem(item)"
                    >
                    <input
                        type="text"
                        class="form-control form-control-sm fw-semibold"
                        :value="formatPrice(livePrice(item))"
                        readonly
                        disabled
                    >
                    <div>
                        <span v-if="item.count > 0" class="badge text-bg-success">{{ availableLabel }}</span>
                        <span v-else class="badge text-bg-secondary">{{ soldLabel }}</span>
                    </div>
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-secondary"
                        :class="{ active: item.breakdownOpen }"
                        :title="breakdownTitle"
                        @click="item.breakdownOpen = !item.breakdownOpen"
                    >
                        <i class="ri-calculator-line"></i>
                    </button>
                    <button
                        v-if="item.count > 0"
                        type="button"
                        class="btn btn-outline-danger btn-sm"
                        @click="removeItem(item)"
                        :title="removeLabel"
                    >
                        <i class="ri-delete-bin-line"></i>
                    </button>
                    <span v-else></span>
                </div>

                <div v-show="item.breakdownOpen" class="stock-row-breakdown">
                    <div v-if="breakdown(item)" class="price-breakdown">
                        <ol class="price-breakdown-list mb-0">
                            <li v-for="(step, stepIndex) in breakdown(item).steps" :key="stepIndex">
                                <span class="step-label">{{ step.label }}</span>
                                <span class="step-math" dir="ltr">{{ step.math }}</span>
                                <span class="step-value" dir="ltr">{{ step.value }}</span>
                            </li>
                        </ol>
                        <div class="price-breakdown-total mt-2">
                            <span>{{ finalLabel }}:</span>
                            <strong dir="ltr">{{ formatPrice(breakdown(item).final) }}</strong>
                        </div>
                    </div>
                    <div v-else class="alert alert-light border small mb-0">
                        {{ needWeightHint }}
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" :name="xname" :value="payload">
    </div>
</template>


<script>
let keySeed = 0;

function uncommafy(txt) {
    return String(txt ?? '').split(',').join('').trim();
}

function toNumber(value, fallback = 0) {
    const n = Number(uncommafy(value));
    return Number.isFinite(n) ? n : fallback;
}

export default {
    name: 'stock-items-input',
    props: {
        xname: {
            type: String,
            default: 'stock_items',
        },
        xvalue: {
            type: [Array, String],
            default: () => [],
        },
        productSku: {
            type: String,
            default: '',
        },
        goldPrice: {
            type: [Number, String],
            default: 0,
        },
        silverPrice: {
            type: [Number, String],
            default: 0,
        },
        minimumPercent: {
            type: [Number, String],
            default: 100,
        },
        title: {
            type: String,
            default: 'قطعات موجودی',
        },
        subtitle: {
            type: String,
            default: 'هر ردیف یک قطعه یکتا با وزن مخصوص خودش است.',
        },
        addLabel: {
            type: String,
            default: 'افزودن قطعه',
        },
        emptyLabel: {
            type: String,
            default: 'هنوز قطعه‌ای ثبت نشده است.',
        },
        weightLabel: {
            type: String,
            default: 'وزن (گرم)',
        },
        skuLabel: {
            type: String,
            default: 'SKU',
        },
        codeLabel: {
            type: String,
            default: 'کد',
        },
        codePlaceholder: {
            type: String,
            default: 'اختیاری',
        },
        priceLabel: {
            type: String,
            default: 'مبلغ',
        },
        statusLabel: {
            type: String,
            default: 'وضعیت',
        },
        availableLabel: {
            type: String,
            default: 'موجود',
        },
        soldLabel: {
            type: String,
            default: 'فروخته‌شده',
        },
        removeLabel: {
            type: String,
            default: 'حذف',
        },
        liveHint: {
            type: String,
            default: 'بر اساس وزن فعلی و تنظیمات قیمت‌گذاری محاسبه می‌شود.',
        },
        breakdownTitle: {
            type: String,
            default: 'نحوه محاسبه قیمت',
        },
        finalLabel: {
            type: String,
            default: 'قیمت نهایی',
        },
        needWeightHint: {
            type: String,
            default: 'برای نمایش جزئیات محاسبه، وزن قطعه را وارد کنید.',
        },
        metalGoldLabel: {
            type: String,
            default: 'طلا',
        },
        metalSilverLabel: {
            type: String,
            default: 'نقره',
        },
        searchPlaceholder: {
            type: String,
            default: 'جستجوی SKU یا وزن',
        },
    },
    data() {
        return {
            items: this.normalize(this.xvalue),
            formula: {
                metalType: 'gold',
                feePercent: 15,
                profitPercent: 7,
                taxPercent: 9,
                addon: 0,
            },
            formulaTick: 0,
            formEl: null,
            query: '',
        };
    },
    watch: {
        availableCount: {
            immediate: true,
            handler(value) {
                const el = document.getElementById('stock_quantity');
                if (el) {
                    el.value = value;
                }
            },
        },
    },
    computed: {
        payload() {
            return JSON.stringify(this.items.map((item) => {
                const price = this.livePrice(item);
                return {
                    id: item.id,
                    weight: item.weight,
                    code: item.code,
                    count: item.count,
                    price,
                    image: item.image,
                };
            }));
        },
        metalUnitPrice() {
            this.formulaTick;
            const marketPrice = this.marketMetalUnitPrice;
            const minimumPercent = this.minimumPercentValue;

            return Math.round((marketPrice * minimumPercent) / 100);
        },
        marketMetalUnitPrice() {
            this.formulaTick;
            return this.formula.metalType === 'silver'
                ? toNumber(this.silverPrice)
                : toNumber(this.goldPrice);
        },
        minimumPercentValue() {
            this.formulaTick;
            const minimumPercent = toNumber(this.minimumPercent, 100);

            return minimumPercent > 0 ? minimumPercent : 100;
        },
        metalName() {
            this.formulaTick;
            return this.formula.metalType === 'silver' ? this.metalSilverLabel : this.metalGoldLabel;
        },
        currentProductSku() {
            this.formulaTick;
            const fromForm = (this.readField('#sku') || '').trim();
            return fromForm || String(this.productSku || '').trim();
        },
        availableCount() {
            return this.items.filter((item) => item.count > 0).length;
        },
        soldCount() {
            return this.items.length - this.availableCount;
        },
        filteredItems() {
            const q = String(this.query || '').trim().toLowerCase();
            if (!q) {
                return this.items;
            }

            return this.items.filter((item) => {
                const code = String(item.code || '').toLowerCase();
                const weight = String(item.weight ?? '');
                return code.includes(q) || weight.includes(q);
            });
        },
    },
    mounted() {
        this.formEl = this.$el.closest('form');
        this.syncFormulaFromForm();
        this.ensurePieceSkus();
        this.recalculateAll();

        if (this.formEl) {
            this.formEl.addEventListener('input', this.onFormChanged);
            this.formEl.addEventListener('change', this.onFormChanged);
            this.formEl.addEventListener('keyup', this.onFormChanged);
        }

        document.querySelectorAll('.product-form-tabs li, .step-next, .step-prev').forEach((el) => {
            el.addEventListener('click', this.onFormChanged);
        });
    },
    beforeUnmount() {
        if (this.formEl) {
            this.formEl.removeEventListener('input', this.onFormChanged);
            this.formEl.removeEventListener('change', this.onFormChanged);
            this.formEl.removeEventListener('keyup', this.onFormChanged);
        }
        document.querySelectorAll('.product-form-tabs li, .step-next, .step-prev').forEach((el) => {
            el.removeEventListener('click', this.onFormChanged);
        });
    },
    methods: {
        onFormChanged() {
            this.syncFormulaFromForm();
            this.ensurePieceSkus();
            this.recalculateAll();
        },
        readField(selector) {
            const el = this.formEl?.querySelector(selector);
            return el ? el.value : null;
        },
        syncFormulaFromForm() {
            if (!this.formEl) {
                return;
            }

            const metalType = this.readField('#metal_type') || this.readField('[name="metal_type"]') || 'gold';
            const feePercent = toNumber(
                this.readField('input[name="labor_charge_1"]') ?? this.readField('#labor_charge_1'),
                15
            );
            const profitPercent = toNumber(this.readField('#profit') ?? this.readField('input[name="profit"]'), 7);
            const taxPercent = toNumber(this.readField('#tax') ?? this.readField('input[name="tax"]'), 9);
            const addon = toNumber(
                this.readField('input[name="addon"]') ?? this.readField('#addon'),
                0
            );

            this.formula = {
                metalType,
                feePercent,
                profitPercent,
                taxPercent,
                addon,
            };
            this.formulaTick += 1;
        },
        explain(item) {
            this.formulaTick;

            const weight = Number(item.weight || 0);
            const marketMetalPrice = this.marketMetalUnitPrice;
            const minimumPercent = this.minimumPercentValue;
            const metalPrice = this.metalUnitPrice;
            const feePercent = Number(this.formula.feePercent || 0);
            const profitPercent = Number(this.formula.profitPercent || 0);
            const taxPercent = Number(this.formula.taxPercent || 0);
            const addon = Number(this.formula.addon || 0);

            if (!weight || weight <= 0 || !marketMetalPrice || !metalPrice) {
                return null;
            }

            const profitRate = profitPercent / 100;
            const taxRate = taxPercent / 100;
            const p = metalPrice;
            const n1 = p + (p * (feePercent / 100));
            const n2 = (n1 + (n1 * profitRate) - p);
            const n3 = (n2 * taxRate) + n2;
            const complete = (n3 + p) * weight;
            const rounded = Math.floor(complete / 1000) * 1000;
            const final = rounded + addon;

            return {
                final,
                steps: [
                    {
                        label: `نرخ روز ${this.metalName}`,
                        math: `${this.metalName} / گرم`,
                        value: this.formatPrice(marketMetalPrice),
                    },
                    {
                        label: `حداقل درصد سود ${this.formatPercent(minimumPercent)}`,
                        math: `${this.formatPlain(marketMetalPrice)} × ${this.formatPercent(minimumPercent)}`,
                        value: this.formatPrice(p),
                    },
                    {
                        label: `اجرت ${this.formatPercent(feePercent)}`,
                        math: `${this.formatPlain(p)} + (${this.formatPlain(p)} × ${this.formatPercent(feePercent)})`,
                        value: this.formatPrice(Math.round(n1)),
                    },
                    {
                        label: `سود ${this.formatPercent(profitPercent)} روی اجرت`,
                        math: `(${this.formatPlain(Math.round(n1))} × ${this.formatPercent(100 + profitPercent)}) - ${this.formatPlain(p)}`,
                        value: this.formatPrice(Math.round(n2)),
                    },
                    {
                        label: `مالیات ${this.formatPercent(taxPercent)} روی اجرت+سود`,
                        math: `${this.formatPlain(Math.round(n2))} × ${this.formatPercent(100 + taxPercent)}`,
                        value: this.formatPrice(Math.round(n3)),
                    },
                    {
                        label: `ضرب در وزن ${this.formatWeight(weight)} گرم`,
                        math: `(${this.formatPlain(Math.round(n3))} + ${this.formatPlain(p)}) × ${this.formatWeight(weight)}`,
                        value: this.formatPrice(Math.round(complete)),
                    },
                    {
                        label: 'رند به پایین تا هزار تومان',
                        math: `floor(${this.formatPlain(Math.round(complete))} / 1000) × 1000`,
                        value: this.formatPrice(rounded),
                    },
                    {
                        label: 'اضافه کردن مبلغ جانبی',
                        math: `${this.formatPlain(rounded)} + ${this.formatPlain(addon)}`,
                        value: this.formatPrice(final),
                    },
                ],
            };
        },
        breakdown(item) {
            return this.explain(item);
        },
        livePrice(item) {
            return this.explain(item)?.final ?? 0;
        },
        recalculateItem(item) {
            item.price = this.livePrice(item);
        },
        recalculateAll() {
            this.items.forEach((item) => this.recalculateItem(item));
        },
        normalize(value) {
            let rows = value;
            if (typeof value === 'string') {
                try {
                    rows = JSON.parse(value);
                } catch (e) {
                    rows = [];
                }
            }
            if (!Array.isArray(rows)) {
                rows = [];
            }

            return rows.map((row) => ({
                _key: ++keySeed,
                id: row.id ?? null,
                weight: row.weight != null ? Number(row.weight) : null,
                code: row.code ?? '',
                count: row.count == null ? 1 : Number(row.count),
                price: row.price ?? 0,
                image: row.image ?? null,
                breakdownOpen: false,
                isNew: false,
            })).reverse();
        },
        parsePieceNumber(code) {
            const sku = this.currentProductSku;
            const value = String(code || '').trim();
            if (!sku || !value.startsWith(`${sku}-`)) {
                return 0;
            }
            const n = parseInt(value.slice(sku.length + 1), 10);
            return Number.isFinite(n) ? n : 0;
        },
        pieceSku(number) {
            const sku = this.currentProductSku;
            if (!sku) {
                return '';
            }
            return `${sku}-${String(number).padStart(4, '0')}`;
        },
        nextPieceNumber() {
            let max = 0;
            this.items.forEach((item) => {
                max = Math.max(max, this.parsePieceNumber(item.code));
            });
            return Math.max(max, this.items.length) + 1;
        },
        ensurePieceSkus() {
            const sku = this.currentProductSku;
            if (!sku) {
                return;
            }
            this.items.forEach((item, index) => {
                const code = String(item.code || '').trim();
                if (code !== '' && this.parsePieceNumber(code) > 0) {
                    return;
                }
                if (code !== '' && !code.includes('[native code]')) {
                    return;
                }
                let max = 0;
                this.items.forEach((other) => {
                    max = Math.max(max, this.parsePieceNumber(other.code));
                });
                item.code = this.pieceSku(Math.max(max, index) + 1);
            });
        },
        addItem() {
            this.query = '';
            const item = {
                _key: ++keySeed,
                id: null,
                weight: null,
                code: this.pieceSku(this.nextPieceNumber()),
                count: 1,
                price: 0,
                image: null,
                breakdownOpen: false,
                isNew: true,
            };
            this.items.unshift(item);
            this.recalculateItem(item);
            this.$nextTick(() => {
                this.$refs.list?.scrollTo({ top: 0, behavior: 'smooth' });
                this.$el.querySelector('.stock-row input[type="number"]')?.focus();
            });
            window.setTimeout(() => {
                item.isNew = false;
            }, 1600);
        },
        removeItem(item) {
            const index = this.items.findIndex((row) => row._key === item._key);
            if (index >= 0) {
                this.items.splice(index, 1);
            }
        },
        formatPrice(price) {
            if (price === null || price === undefined || price === '') {
                return '—';
            }
            return Number(price).toLocaleString('fa-IR');
        },
        formatPlain(price) {
            return Number(price || 0).toLocaleString('en-US');
        },
        formatPercent(value) {
            return `${Number(value || 0).toLocaleString('fa-IR')}٪`;
        },
        formatWeight(value) {
            return Number(value || 0).toLocaleString('fa-IR', { maximumFractionDigits: 3 });
        },
    },
};
</script>

<style scoped>
.stock-toolbar {
    position: sticky;
    top: 0;
    z-index: 4;
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    margin: -.25rem -.25rem 1rem;
    padding: .75rem .25rem .85rem;
    background: #fff;
    border-bottom: 1px solid rgba(0, 0, 0, .06);
}

.stock-counts {
    display: flex;
    flex-wrap: wrap;
    gap: .35rem;
    margin-top: .5rem;
}

.stock-toolbar-actions {
    display: flex;
    align-items: center;
    gap: .5rem;
    flex-wrap: wrap;
}

.stock-search {
    position: relative;
    min-width: 180px;
}

.stock-search i {
    position: absolute;
    inset-inline-start: .6rem;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    pointer-events: none;
}

.stock-search input {
    padding-inline-start: 1.85rem;
}

.stock-list {
    max-height: min(62vh, 720px);
    overflow: auto;
    border: 1px solid rgba(0, 0, 0, .08);
    border-radius: .75rem;
    background: #fff;
}

.stock-table-head,
.stock-row-main {
    display: grid;
    grid-template-columns: minmax(140px, 1.15fr) 118px minmax(120px, 1fr) 88px 40px 40px;
    gap: .5rem;
    align-items: center;
}

.stock-table-head {
    position: sticky;
    top: 0;
    z-index: 2;
    padding: .55rem .75rem;
    background: #f8fafc;
    border-bottom: 1px solid rgba(0, 0, 0, .06);
    color: #64748b;
    font-size: .75rem;
    font-weight: 700;
}

.stock-row {
    border-bottom: 1px solid rgba(0, 0, 0, .05);
    padding: .55rem .75rem;
}

.stock-row:last-child {
    border-bottom: 0;
}

.stock-row.is-sold {
    opacity: .72;
    background: #f8fafc;
}

.stock-row.is-new {
    background: #eff6ff;
}

.stock-row-breakdown {
    padding-top: .65rem;
}

.stock-empty-filter {
    padding: 1.25rem .75rem;
    color: #64748b;
    font-size: .85rem;
}

.price-breakdown {
    background: #f8fafc;
    border: 1px solid rgba(0, 0, 0, .06);
    border-radius: .75rem;
    padding: .85rem 1rem;
}

.price-breakdown-list {
    padding-inline-start: 1.1rem;
    margin: 0;
}

.price-breakdown-list li {
    display: grid;
    grid-template-columns: minmax(140px, 1.1fr) minmax(160px, 1.4fr) auto;
    gap: .5rem 1rem;
    padding: .35rem 0;
    border-bottom: 1px dashed rgba(0, 0, 0, .06);
    font-size: .8rem;
}

.price-breakdown-list li:last-child {
    border-bottom: 0;
}

.step-label {
    color: #334155;
    font-weight: 600;
}

.step-math {
    color: #64748b;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    word-break: break-word;
}

.step-value {
    color: #0f172a;
    font-weight: 700;
    text-align: end;
    white-space: nowrap;
}

.price-breakdown-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    padding-top: .5rem;
    border-top: 1px solid rgba(0, 0, 0, .08);
    font-size: .9rem;
}

@media (max-width: 768px) {
    .stock-table-head {
        display: none;
    }

    .stock-row-main,
    .price-breakdown-list li {
        grid-template-columns: 1fr;
        gap: .35rem;
    }

    .step-value {
        text-align: start;
    }
}
</style>

