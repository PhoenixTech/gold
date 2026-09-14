import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import "./client-custom/assetsNode.js";
import "./client-custom/customerActions.js";
import "./client-custom/login.js";
import "./payment-receipt-uploader.js";
import "./client-custom/safeForm.js";
import "./client-custom/tabControll.js";
import "./client-custom/windowLoader.js";

// Client component scripts (required by ActiveThemeSegmentsTest)
import "./client/header.js";
import "./client/home.js";
import "./client/products.js";
import "./client/customer.js";
import "./client/gallery.js";
