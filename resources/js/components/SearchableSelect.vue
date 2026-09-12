<template>
    <div class="searchable-select-component position-relative">
        <!-- Native Bootstrap 5 Modal for Search -->
        <div
            v-if="modalShow"
            class="modal fade show d-block"
            tabindex="-1"
            style="background: rgba(0, 0, 0, 0.45); z-index: 1060;"
            @click.self="hideModal"
        >
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 440px;">
                <div class="modal-content shadow border-0">
                    <div class="modal-header py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                        <span class="fs-14 fw-bold text-dark">{{ xtitle }}</span>
                        <button type="button" class="btn-close" @click="hideModal" aria-label="Close"></button>
                    </div>
                    <div class="p-2 border-bottom bg-light">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0 text-muted">
                                <i class="ri-search-line"></i>
                            </span>
                            <input
                                type="search"
                                class="form-control border-start-0"
                                v-model="q"
                                ref="searchInput"
                                :placeholder="xtitle"
                            >
                            <button v-if="q" class="btn btn-outline-secondary border-start-0" type="button" @click="q = ''">
                                <i class="ri-close-line"></i>
                            </button>
                        </div>
                    </div>
                    <div class="modal-body p-0" style="max-height: 320px; overflow-y: auto;">
                        <div class="list-group list-group-flush">
                            <button
                                type="button"
                                v-for="item in filteredItems"
                                :key="item[valueField]"
                                @click="selecting(item[valueField])"
                                class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2 px-3 fs-13 text-dark border-0 border-bottom"
                                :class="{'active': String(val) === String(item[valueField])}"
                            >
                                <span>{{ getItemTitle(item) }}</span>
                                <i v-if="String(val) === String(item[valueField])" class="ri-check-line"></i>
                            </button>
                            <div v-if="filteredItems.length === 0" class="text-center py-4 text-muted fs-13">
                                {{ xlang === 'en' ? 'No items found' : 'موردی یافت نشد' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Input Group with Search Button and Select -->
        <div class="input-group">
            <button class="btn btn-outline-secondary" type="button" @click="showModal" :title="xtitle">
                <i class="ri-search-2-line"></i>
            </button>
            <select :id="xid || undefined" :class="getClass" v-model="val" @change="select">
                <option value=""> {{ xtitle }}</option>
                <option
                    v-for="item in items"
                    :key="item[valueField]"
                    :value="item[valueField]"
                    :selected="String(item[valueField]) === String(val)"
                >
                    {{ getItemTitle(item) }}
                </option>
            </select>
        </div>
        <input type="hidden" :name="xname" :value="val">
    </div>
</template>

<script>
export default {
    name: "searchable-select",
    emits: ['update:modelValue'],
    props: {
        vuexDispatch: {
            default: null,
        },
        xlang: {
            default: null,
        },
        modelValue: {
            default: NaN,
        },
        items: {
            required: true,
            default: () => [],
            type: Array,
        },
        valueField: {
            default: 'id',
            type: String,
        },
        titleField: {
            default: 'title',
            type: String,
        },
        xname: {
            default: "",
            type: String,
        },
        xtitle: {
            default: "Please select",
            type: String,
        },
        xvalue: {
            default: "",
        },
        xid: {
            default: "",
            type: String,
        },
        customClass: {
            default: "",
            type: String,
        },
        err: {
            default: false,
            type: Boolean,
        },
        onSelect: {
            default: () => {},
            type: Function,
        },
        closeOnSelect: {
            default: false,
            type: Boolean,
        },
    },
    data() {
        return {
            modalShow: false,
            q: '',
            val: '',
        };
    },
    mounted() {
        if (!isNaN(this.modelValue)) {
            this.val = this.modelValue;
        } else if (this.xvalue !== undefined && this.xvalue !== null) {
            this.val = this.xvalue;
        }
    },
    computed: {
        getClass() {
            const baseClass = 'form-select';
            if (this.err === true || (typeof this.err === 'string' && this.err.trim() === '1')) {
                return `${baseClass} is-invalid ${this.customClass}`.trim();
            }
            return `${baseClass} ${this.customClass}`.trim();
        },
        filteredItems() {
            if (!this.q || this.q.trim() === '') {
                return this.items;
            }
            const query = this.q.trim().toLowerCase();
            return this.items.filter(item => {
                const title = this.getItemTitle(item).toLowerCase();
                return title.includes(query);
            });
        },
    },
    methods: {
        getItemTitle(item) {
            if (!item) return '';
            const val = item[this.titleField];
            if (this.xlang && typeof val === 'object' && val !== null) {
                return val[this.xlang] ?? Object.values(val)[0] ?? '';
            }
            if (typeof val === 'object' && val !== null) {
                const lang = document.documentElement.lang || 'fa';
                return val[lang] ?? Object.values(val)[0] ?? '';
            }
            return String(val ?? '');
        },
        selecting(i) {
            this.val = i;
            this.onSelect(this.val);
            if (this.closeOnSelect) {
                this.hideModal();
            }
        },
        select() {
            this.onSelect(this.val);
        },
        hideModal() {
            this.modalShow = false;
        },
        showModal() {
            this.modalShow = true;
            this.$nextTick(() => {
                if (this.$refs.searchInput) {
                    this.$refs.searchInput.focus();
                }
            });
        },
    },
    watch: {
        val(newValue) {
            if (!isNaN(this.modelValue)) {
                this.$emit('update:modelValue', newValue);
            }
            if (this.vuexDispatch != null) {
                this.$store.dispatch(this.vuexDispatch, newValue);
            }
        },
        modelValue(newVal) {
            if (!isNaN(newVal)) {
                this.val = newVal;
            }
        },
    },
};
</script>
