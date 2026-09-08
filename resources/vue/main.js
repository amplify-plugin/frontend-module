import {createApp} from 'vue';
import {createPinia} from "pinia";
import modules from './modules.js';

const app = createApp({})
    .use(createPinia())
    .use(modules)
    .mount('#app');