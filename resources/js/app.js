/**
 * Admin Panel Application Entry Point
 * Architecture: Scoped ES modules with container element guards
 */

import './bootstrap';
import { createApp } from 'vue';
import bsToast, { ToastPlugin } from './bs-toast.js';
import store from './components/libs/store.js';

// Panel modular controllers
import { initNavbar } from './panel/navbar.js';
import { initBulkActions } from './panel/list-checkboxes.js';
import { initGeneralEvents } from './panel/general-events.js';
import { initQuillEditors } from './panel/editor-handle.js';
import { initStepController } from './panel/step-controller.js';
import { initProductUpload } from './panel/product-upload-controller.js';
import { initSettingSections } from './panel/setting-section-controller.js';
import { initSortableController } from './panel/sortable-controller.js';
import { initPanelPreloader } from './panel/panel-window-loader.js';
import { initFastEdit } from './panel/fast-edit.js';
import { initFastAttachment } from './panel/fast-attachment.js';

// Initialize Vue 3 Application
const app = createApp({});
const $toast = bsToast;

// Active Admin Vue Components

import CurrencyInput from './components/CurrencyInput.vue';
app.component('currency-input', CurrencyInput);

import RemixIconPicker from './components/RemixIconPicker.vue';
app.component('remix-icon-picker', RemixIconPicker);

import vueDateTimePicker from './components/vueDateTimePicker.vue';
app.component('vue-datetime-picker-input', vueDateTimePicker);

import SearchableSelect from './components/SearchableSelect.vue';
app.component('searchable-select', SearchableSelect);

import SearchableMultiSelect from './components/SearchableMultiSelect.vue';
app.component('searchable-multi-select', SearchableMultiSelect);

import Increment from './components/Increment.vue';
app.component('increment', Increment);

import TagInput from './components/TagInput.vue';
app.component('tag-input', TagInput);

import AddressInput from './components/AddressInput.vue';
app.component('address-input', AddressInput);

import PropTypeInput from './components/PropTypeInput.vue';
app.component('props-type-input', PropTypeInput);

import MetaInput from './components/MetaInput.vue';
app.component('meta-input', MetaInput);

import StockItemsInput from './components/StockItemsInput.vue';
app.component('stock-items-input', StockItemsInput);

import MorphSelector from './components/MorphSelector.vue';
app.component('morph-selector', MorphSelector);

import Latlng from './components/latlng.vue';
app.component('lat-lng', Latlng);

import MenuItemInput from './components/MenuItemInput.vue';
app.component('menu-item-input', MenuItemInput);

import VueTimepicker from './components/vueTimePicker.vue';
app.component('vue-time-picker', VueTimepicker);

import FastAttaching from './components/FastAttaching.vue';
app.component('fast-attaching', FastAttaching);

// Mount Vue
app.use(ToastPlugin);
app.use(store);
app.mount('#app');

// Scoped Panel Lifecycle Initialization on DOMContentLoaded with single initQuillEditors() call
document.addEventListener('DOMContentLoaded', () => {
    initNavbar();
    initBulkActions();
    initGeneralEvents();
    initQuillEditors();
    initStepController();
    initProductUpload();
    initSettingSections();
    initSortableController();
    initPanelPreloader();
    initFastEdit();
    initFastAttachment();
});

// Singletons & Services
window.app = app;
window.$toast = $toast;
window.store = store;
window.initQuillEditors = initQuillEditors;
