<template>
    <div class="dropdown searchable-multi-select-component d-inline-block position-relative" ref="dropdownRef">
        <!-- Trigger Button -->
        <button
            type="button"
            class="btn btn-sm btn-outline-secondary dropdown-toggle d-inline-flex align-items-center gap-1 bg-white text-dark border shadow-none"
            :class="[getClass, customClass]"
            :id="xid || undefined"
            @click.stop="toggleDropdown"
            :aria-expanded="isOpen"
            style="min-height: 31px;"
        >
            <i class="ri-filter-3-line text-muted fs-14"></i>
            <span class="fs-13 fw-normal">{{ buttonLabel }}</span>
            <span v-if="val.length > 0" class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill ms-1 fs-11">
                {{ val.length }}
            </span>
        </button>

        <!-- Dropdown Menu -->
        <div
            v-if="isOpen"
            class="dropdown-menu show shadow-sm border p-2 mt-1 start-0"
            style="display: block !important; min-width: 250px; max-width: 320px; z-index: 1055; position: absolute; top: 100%;"
            @click.stop
        >
            <!-- Search Input -->
            <div class="mb-2">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0 text-muted">
                        <i class="ri-search-line"></i>
                    </span>
                    <input
                        type="search"
                        class="form-control border-start-0"
                        v-model="q"
                        ref="searchInput"
                        :placeholder="isFa ? 'جستجو...' : 'Search...'"
                    >
                    <button v-if="q" class="btn btn-outline-secondary border-start-0" type="button" @click="q = ''">
                        <i class="ri-close-line"></i>
                    </button>
                </div>
            </div>

            <!-- Quick Action Links -->
            <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 border-bottom fs-12">
                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-primary fs-12" @click="selectAll">
                    {{ isFa ? 'انتخاب همه' : 'Select all' }}
                </button>
                <button v-if="val.length > 0" type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-danger fs-12" @click="clearAll">
                    {{ isFa ? 'پاک کردن' : 'Clear' }} ({{ val.length }})
                </button>
            </div>

            <!-- Items List -->
            <div class="overflow-y-auto" style="max-height: 220px;">
                <label
                    v-for="item in filteredItems"
                    :key="item[valueField]"
                    class="dropdown-item d-flex align-items-center gap-2 py-1 px-2 rounded-1 user-select-none"
                    style="cursor: pointer;"
                >
                    <input
                        type="checkbox"
                        class="form-check-input mt-0 flex-shrink-0"
                        :checked="isSelected(item[valueField])"
                        @change="selecting(item[valueField])"
                    >
                    <span class="fs-13 text-dark text-truncate">{{ getItemTitle(item) }}</span>
                </label>
                <div v-if="filteredItems.length === 0" class="text-center py-2 text-muted fs-13">
                    {{ isFa ? 'موردی یافت نشد' : 'No results found' }}
                </div>
            </div>
        </div>

        <!-- Optional Selected Tags Below -->
        <div v-if="showTags && val.length > 0" class="d-flex flex-wrap gap-1 mt-1">
            <span
                v-for="item in selectedItems"
                :key="item[valueField]"
                class="badge bg-secondary-subtle text-dark border border-secondary-subtle d-inline-flex align-items-center gap-1 fs-12 fw-normal"
            >
                {{ getItemTitle(item) }}
                <i class="ri-close-line text-muted" style="cursor: pointer;" @click.stop="rem(item[valueField])"></i>
            </span>
        </div>

        <!-- Hidden input for native form submission -->
        <input type="hidden" :name="xname" :value="val.length > 0 ? JSON.stringify(val) : ''">
    </div>
</template>

<script>
export default {
    name: "searchable-multi-select",
    emits: ['update:modelValue'],
    props: {
        xlang: {
            default: null,
        },
        modelValue: {
            default: 'nop',
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
            default: () => [],
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
        showTags: {
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
            isOpen: false,
            q: '',
            val: [],
        };
    },
    mounted() {
        let initial = [];
        if (this.modelValue !== 'nop' && this.modelValue !== undefined && this.modelValue !== null) {
            initial = this.modelValue;
        } else if (this.xvalue) {
            initial = this.xvalue;
        }

        if (typeof initial === 'string') {
            try {
                let parsed = JSON.parse(initial);
                if (typeof parsed === 'string') {
                    try {
                        parsed = JSON.parse(parsed);
                    } catch (e) {}
                }
                this.val = Array.isArray(parsed) ? parsed : [parsed];
            } catch (e) {
                this.val = initial.trim() !== '' ? [initial.trim()] : [];
            }
        } else if (Array.isArray(initial)) {
            if (initial.length === 1 && typeof initial[0] === 'string' && (initial[0].startsWith('[') || initial[0].startsWith('{'))) {
                try {
                    const parsed = JSON.parse(initial[0]);
                    this.val = Array.isArray(parsed) ? parsed : [parsed];
                } catch (e) {
                    this.val = [...initial];
                }
            } else {
                this.val = [...initial];
            }
        } else if (initial !== null && initial !== undefined && initial !== '') {
            this.val = [initial];
        } else {
            this.val = [];
        }

        document.addEventListener('click', this.handleClickOutside);
        document.addEventListener('keydown', this.handleKeyDown);
    },
    beforeUnmount() {
        document.removeEventListener('click', this.handleClickOutside);
        document.removeEventListener('keydown', this.handleKeyDown);
    },
    computed: {
        isFa() {
            return (document.documentElement.lang || 'fa') === 'fa';
        },
        getClass() {
            if (this.err === true || (typeof this.err === 'string' && this.err.trim() === '1')) {
                return 'is-invalid';
            }
            return '';
        },
        buttonLabel() {
            if (this.val.length === 0) {
                return this.xtitle || (this.isFa ? 'انتخاب' : 'Select');
            }
            if (this.val.length === 1) {
                const selected = this.items.find(i => String(i[this.valueField]) === String(this.val[0]));
                if (selected) {
                    return (this.xtitle ? this.xtitle + ': ' : '') + this.getItemTitle(selected);
                }
            }
            return (this.xtitle || (this.isFa ? 'انتخاب‌شده' : 'Selected')) + ' (' + this.val.length + ')';
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
        selectedItems() {
            return this.items.filter(item => this.isSelected(item[this.valueField]));
        },
    },
    methods: {
        toggleDropdown() {
            this.isOpen = !this.isOpen;
            if (this.isOpen) {
                this.$nextTick(() => {
                    if (this.$refs.searchInput) {
                        this.$refs.searchInput.focus();
                    }
                });
            }
        },
        handleClickOutside(e) {
            if (!this.isOpen) return;
            if (this.$refs.dropdownRef && !this.$refs.dropdownRef.contains(e.target)) {
                this.isOpen = false;
            }
        },
        handleKeyDown(e) {
            if (e.key === 'Escape' && this.isOpen) {
                this.isOpen = false;
            }
        },
        getItemTitle(item) {
            if (!item) return '';
            let val = item[this.titleField];
            if (this.xlang && typeof val === 'object' && val !== null) {
                return val[this.xlang] ?? Object.values(val)[0] ?? '';
            }
            if (typeof val === 'object' && val !== null) {
                const lang = document.documentElement.lang || 'fa';
                return val[lang] ?? Object.values(val)[0] ?? '';
            }
            return String(val ?? '');
        },
        isSelected(v) {
            return this.val.some(item => String(item) === String(v));
        },
        selecting(v) {
            const idx = this.val.findIndex(item => String(item) === String(v));
            if (idx === -1) {
                this.val.push(v);
            } else {
                this.val.splice(idx, 1);
            }
            this.emitChange(v);
            if (this.closeOnSelect) {
                this.isOpen = false;
            }
        },
        rem(v) {
            const idx = this.val.findIndex(item => String(item) === String(v));
            if (idx !== -1) {
                this.val.splice(idx, 1);
                this.emitChange(v);
            }
        },
        selectAll() {
            const allIds = this.filteredItems.map(item => item[this.valueField]);
            const set = new Set([...this.val.map(String), ...allIds.map(String)]);
            this.val = Array.from(set);
            this.emitChange();
        },
        clearAll() {
            this.val = [];
            this.emitChange();
        },
        emitChange(lastChanged = null) {
            if (this.modelValue !== 'nop') {
                this.$emit('update:modelValue', this.val);
            }
            this.onSelect(this.val, lastChanged);
        },
    },
    watch: {
        modelValue(newVal) {
            if (newVal !== 'nop' && Array.isArray(newVal)) {
                this.val = [...newVal];
            }
        },
    },
};
</script>
