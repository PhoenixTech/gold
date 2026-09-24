import { createApp } from 'vue';
import bsToast, { ToastPlugin } from '../bs-toast.js';

const app = createApp({});
const $toast = bsToast;

import QuantitiesAddToCard from "../client-vue/QuantitiesAddToCard.vue";
app.component('quantities-add-to-card', QuantitiesAddToCard);

import videoPlayer from "../client-vue/videoPlayer.vue";
app.component('mp4player', videoPlayer);

import addressInput from "../client-vue/AddressInput.vue";
app.component('address-input', addressInput);

import NsCard from "../client-vue/NsCard.vue";
app.component('ns-card', NsCard);

app.use(ToastPlugin);
app.mount('#app');

window.app = app;
window.$toast = $toast;
