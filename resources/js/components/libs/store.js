import { reactive } from 'vue';

const state = reactive({
    category: '',
    quantities: [],
});

const store = {
    state,
    commit(type, payload) {
        if (type === 'UPDATE_CATEGORY') {
            state.category = payload;
        } else if (type === 'UPDATE_QUANTITIES') {
            state.quantities = payload;
        }
    },
    dispatch(action, payload) {
        if (action === 'updateCategory') {
            this.commit('UPDATE_CATEGORY', payload);
        } else if (action === 'updateQuantities') {
            this.commit('UPDATE_QUANTITIES', payload);
        }
    },
    install(app) {
        app.config.globalProperties.$store = this;
        app.provide('store', this);
    },
};

export default store;
