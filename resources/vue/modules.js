import {defineAsyncComponent} from "vue";

export default {
    install(app) {
        app.component('Checkout', defineAsyncComponent(() => import('./modules/checkout/index.vue')));
    }
};