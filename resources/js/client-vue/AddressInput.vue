<template>
    <div id="address-input">

        <div v-if="addresses.length" class="address-list">
            <div class="address-item" v-for="ad in addresses">
                <div class="address-item-main">
                    <div class="address-item-icon"><i class="ri-map-pin-2-line"></i></div>
                    <div class="address-item-text">{{ ad.address }}</div>
                </div>
                <div class="address-item-actions">
                    <button type="button" class="address-btn address-btn-edit" @click="editing(ad)">
                        <i class="ri-edit-2-line"></i>
                    </button>
                    <button type="button" class="address-btn address-btn-del" @click="removing(ad.id)">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                </div>
            </div>
        </div>
        <p v-else class="address-empty">
            <i class="ri-map-pin-line"></i>
        </p>

        <button type="button" class="address-add-btn" @click="adding">
            <i class="ri-add-line"></i>
            {{ translate['add-address'] || 'Add address' }}
        </button>

        <div id="address-modal" v-if="modal" @click.self="modal = false">
            <div class="address-modal-card">
                <div class="address-modal-head">
                    <h5><i class="ri-map-pin-user-line"></i> {{ translate['addr-editor'] }}</h5>
                    <button type="button" class="address-modal-close" @click="modal = false">
                        <i class="ri-close-line"></i>
                    </button>
                </div>
                <div class="address-modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="st">
                                {{ translate['state'] }} :
                            </label>
                            <select @change="updateState" class="form-control" v-model="state_id" id="st">
                                <option :data-lat="s.lat" :data-lng="s.lng" :value="s.id" v-for="s in states">
                                    {{ s.name }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="st">
                                {{ translate['city'] }}:
                            </label>
                            <select @change="updateCity" class="form-control" v-model="city_id" id="st">
                                <option :value="c.id" v-for="c in cities"> {{ c.name }}</option>
                            </select>
                        </div>
                        <div class="col-12 my-3">
                            <div ref="mapContainer" :style="'height: 300px;'+mapStyle"></div>
                        </div>
                        <div class="col-12">
                            <textarea rows="2" class="form-control" :placeholder="translate['address']"
                                      v-model="address"></textarea>
                        </div>
                        <div class="col-12 mt-3">
                            <label for="zip">
                                {{ translate['post-code'] }}:
                            </label>
                            <input type="text" id="zip" class="form-control" v-model="zip">
                        </div>
                    </div>
                </div>
                <div class="address-modal-foot">
                    <button class="address-save-btn" type="button" @click="save">
                        <i class="ri-save-2-line"></i>
                        {{ translate['save'] || 'Save' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>

import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import bsToast from '../bs-toast.js';
import axios from "axios";

const $toast = bsToast;

export default {
    name: "address-input",
    components: {},
    data: () => {
        return {
            id: null,
            action: 'add',
            modal: false,
            addresses: [],
            states: [],
            cities: [],
            state_id: null,
            city_id: null,
            map: null,
            marker: null,
            zoom: 10,
            address: '',
            zip: '',
            lat: null,
            lng: null,
        }
    },
    props: {
        listLink: {
            type: String,
            required: true,
        },
        addLink: {
            type: String,
            required: true,
        },
        updateLink: {
            type: String,
            required: true,
        },
        remLink: {
            type: String,
            required: true,
        },
        stateLink: {
            type: String,
            required: true,
        },
        citiesLink: {
            type: String,
            required: true,
        },
        darkMode: {
            type: Boolean,
            default: false,
        },
        translate: {
            default: {},
        }
    },
    async mounted() {
        try {
            let res = await axios.get(this.stateLink);
            this.states = res.data.data;
            // console.log(res.data);
        } catch (e) {
            $toast.error(e.message);
        }
        await this.updateList();
        // await this.initMap();
        // if (this.states[0].lat != null && this.states[0].lng != null){
        //     this.changeMapCenter(this.states[0].lat,this.states[0].lng)
        // }
    },
    computed: {
        mapStyle() {
            if (this.darkMode) {
                return 'filter: invert(100%) hue-rotate(120deg) brightness(95%) contrast(90%);';
            }
            return '';
        }
    },
    methods: {
        async save() {
            let canSave = true;
            if (this.state_id == null) {
                $toast.error("State is required"); // WIP translate
                canSave = false;
            }
            if (this.city_id == null) {
                $toast.error("City is required"); // WIP translate
                canSave = false;
            }
            if (this.address.length < 10) {
                $toast.error("Address is required"); // WIP translate
                canSave = false;
            }
            if (this.zip.length < 5) {
                $toast.error("Post code is required"); // WIP translate
                canSave = false;
            }
            if (!canSave) {
                return false;
            }

            if (this.action == 'add') {

                let data = {
                    address: this.address,
                    state_id: this.state_id,
                    city_id: this.city_id,
                    zip: this.zip,
                    lat: this.lat,
                    lng: this.lng
                };
                try {
                    let r = await axios.post(this.addLink, data);
                    if (r.data.OK) {
                        this.addresses = r.data.list;
                        $toast.success(r.data.message);
                        this.modal = false;
                    }
                } catch (e) {
                    $toast.error('err!' + e.message);
                }


            } else {
                let data = {
                    address: this.address,
                    state_id: this.state_id,
                    city_id: this.city_id,
                    zip: this.zip,
                    lat: this.lat,
                    lng: this.lng
                };
                try {
                    const url = this.updateLink + '/' + this.id;
                    let r = await axios.post(url, data);
                    if (r.data.OK) {
                        $toast.success(r.data.message);
                        await this.updateList();
                        this.modal = false;
                    }
                } catch (e) {
                    $toast.error('err!' + e.message);
                }
            }
        },
        showModal() {

            this.modal = true;
            setTimeout(() => {
                this.initMap();
            }, 50);
        },
        async removing(id) {
            if (!confirm('Sure?')) { //WIP: translate
                return;
            }

            const url = this.remLink + '/' + id;
            try {
                let r = await axios.get(url);
                if (r.data.OK) {
                    $toast.success(r.data.message);
                    this.updateList();
                }
            } catch (e) {
                $toast.error('err!' + e.message);
            }
        },
        async editing(dt) {
            this.showModal();
            this.action = 'edit';
            this.id = dt.id;
            this.lat = dt.lat;
            this.lng = dt.lng;
            this.zip = dt.zip;
            this.address = dt.address;
            this.state_id = dt.state_id;
            await this.updateState();
            this.city_id = dt.city_id;
            if (this.lng != null && this.lat != null) {
                this.zoom = 16;
                setTimeout(() => {
                    this.changeMapCenter(this.lat, this.lng);
                    this.marker = L.marker({lat: this.lat, lng: this.lng}).addTo(this.map);
                }, 100);
            }

        },
        adding() {
            this.action = 'add';
            this.address = '';
            this.zip = '';
            this.state_id = null;
            this.showModal();

        },
        initMap() {
            if (!import.meta.env.DEV){
                L.Icon.Default.mergeOptions({
                    iconRetinaUrl: "/assets/vendor/leaflet/marker-icon-2x.png",
                    iconUrl: "/assets/vendor/leaflet/marker-icon.png",
                    shadowUrl: "/assets/vendor/leaflet/marker-shadow.png"
                });
            }

            this.map = L.map(this.$refs.mapContainer).setView([35.83266000, 50.99155000], 10);


            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; openstreetmap',
                attributionControl: false,
            }).addTo(this.map);

            this.map.on('click', this.onMapClick);
            this.map.attributionControl.setPrefix('xShop');

        },
        onMapClick(e) {
            if (this.marker) {
                this.map.removeLayer(this.marker);
            }

            this.marker = L.marker(e.latlng).addTo(this.map);
            // You can emit the selected location or perform any other desired action here
            // console.log('Selected location:', e.latlng);
            this.getAddress(e.latlng);
            this.lat = e.latlng.lat;
            this.lng = e.latlng.lng;
        },
        changeMapCenter(lat, lng) {
            try {

                this.map.setView([lat, lng], this.zoom);
            } catch (e) {
                // console.log(e.message);
                setTimeout(() => {
                    console.log('repeat');
                    this.changeMapCenter(lat, lng);
                }, 10);
            }

            // Change the map center to [40.7128, -74.0059] (New York City) with zoom level 12

        },
        async updateList() {
            try {
                let res = await axios.get(this.listLink);
                this.addresses = res.data;
            } catch (e) {
                $toast.error('err!' + e.message);
            }
        },
        async updateState() {
            for (const st of this.states) {
                if (st.id == this.state_id) {
                    // console.log(st);
                    if (st.lat != null && st.lng != null) {
                        this.zoom = 10;
                        this.changeMapCenter(st.lat, st.lng)
                    }
                    break;
                }
            }

            try {
                let res = await axios.get(this.citiesLink + '/' + this.state_id);
                this.cities = res.data.data;
            } catch (e) {
                $toast.error('err!' + e.message);
            }
        },
        async updateCity() {
            for (const c of this.cities) {
                if (c.id == this.city_id) {
                    if (c.lat != null && c.lng != null) {
                        this.zoom = 12;
                        this.changeMapCenter(c.lat, c.lng)
                    }
                    break;
                }
            }
        },
        getAddress(latlng) {
            const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${latlng.lat}&lon=${latlng.lng}&addressdetails=1&accept-language=en`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    const address = this.formatAddress(data.address);
                    this.address = address;
                })
                .catch(error => {
                    $toast.error('err!' + error.message);
                });

        },
        formatAddress(addressData) {

            let formattedAddress = '';

            if (addressData.road) {
                formattedAddress += addressData.road;
            }
            if (addressData.neighbourhood) {
                formattedAddress += addressData.neighbourhood;
            }
            //
            // if (addressData.house_number) {
            //     formattedAddress += ` ${addressData.house_number}`;
            // }
            //
            if (addressData.postcode) {
                // formattedAddress += `, ${addressData.postcode}`;
                let x = addressData.postcode.split('-');
                this.zip = x.join('');
            }

            if (addressData.city) {
                formattedAddress += `, ${addressData.city}`;
            }
            //
            // if (addressData.country) {
            //     formattedAddress += `, ${addressData.country}`;
            // }

            return formattedAddress;
        },
    }
}
</script>

<style scoped>
#address-input {
    padding: 0 .25rem .5rem;
}

/* address list */
.address-list {
    display: grid;
    gap: .7rem;
    margin-bottom: .9rem;
}

.address-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    border: 1px solid color-mix(in srgb, var(--xshop-primary) 30%, transparent);
    border-radius: .9rem;
    background: var(--xshop-background);
    padding: .8rem .9rem;
    box-shadow: 0 3px 12px -8px rgba(0, 0, 0, .18);
    transition: transform .2s ease, box-shadow .2s ease;
}

.address-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px -10px rgba(0, 0, 0, .24);
}

.address-item-main {
    display: flex;
    align-items: center;
    gap: .7rem;
    min-width: 0;
}

.address-item-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    min-width: 40px;
    border-radius: 11px;
    background: color-mix(in srgb, var(--xshop-primary) 12%, var(--xshop-background));
    color: var(--xshop-primary);
    font-size: 19px;
}

.address-item-text {
    font-size: 14px;
    color: var(--xshop-text);
    line-height: 1.7;
}

.address-item-actions {
    display: inline-flex;
    gap: .45rem;
    flex-shrink: 0;
}

.address-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 10px;
    border: 1px solid color-mix(in srgb, var(--xshop-primary) 45%, transparent);
    color: var(--xshop-primary);
    background: var(--xshop-background);
    font-size: 16px;
    cursor: pointer;
    transition: all .18s ease;
}

.address-btn-edit:hover {
    background: var(--xshop-info);
    border-color: var(--xshop-info);
    color: var(--xshop-diff2);
}

.address-btn-del {
    border-color: color-mix(in srgb, var(--xshop-danger) 45%, transparent);
    color: var(--xshop-danger);
}

.address-btn-del:hover {
    background: var(--xshop-danger);
    border-color: var(--xshop-danger);
    color: #ffffff;
}

.address-empty {
    text-align: center;
    color: color-mix(in srgb, var(--xshop-text) 45%, transparent);
    padding: 1.5rem 1rem;
    margin: 0;
    font-size: 14px;
}

.address-empty i {
    font-size: 34px;
    display: block;
    margin-bottom: .5rem;
    color: color-mix(in srgb, var(--xshop-primary) 50%, transparent);
}

.address-add-btn {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .55rem 1.1rem;
    border-radius: 11px;
    border: 0;
    background: var(--xshop-primary);
    color: var(--xshop-diff2);
    font-size: 13.5px;
    font-weight: 600;
    cursor: pointer;
    transition: all .18s ease;
}

.address-add-btn:hover {
    background: var(--xshop-secondary);
    color: var(--xshop-diff2);
}

/* modal */
#address-modal {
    position: fixed;
    inset: 0;
    z-index: 2000;
    backdrop-filter: blur(4px);
    background: rgba(0, 0, 0, .35);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.address-modal-card {
    width: 100%;
    max-width: 820px;
    max-height: calc(100vh - 2rem);
    overflow-y: auto;
    border-radius: 1.1rem;
    background: var(--xshop-background);
    box-shadow: 0 30px 60px -20px rgba(0, 0, 0, .4);
    animation: addressModalIn .22s ease;
}

@keyframes addressModalIn {
    from {
        opacity: 0;
        transform: translateY(14px) scale(.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.address-modal-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.2rem;
    background: linear-gradient(135deg, var(--xshop-primary), color-mix(in srgb, var(--xshop-primary) 55%, var(--xshop-secondary)));
    border-radius: 1.1rem 1.1rem 0 0;
}

.address-modal-head h5 {
    margin: 0;
    color: var(--xshop-diff2);
    font-size: 16px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: .5rem;
}

.address-modal-close {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    border: 0;
    background: rgba(255, 255, 255, .2);
    color: var(--xshop-diff2);
    font-size: 17px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background .18s ease;
}

.address-modal-close:hover {
    background: rgba(255, 255, 255, .32);
}

.address-modal-body {
    padding: 1.2rem;
}

.address-modal-foot {
    padding: .9rem 1.2rem;
    border-top: 1px solid color-mix(in srgb, var(--xshop-primary) 22%, transparent);
}

.address-save-btn {
    width: 100%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .45rem;
    padding: .65rem;
    border-radius: 11px;
    border: 0;
    background: var(--xshop-primary);
    color: var(--xshop-diff2);
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: all .18s ease;
}

.address-save-btn:hover {
    background: var(--xshop-secondary);
    color: var(--xshop-diff2);
}

@media (max-width: 640px) {
    .address-item {
        flex-wrap: wrap;
    }

    .address-item-text {
        font-size: 13px;
    }

    .address-item-actions {
        width: 100%;
        justify-content: flex-end;
        border-top: 1px dashed color-mix(in srgb, var(--xshop-primary) 20%, transparent);
        padding-top: .5rem;
    }
}
</style>
