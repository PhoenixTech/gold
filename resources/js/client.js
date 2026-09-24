import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import "./client/client-app.js";
import "./client/storefront-actions.js";
import "./client/login.js";
import "./client/payment-receipt-uploader.js";
import "./client/safe-form.js";
import "./client/tab-control.js";
import "./client/window-loader.js";

import "./client/header.js";
import "./client/home.js";
import "./client/products.js";
import "./client/customer.js";
import "./client/gallery.js";
