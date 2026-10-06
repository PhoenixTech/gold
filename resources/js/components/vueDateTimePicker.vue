<template>
    <div id="vue-datepicker">
        <div id="dp-modal" @click.self="closeModal" v-if="modalShow">
            <div id="picker">
                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                    <div class="nav nav-pills gap-1">
                        <button type="button" class="btn btn-sm rounded-pill py-1 px-2.5"
                                :class="tabIndex === 0 ? 'btn-primary' : 'btn-light border-0 text-muted'"
                                @click="tabIndex = 0">
                            {{ pTitle }}
                        </button>
                        <button type="button" class="btn btn-sm rounded-pill py-1 px-2.5"
                                :class="tabIndex === 1 ? 'btn-primary' : 'btn-light border-0 text-muted'"
                                @click="tabIndex = 1">
                            {{ gTitle }}
                        </button>
                        <button v-if="timepicker" type="button" class="btn btn-sm rounded-pill py-1 px-2.5"
                                :class="tabIndex === 2 ? 'btn-primary' : 'btn-light border-0 text-muted'"
                                @click="tabIndex = 2">
                            <i class="ri-time-line me-1"></i>
                            {{ tTitle }}
                        </button>
                    </div>
                    <button type="button" class="btn btn-sm btn-light rounded-circle p-0 d-flex align-items-center justify-content-center text-muted"
                            style="width: 32px; height: 32px;"
                            @click="closeModal" title="بستن">
                        <i class="ri-close-line fs-5"></i>
                    </button>
                </div>

                <div class="d-flex align-items-center justify-content-between mb-3 px-1" v-if="pDate !== null && tabIndex < 2">
                    <button type="button" class="btn btn-sm btn-light border-0 p-1" style="width: 32px; height: 32px;" @click="previous" title="ماه قبل">
                        <i class="ri-arrow-right-s-line fs-5"></i>
                    </button>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary fw-bold px-2 py-1" @click="monthPick">
                            <span v-if="tabIndex === 0">{{ pMonths[parseInt(peDate[1]) - 1] }}</span>
                            <span v-else>{{ gMonths[geDate[1]] }}</span>
                            <i class="ri-arrow-down-s-line ms-1"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary fw-bold px-2 py-1" @click="yearPick">
                            <span v-if="tabIndex === 0">{{ pDate.parseHindi(peDate[0]) }}</span>
                            <span v-else>{{ geDate[0] }}</span>
                            <i class="ri-arrow-down-s-line ms-1"></i>
                        </button>
                    </div>
                    <button type="button" class="btn btn-sm btn-light border-0 p-1" style="width: 32px; height: 32px;" @click="next" title="ماه بعد">
                        <i class="ri-arrow-left-s-line fs-5"></i>
                    </button>
                </div>

                <div class="position-relative" style="min-height: 240px;">
                    <div class="sub-picker p-2" v-if="yPicker">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <button type="button" class="btn btn-sm btn-light border-0" @click="startYear -= 12">
                                <i class="ri-arrow-right-s-line"></i>
                            </button>
                            <span class="fw-bold fs-14 text-secondary">انتخاب سال</span>
                            <button type="button" class="btn btn-sm btn-light border-0" @click="startYear += 12">
                                <i class="ri-arrow-left-s-line"></i>
                            </button>
                        </div>
                        <div class="row g-2">
                            <div class="col-4" v-for="i in 12" :key="i">
                                <button type="button"
                                        class="btn btn-sm w-100 py-2 font-monospace"
                                        :class="parseInt(tabIndex === 0 ? peDate[0] : geDate[0]) === (startYear - 6 + i) ? 'btn-primary' : 'btn-light border'"
                                        @click="yearPicking(startYear - 6 + i)">
                                    {{ tabIndex === 0 ? pDate.parseHindi(startYear - 6 + i) : (startYear - 6 + i) }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="sub-picker p-2" v-if="pmPicker">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fw-bold fs-14 text-secondary">انتخاب ماه</span>
                            <button type="button" class="btn btn-sm btn-light border-0" @click="pmPicker = false">
                                <i class="ri-close-line"></i>
                            </button>
                        </div>
                        <div class="row g-2">
                            <div class="col-4" v-for="(m, i) in pMonths" :key="m">
                                <button type="button"
                                        class="btn btn-sm w-100 py-2"
                                        :class="(parseInt(peDate[1]) - 1 === i) ? 'btn-primary' : 'btn-light border'"
                                        @click="pMonthPicking(i)">
                                    {{ m }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="sub-picker p-2" v-if="gmPicker">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fw-bold fs-14 text-secondary">Select Month</span>
                            <button type="button" class="btn btn-sm btn-light border-0" @click="gmPicker = false">
                                <i class="ri-close-line"></i>
                            </button>
                        </div>
                        <div class="row g-2">
                            <div class="col-4" v-for="(m, i) in gMonths" :key="m">
                                <button type="button"
                                        class="btn btn-sm w-100 py-2"
                                        :class="(geDate[1] === i) ? 'btn-primary' : 'btn-light border'"
                                        @click="gMonthPicking(i)">
                                    {{ m }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="tabIndex === 0">
                        <table class="calendar-table w-100">
                            <thead>
                                <tr>
                                    <th v-for="(day, idx) in pWeekDays" :key="day" :class="{ 'text-danger': idx === 6 }">
                                        {{ day }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody v-if="pDate !== null">
                                <tr v-for="(week, wIdx) in pArray" :key="wIdx">
                                    <td v-for="(d, dIdx) in week" :key="dIdx">
                                        <button type="button"
                                                class="day-btn"
                                                :class="[d.class, isActive(d)]"
                                                :disabled="isDateDisabled(d)"
                                                :title="d.date"
                                                @click="select(d)">
                                            {{ pDate.parseHindi(d.pDay) }}
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="tabIndex === 1" dir="ltr">
                        <table class="calendar-table w-100">
                            <thead>
                                <tr>
                                    <th v-for="(day, idx) in gWeekDays" :key="day" :class="{ 'text-danger': idx === 0 }">
                                        {{ day }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody v-if="pDate !== null">
                                <tr v-for="(week, wIdx) in gArray" :key="wIdx">
                                    <td v-for="(d, dIdx) in week" :key="dIdx">
                                        <button type="button"
                                                class="day-btn"
                                                :class="[d.class, isActive(d)]"
                                                :disabled="isDateDisabled(d)"
                                                :title="d.pdate"
                                                @click="select(d)">
                                            {{ d.day }}
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="tabIndex === 2" class="py-2">
                        <div class="text-center p-3 mb-3 bg-light rounded-3 border">
                            <span class="d-block fs-12 text-muted mb-1">زمان انتخاب شده</span>
                            <div class="fs-1 fw-bold text-primary font-monospace" dir="ltr">
                                {{ String(cTime[0]).padStart(2, '0') }} : {{ String(cTime[1]).padStart(2, '0') }}
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-center gap-4 mb-3" dir="ltr">
                            <div class="d-flex flex-column align-items-center">
                                <span class="fs-12 text-muted mb-1 fw-semibold">ساعت</span>
                                <button type="button" class="btn btn-outline-secondary btn-sm rounded-circle p-0"
                                        style="width: 32px; height: 32px;"
                                        @click="incrementHour(1)">
                                    <i class="ri-arrow-up-s-line fs-5"></i>
                                </button>
                                <div class="step-value font-monospace my-1" @wheel.prevent="incrementHour($event.deltaY < 0 ? 1 : -1)">
                                    {{ String(cTime[0]).padStart(2, '0') }}
                                </div>
                                <button type="button" class="btn btn-outline-secondary btn-sm rounded-circle p-0"
                                        style="width: 32px; height: 32px;"
                                        @click="incrementHour(-1)">
                                    <i class="ri-arrow-down-s-line fs-5"></i>
                                </button>
                            </div>

                            <div class="fs-1 fw-bold text-muted mt-3">:</div>

                            <div class="d-flex flex-column align-items-center">
                                <span class="fs-12 text-muted mb-1 fw-semibold">دقیقه</span>
                                <button type="button" class="btn btn-outline-secondary btn-sm rounded-circle p-0"
                                        style="width: 32px; height: 32px;"
                                        @click="incrementMinute(5)">
                                    <i class="ri-arrow-up-s-line fs-5"></i>
                                </button>
                                <div class="step-value font-monospace my-1" @wheel.prevent="incrementMinute($event.deltaY < 0 ? 1 : -1)">
                                    {{ String(cTime[1]).padStart(2, '0') }}
                                </div>
                                <button type="button" class="btn btn-outline-secondary btn-sm rounded-circle p-0"
                                        style="width: 32px; height: 32px;"
                                        @click="incrementMinute(-5)">
                                    <i class="ri-arrow-down-s-line fs-5"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <span class="fs-12 text-muted d-block mb-1">دقیقه:</span>
                            <div class="d-flex gap-1 justify-content-center" dir="ltr">
                                <button type="button"
                                        v-for="m in [0, 15, 30, 45]"
                                        :key="m"
                                        class="btn btn-sm btn-light border flex-fill font-monospace py-1"
                                        :class="{ 'btn-primary text-white': cTime[1] === m }"
                                        @click="setMinute(m)">
                                    :{{ String(m).padStart(2, '0') }}
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <span class="fs-12 text-muted d-block mb-1">میانبر ساعت:</span>
                            <div class="d-flex gap-1 justify-content-center">
                                <button type="button"
                                        v-for="s in [{h:0,m:0,t:'۰۰:۰۰'}, {h:9,m:0,t:'۰۹:۰۰'}, {h:12,m:0,t:'۱۲:۰۰'}, {h:18,m:0,t:'۱۸:۰۰'}, {h:23,m:59,t:'۲۳:۵۹'}]"
                                        :key="s.t"
                                        class="btn btn-sm btn-light border flex-fill py-1 fs-12"
                                        @click="setQuickTime(s.h, s.m)">
                                    {{ s.t }}
                                </button>
                            </div>
                        </div>

                        <div class="d-flex justify-content-center pt-1">
                            <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1 px-3 py-1.5" @click="tabIndex = 0">
                                <i class="ri-calendar-line"></i>
                                <span>بازگشت به تقویم</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="timepicker && tabIndex < 2"
                     class="d-flex align-items-center justify-content-between mt-2 p-2 rounded-3 bg-light border">
                    <div class="d-flex align-items-center gap-1.5 text-muted fs-13">
                        <i class="ri-time-line text-primary fs-14"></i>
                        <span>ساعت:</span>
                        <span class="fw-bold font-monospace text-dark fs-14" dir="ltr">
                            {{ String(cTime[0]).padStart(2, '0') }}:{{ String(cTime[1]).padStart(2, '0') }}
                        </span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary py-0.5 px-2 fs-12 d-flex align-items-center gap-1" @click="tabIndex = 2">
                        <span>تنظیم ساعت</span>
                        <i class="ri-arrow-left-s-line"></i>
                    </button>
                </div>

                <div class="d-flex align-items-center justify-content-between pt-3 mt-2 border-top gap-2">
                    <button type="button" class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1 py-1 px-2" @click="clear" title="پاک کردن">
                        <i class="ri-delete-bin-line"></i>
                        <span class="fs-12">پاک</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-light border d-flex align-items-center gap-1 py-1 px-2" @click="nowSelect" title="زمان کنونی">
                        <i class="ri-time-line"></i>
                        <span class="fs-12">اکنون</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-primary d-flex align-items-center gap-1 py-1 px-3 ms-auto" @click="confirmAndClose">
                        <i class="ri-check-line"></i>
                        <span class="fs-12 fw-bold">تایید</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="position-relative">
            <div class="input-group">
                <input @click="openModal"
                       @focus="openModal"
                       :id="xid"
                       :placeholder="xtitle"
                       :class="getClass"
                       type="text"
                       readonly
                       :value="(val == null || val === '' ? '' : selectedDateTime)">
                <button class="btn btn-outline-secondary" type="button" @click="openModal">
                    <i class="ri-calendar-2-line"></i>
                </button>
            </div>
            <input v-if="xname" type="hidden" :name="xname" :value="val">
        </div>
    </div>
</template>

<script>
import persianDate from './libs/persian-date.js';

const ONE_DAY = 86400;
const ONE_YEAR = ONE_DAY * 365;

function chunkArray(arr, count) {
    const result = [];
    for (let i = 0; i < arr.length; i += count) {
        result.push(arr.slice(i, i + count));
    }
    return result;
}

export default {
    name: "vue-datetimepicker",
    data: () => {
        return {
            modalShow: false,
            pDate: null,
            startYear: 1403,
            tabIndex: 0,
            current: null,
            gmPicker: false,
            pmPicker: false,
            yPicker: false,
            val: null,
            pWeekDays: ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'],
            gWeekDays: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
            pMonths: ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'],
            gMonths: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
        };
    },
    emits: ['update:modelValue'],
    props: {
        modelValue: {
            default: NaN,
        },
        xvalue: {
            default: null,
            type: [Number, String],
        },
        xmax: {
            default: null,
            type: Number,
        },
        xmin: {
            default: null,
            type: Number,
        },
        xshow: {
            default: 'pdate',
            type: String,
        },
        onSelect: {
            default: function (date) {},
            type: Function,
        },
        pTitle: {
            default: 'شمسی',
            type: String,
        },
        gTitle: {
            default: 'میلادی',
            type: String,
        },
        tTitle: {
            default: 'ساعت',
            type: String,
        },
        defTab: {
            default: 0,
            type: [Number, String],
        },
        xname: {
            default: "",
            type: String,
        },
        xtitle: {
            default: "",
            type: String,
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
            type: [Boolean, String, Number],
        },
        timepicker: {
            default: false,
            type: Boolean,
        },
        closeOnSelect: {
            default: false,
            type: Boolean,
        },
    },
    mounted() {
        this.pDate = new persianDate();
        let initialTs = null;

        if (!isNaN(this.modelValue) && this.modelValue !== null && this.modelValue !== '' && this.modelValue !== 'null') {
            initialTs = parseInt(this.modelValue);
        } else if (this.xvalue !== null && this.xvalue !== '' && this.xvalue !== 'null' && !isNaN(parseInt(this.xvalue))) {
            initialTs = parseInt(this.xvalue);
        }

        if (initialTs !== null && !isNaN(initialTs)) {
            this.val = initialTs;
            this.current = initialTs;
        } else {
            this.val = null;
            this.current = Math.floor(Date.now() / 1000);
        }

        this.tabIndex = parseInt(this.defTab) || 0;
        this.startYear = parseInt(this.peDate[0]) || 1403;
        window.addEventListener('keydown', this.handleKeyDown);
    },
    beforeUnmount() {
        window.removeEventListener('keydown', this.handleKeyDown);
    },
    computed: {
        selectedDateTime() {
            if (this.val == null || this.val === '') {
                return '';
            }
            if (!this.pDate) {
                return '';
            }
            const dt = new Date(this.val * 1000);
            const obj = this.makeDateObject(dt);
            return obj[this.xshow] || obj.pdatetime || '';
        },
        getClass() {
            let base = 'form-control text-center bg-white cursor-pointer';
            if (this.err === true || this.err === '1' || this.err === 1) {
                base += ' is-invalid';
            }
            if (this.customClass) {
                base += ' ' + this.customClass;
            }
            return base;
        },
        gArray() {
            let result = [];
            const baseDate = (this.current || Math.floor(Date.now() / 1000)) * 1000;
            let d = new Date(baseDate);
            const currentMonth = d.getMonth();
            for (let i = 0; i >= -7; i--) {
                d = new Date(baseDate);
                d.setDate(i);
                result.push(this.makeDateObject(d, 'previous'));
                if (d.getDay() === 0) {
                    break;
                }
            }
            result = result.reverse();
            let nextCount = 0;
            for (let i = 1; i <= 45; i++) {
                d = new Date(baseDate);
                d.setDate(i);
                if (d.getMonth() === currentMonth) {
                    result.push(this.makeDateObject(d));
                } else {
                    if (d.getDay() === 0 && nextCount > 0) {
                        break;
                    }
                    result.push(this.makeDateObject(d, 'next'));
                    nextCount++;
                }
            }
            return chunkArray(result, 7);
        },
        pArray() {
            if (!this.pDate) {
                return [];
            }
            let result = [];
            const baseDate = (this.current || Math.floor(Date.now() / 1000)) * 1000;
            let d = this.pDate.convertDate2Persian(new Date(baseDate));
            const currentMonth = d[1];
            for (let i = 0; i > -40; i--) {
                let dt = new Date(baseDate + (i * ONE_DAY * 1000));
                let pdt = this.pDate.convertDate2Persian(dt);
                if (pdt[1] === currentMonth) {
                    result.push(this.makeDateObject(dt));
                } else {
                    result.push(this.makeDateObject(dt, 'previous'));
                    if (this.makePWeek(dt) === 0) {
                        break;
                    }
                }
            }
            result = result.reverse();
            for (let i = 1; i < 40; i++) {
                let dt = new Date(baseDate + (i * ONE_DAY * 1000));
                let pdt = this.pDate.convertDate2Persian(dt);
                if (pdt[1] === currentMonth) {
                    result.push(this.makeDateObject(dt));
                } else {
                    result.push(this.makeDateObject(dt, 'next'));
                    if (this.makePWeek(dt) === 6) {
                        break;
                    }
                }
            }
            return chunkArray(result, 7);
        },
        geDate() {
            const baseDate = (this.current || Math.floor(Date.now() / 1000)) * 1000;
            let d = new Date(baseDate);
            return [d.getFullYear(), d.getMonth(), d.getDate()];
        },
        peDate() {
            if (!this.pDate) {
                return ['1403', '01', '01'];
            }
            const baseDate = (this.current || Math.floor(Date.now() / 1000)) * 1000;
            let d = new Date(baseDate);
            return this.pDate.convertDate2Persian(d);
        },
        cTime() {
            const ts = (this.val || this.current || Math.floor(Date.now() / 1000)) * 1000;
            const d = new Date(ts);
            return [d.getHours(), d.getMinutes()];
        },
    },
    methods: {
        openModal() {
            if (this.val == null && !this.current) {
                this.current = Math.floor(Date.now() / 1000);
            }
            this.modalShow = true;
        },
        closeModal() {
            this.modalShow = false;
            this.pmPicker = false;
            this.gmPicker = false;
            this.yPicker = false;
        },
        confirmAndClose() {
            if (this.val == null) {
                this.val = this.current || Math.floor(Date.now() / 1000);
            }
            this.closeModal();
        },
        clear() {
            this.val = null;
            this.current = Math.floor(Date.now() / 1000);
            this.closeModal();
        },
        nowSelect() {
            const now = Math.floor(Date.now() / 1000);
            this.val = now;
            this.current = now;
            this.onSelect(this.makeDateObject(new Date(now * 1000)));
        },
        handleKeyDown(e) {
            if (e.key === 'Escape' && this.modalShow) {
                this.closeModal();
            }
        },
        isDateDisabled(d) {
            if (this.xmax != null && d.unix > this.xmax) {
                return true;
            }
            if (this.xmin != null && d.unix < this.xmin) {
                return true;
            }
            return false;
        },
        isActive(obj) {
            let r = '';
            if (this.val != null) {
                const dt = new Date(this.val * 1000);
                const gMonthStr = String(dt.getMonth() + 1).padStart(2, '0');
                const gDayStr = String(dt.getDate()).padStart(2, '0');
                if (`${dt.getFullYear()}-${gMonthStr}-${gDayStr}` === obj.date) {
                    r = 'active-selected';
                }
            }
            return r;
        },
        select(obj) {
            if (this.isDateDisabled(obj)) {
                return false;
            }
            if (obj.class === 'next') {
                this.next();
                return false;
            }
            if (obj.class === 'previous') {
                this.previous();
                return false;
            }
            this.val = obj.unix;
            this.current = obj.unix;
            this.onSelect(obj);
            if (this.closeOnSelect && !this.timepicker) {
                this.closeModal();
            }
            return true;
        },
        setTime(h, m) {
            const base = (this.val || this.current || Math.floor(Date.now() / 1000)) * 1000;
            const dt = new Date(base);
            dt.setHours(h, m, 0, 0);
            this.val = Math.floor(dt.getTime() / 1000);
            this.current = this.val;
        },
        setHour(h) {
            this.setTime(Math.max(0, Math.min(23, parseInt(h) || 0)), this.cTime[1]);
        },
        setMinute(m) {
            this.setTime(this.cTime[0], Math.max(0, Math.min(59, parseInt(m) || 0)));
        },
        incrementHour(delta) {
            this.setHour((this.cTime[0] + delta + 24) % 24);
        },
        incrementMinute(delta) {
            this.setMinute((this.cTime[1] + delta + 60) % 60);
        },
        setQuickTime(h, m) {
            this.setTime(h, m);
        },
        next() {
            let dt = new Date((this.current || Math.floor(Date.now() / 1000)) * 1000);
            if (this.tabIndex === 1) {
                dt.setMonth(dt.getMonth() + 1);
            } else {
                let currentMonth = this.pDate.convertDate2Persian(new Date(dt))[1];
                do {
                    dt.setDate(dt.getDate() + 10);
                } while (currentMonth === this.pDate.convertDate2Persian(dt)[1]);
            }
            this.current = Math.floor(dt.getTime() / 1000);
        },
        previous() {
            let dt = new Date((this.current || Math.floor(Date.now() / 1000)) * 1000);
            if (this.tabIndex === 1) {
                dt.setMonth(dt.getMonth() - 1);
            } else {
                let currentMonth = this.pDate.convertDate2Persian(new Date(dt))[1];
                do {
                    dt.setDate(dt.getDate() - 10);
                } while (currentMonth === this.pDate.convertDate2Persian(dt)[1]);
            }
            this.current = Math.floor(dt.getTime() / 1000);
        },
        yearPick() {
            this.startYear = parseInt(this.tabIndex === 1 ? this.geDate[0] : this.peDate[0]);
            this.yPicker = !this.yPicker;
            this.pmPicker = false;
            this.gmPicker = false;
        },
        monthPick() {
            if (this.tabIndex === 1) {
                this.gmPicker = !this.gmPicker;
                this.pmPicker = false;
            } else {
                this.pmPicker = !this.pmPicker;
                this.gmPicker = false;
            }
            this.yPicker = false;
        },
        yearPicking(i) {
            let dt = new Date((this.current || Math.floor(Date.now() / 1000)) * 1000);
            if (this.tabIndex === 1) {
                dt.setFullYear(i);
                this.current = Math.floor(dt.getTime() / 1000);
            } else {
                let diff = ONE_YEAR * (i - parseInt(this.peDate[0]));
                this.current = Math.floor(this.current + diff);
            }
            this.yPicker = false;
        },
        pMonthPicking(i) {
            let dt = new Date((this.current || Math.floor(Date.now() / 1000)) * 1000);
            let targetMonth = String(i + 1).padStart(2, '0');
            if (targetMonth !== this.peDate[1]) {
                let dir = (i + 1 < parseInt(this.peDate[1])) ? -10 : 10;
                do {
                    dt.setDate(dt.getDate() + dir);
                } while (targetMonth !== this.pDate.convertDate2Persian(dt)[1]);
                this.current = Math.floor(dt.getTime() / 1000);
            }
            this.pmPicker = false;
        },
        gMonthPicking(i) {
            let dt = new Date((this.current || Math.floor(Date.now() / 1000)) * 1000);
            dt.setMonth(parseInt(i));
            this.current = Math.floor(dt.getTime() / 1000);
            this.gmPicker = false;
        },
        makeDateObject(dt, cls) {
            const dateObj = new Date(dt.getTime());
            dateObj.setHours(this.cTime[0], this.cTime[1], 0, 0);
            const pArr = this.pDate ? this.pDate.convertDate2Persian(dateObj) : ['1403', '01', '01'];
            const hh = String(this.cTime[0]).padStart(2, '0');
            const mm = String(this.cTime[1]).padStart(2, '0');
            const pdateStr = pArr.join('/');
            const pdateTimeStr = `${pdateStr} ${hh}:${mm}`;
            const gMonthStr = String(dateObj.getMonth() + 1).padStart(2, '0');
            const gDayStr = String(dateObj.getDate()).padStart(2, '0');
            const gdateStr = `${dateObj.getFullYear()}-${gMonthStr}-${gDayStr}`;
            const gdateTimeStr = `${gdateStr} ${hh}:${mm}`;
            return {
                day: gDayStr,
                pDay: pArr[2],
                date: gdateStr,
                datetime: gdateTimeStr,
                pdatetime: pdateTimeStr,
                pdate: pdateStr,
                hpdatetime: this.pDate ? this.pDate.parseHindi(pdateTimeStr) : pdateTimeStr,
                hpdate: this.pDate ? this.pDate.parseHindi(pdateStr) : pdateStr,
                weekDay: dateObj.getDay(),
                class: cls,
                unix: Math.floor(dateObj.getTime() / 1000),
            };
        },
        makePWeek(dt) {
            let t = (dt.getDay() + 1) % 7;
            return t === 7 ? 0 : t;
        },
    },
    watch: {
        val(newVal) {
            this.$emit('update:modelValue', newVal);
        },
        modelValue(newVal) {
            if (!isNaN(newVal) && newVal !== null && newVal !== '' && newVal !== 'null') {
                this.val = parseInt(newVal);
                this.current = parseInt(newVal);
            } else if (newVal === null || newVal === '') {
                this.val = null;
            }
        },
        xvalue(newVal) {
            if (newVal !== null && newVal !== '' && newVal !== 'null' && !isNaN(parseInt(newVal))) {
                this.val = parseInt(newVal);
                this.current = parseInt(newVal);
            } else if (newVal === null || newVal === '') {
                this.val = null;
            }
        },
    },
}
</script>

<style scoped>
#vue-datepicker {
    font-size: 14px;
    direction: rtl;
    width: 100%;
}

.cursor-pointer {
    cursor: pointer;
}

#dp-modal {
    position: fixed;
    inset: 0;
    width: 100vw;
    height: 100vh;
    z-index: 2050;
    background: rgba(15, 23, 42, 0.5);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    overflow-y: auto;
    box-sizing: border-box;
}

#picker {
    position: relative;
    width: 100%;
    max-width: 380px;
    max-height: calc(100vh - 32px);
    overflow-y: auto;
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    padding: 16px;
    color: #1e293b;
    direction: rtl;
    box-sizing: border-box;
    font-family: 'Yekan Bakh VF', 'Yekan Bakh', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    border: 1px solid rgba(0, 0, 0, 0.08);
}

.sub-picker {
    position: absolute;
    inset: 0;
    background: #ffffff;
    z-index: 10;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
}

.calendar-table {
    border-collapse: separate;
    border-spacing: 2px;
}

.calendar-table th {
    text-align: center;
    padding: 6px 0;
    font-size: 12px;
    font-weight: 700;
    color: #64748b;
}

.calendar-table td {
    text-align: center;
    padding: 2px;
}

.day-btn {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: none;
    background: transparent;
    font-size: 13px;
    font-weight: 500;
    color: #1e293b;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s;
    margin: auto;
}

.day-btn:hover:not(:disabled) {
    background: #f1f5f9;
    color: #0f172a;
}

.day-btn.active-selected {
    background: #0d6efd;
    color: #ffffff !important;
    font-weight: 700;
    box-shadow: 0 4px 6px -1px rgba(13, 110, 253, 0.3);
}

.day-btn.previous,
.day-btn.next {
    color: #94a3b8;
    opacity: 0.6;
}

.day-btn:disabled {
    color: #cbd5e1;
    cursor: not-allowed;
    background: transparent;
}

.step-value {
    width: 70px;
    height: 50px;
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    font-size: 24px;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    user-select: none;
}
</style>
