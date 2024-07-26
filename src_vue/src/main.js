// This is mostly Orbit boilerplate generated by orbit-cli

import { createApp, reactive } from 'vue'
import { createPinia } from 'pinia';
import { createRouter, createWebHashHistory } from 'vue-router'
import { OrbitConnection, BaseClass } from '@/../node_modules/orbit-component-base'
import { menu as orbit_component_mmsdir } from '@/components/mmsdir.js';
import { menu as orbit_component_stripe } from '@/components/stripe.js';
import PrimeVue from 'primevue/config';
import App from './App.vue'
import metadata from '@/../package.json';
import ConfirmationService from 'primevue/confirmationservice';

const router = createRouter({ history: createWebHashHistory(), routes: [] })
const pinia = createPinia()
const app = createApp(App)
const menu = reactive([])

app.metadata = metadata
app.metadata.components = new Map()
pinia.use(BaseClass)
app.use(pinia)
app.use(OrbitConnection, {pinia: pinia})
app.provide('$menu', menu)
orbit_component_mmsdir(app, router, menu);
orbit_component_stripe(app, router, menu);
app.use(router)
setTimeout(() => {
    router.push('/stripe')
    console.log("Stripe")    
}, 10000)
app.use(PrimeVue)
app.use(ConfirmationService);
app.mount('#mms-directory')
