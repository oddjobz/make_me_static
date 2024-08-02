<template>
    <section class="content stripe">
        <Dialog 
            @update:visible="onHide"
            modal header="MMS Subscription Management"
            style="width:1050px;max-height:95%"
            class="subscription-dialog"
            :visible="visible"
            :closeOnEscape="true" :draggable="false">

            <ConfirmDialog group="templating">
                <template #message="slotProps">
                    <div class="flex flex-col items-center w-full gap-4 border-b border-surface-200 dark:border-surface-700">
                        <i :class="slotProps.message.icon" class="!text-6xl text-primary-500"></i>
                        <p style="font-size: 1.1em" v-html="slotProps.message.message" />
                    </div>
                </template>
            </ConfirmDialog>

            <ConfirmDialog group="okbox" class="okbox">
                <template #message="slotProps">
                    <div class="flex flex-col items-center w-full gap-4 border-b border-surface-200 dark:border-surface-700">
                        <i :class="slotProps.message.icon" class="!text-6xl text-primary-500"></i>
                        <p style="font-size: 1.1em" v-html="slotProps.message.message" />
                    </div>
                </template>
            </ConfirmDialog>

            <div v-show="loading" style="width:100%;height: 527px;display:flex" class="justify-center">
                <div class="spin-wrapper" v-if="!error">
                    <div class="spinner">
                        <ProgressSpinner style="width:70px;height:70px;visibility: visible" strokeWidth="8"/>
                        <div class="loading">L O A D I N G ...</div>
                    </div>
                </div>
                <div v-else style="width:100%;height:100%;padding-top:20%">
                    <div style="color: red;text-align:center;font-weight:600;font-size:1.3em">{{ error }}</div>
                    <div style="margin-top:1em;color: #999;text-align:center;font-weight:500;font-size:1.1em">
                        Please visit <a href="https://support.madpenguin.uk" target="_blank">Mad Penguin Support</a> for help
                    </div>
                </div>
            </div>

            <div v-show="!loading && visible">
                <ul class="products">
                    <li v-for="(val, key) in products" :key="key" style="flex:1">
                        <div 
                            @click="onClickProduct (key)"
                            :class='key == selected ? "product selected" : "product"'
                            >
                            <div><span class="name">{{ key }}</span> <span class="price">({{val.currency}}/month)</span></div>
                        </div>
                    </li>
                </ul>
                <div id="checkout" v-show="checkout"></div>
                <div v-if="!checkout">
                    <Card class="product-description">
                        <template #content>
                            {{ product_desc }}
                        </template>
                    </Card>
                    <div v-if="!changed"> 
                        <Card class="dialog-body">
                            <template #header>
                                <img alt="setup wizard" :src="wizard_image" class="wizard">
                            </template>
                            <template #title>You are currently subscribed to this service</template>
                            <template #content>
                                <div v-if="selected == 'free'" style="height:100%">
                                    <div class="dialog-content">
                                        <p class="m-0 card-text" >
                                            <div style="margin-top: 1em;font-size: 0.9em">
                                                You may upgrade to a paid subscription at any time with a mininum 1 month term.
                                                Once you have a paid subscription you may downgrade to a lower paid subscription no sooner than 24 hours
                                                following an upgrade. 
                                            </div>
                                            <div style="margin-top: 1em;font-size: 0.9em">
                                                Fees and credits for part month usage will appear pro-rata on your next bill.
                                                If you downgrade to the free service, your subscription will run to the end of the current billing
                                                cycle and won't renew.
                                            </div>
                                        </p>
                                        <div style="flex:1"></div>
                                        <!-- <div style="text-align:center" v-if="has_subscription">
                                            <Button
                                                style="width:15em"
                                                class="p-button" 
                                                severity="danger" 
                                                icon="pi pi-trash" 
                                                label="Cancel Subscription"
                                                @click="onClickCancel">
                                            </Button>
                                        </div> -->
                                    </div>
                                </div>
                                <div v-else class="dialog-content">
                                    <p class="m-0 card-text" style="flex:1">
                                        <div v-if="!has_autorenew" class="revert">
                                            This service has been cancelled and will revert to a free subscription in <b>{{ days_remaining }}</b> days.
                                        </div>
                                    </p>
                                    <div style="text-align:center" v-if="has_subscription && has_autorenew">
                                        <Button
                                            style="width:15em"
                                            class="p-button" 
                                            severity="warn" 
                                            icon="pi pi-credit-card" 
                                            label="Change Card Details"
                                            @click="onClickChangePayment">
                                        </Button>
                                    </div>
                                    <div style="text-align:center" v-else>
                                        <Button
                                            style="width:15em"
                                            class="p-button" 
                                            severity="success"
                                            icon="pi pi-credit-card" 
                                            label="Reverse Cancellation"
                                            @click="onClickReinstate">
                                        </Button>
                                    </div>

                                </div>
                            </template>
                        </Card>
                    </div>
                    <div v-else>
                        <div v-if="current_prod != 'free'">
                            <Card class="dialog-body">
                                <template #header>
                                    <img alt="setup wizard" :src="wizard_image" class="wizard">
                                </template>
                                <template #title>
                                    You are currently subscribed to the <span class="caption-class"> {{ current_plan }}</span> plan
                                </template>
                                <template #content v-if="has_autorenew">
                                    <div class="dialog-content">
                                        <p class="m-0 card-text">
                                            You are currently subscribed to the <b>{{ current_plan }}</b> plan which is billed at <b>{{ current_price }}</b>
                                            per month. If you select this option your subscription will be changed to the <b>{{ new_plan }}</b> plan 
                                            <span v-if="selected == 'free'">
                                                at the end of the current billing cycle in <b>{{ days_remaining }}</b> days time.
                                            </span>
                                            <span v-else>
                                                which is billed at <b>{{ new_price }}</b> per month. Note that your bill for the next billing cycle will be at
                                                the new rate and will include a one-off pro-rata for <b>{{ days_remaining }}</b> days of the current bulling period at 
                                                the new rate.
                                            </span>
                                        </p>
                                        <div style="flex:1"></div>
                                        <div style="text-align:center">
                                            <Button
                                                style="width:15em"
                                                class="p-button" 
                                                severity="help" 
                                                icon="pi pi-user-edit" 
                                                label="Change Subscription"
                                                @click="onClickChange">
                                            </Button>
                                        </div>
                                    </div>
                                </template>
                            </Card>
                        </div>
                    </div>
                </div>
            </div>
        </Dialog>
    </section>
</template>

<script setup>
import { defineComponent, ref, watch, computed, inject, onMounted, nextTick } from 'vue';
import { defineStore } from 'pinia';
import { OrbitComponentMixin } from '@/../node_modules/orbit-component-base';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Card from 'primevue/card';
import ConfirmDialog from 'primevue/confirmdialog';
import ProgressSpinner from 'primevue/progressspinner';
import { useConfirm } from "primevue/useconfirm";
import { useSubsStore } from '@/stores/subsStore.js';
import { useRouteStore } from '@/stores/routeStore.js';
import { useRoute, useRouter } from 'vue-router';
import { useLogger } from './OrbitLogger.js'
import pkg from '../../package.json';
//
//  VUE Router
//
const vroute            = useRoute()
const vrouter           = useRouter()
const confirm           = useConfirm();
const log               = useLogger()
const plugin            = inject('$orbitPlugin')
const connection        = inject('$connection')
const socket            = ref({connected:false})
const subsStore         = useSubsStore()
const routeStore        = useRouteStore()
const active            = computed(() => socket.value.connected)
const authenticated     = computed(() => connection.authenticated)
const visible           = ref(false)
const route_data        = computed(() => routeStore.data)
const route_ids         = computed(() => routeStore.ids(root.value))
const root              = computed(() => opt.router  ? opt.router.currentRoute.value.meta.root : '')
const selected          = ref(null)
const products          = ref({})
const product           = computed(() => products.value[selected.value])
const pub_api_key       = 'pk_test_51NkrJjDv2TmnMK2SfOtBgE9flxv2KGDjQzVbhmrHBHifzTiwqU0vZv5yRvh1P7YWnKGTirF1n0q4hYzblL2BEaRA00HdcIBgDL'
const checkout          = ref(null)
const product_desc      = computed(() => products.value ? (selected.value in products.value ? products.value[selected.value].desc : '') : '')
const change_prod       = computed(() => current_prod.value != selected.value)
const current_prod      = ref(null)
const loading           = ref(true)
const error             = ref(null)
const route             = computed(() => route_data.value.get(route_ids.value[0]))
const changed           = computed(() => current_prod.value && selected.value ? current_prod.value != selected.value : false)
const new_plan          = computed(() => selected.value)
const new_price         = computed(() => selected.value in products.value ? products.value[selected.value].currency : '')
const current_plan      = computed(() => route.value ? route.value.plan : '')
const current_price     = computed(() => products.value[current_plan.value].currency)
const has_autorenew     = computed(() => route.value && route.value.autorenew ? true : false)
const has_subscription  = computed(() => route.value && route.value.sub ? true : false)
const days_remaining    = computed(() => {
    if (!route.value) return 0
    return route.value.days - parseInt((new Date() - new Date(route.value.since * 1000))/1000/60/60/24)
})
const days_since        = computed(() => {
    return parseFloat((new Date() - new Date(route.value.since * 1000))/1000/60/60/24)
})
const wizard_image = computed(() => pkg.parameters.host + '/wizard.jpeg')

onMounted(async () => {
    if (!opt.router) return vrouter.go()
    await vrouter.isReady()
    await plugin (opt, namespace, socket);
    if (typeof Stripe === 'undefined') {
        let node = document.createElement ('script')
        node.setAttribute ('src', 'https://js.stripe.com/v3/')
        document.head.appendChild (node)
    }
    onLoadModule ()
})
watch (vroute, (curr) => {
    if (curr.path == '/stripe') {
        onLoadModule()
    }
})
watch (current_plan, (value) => {
    current_prod.value = value
    selected.value = value
})
watch (socket, () => {
    loadProducts()
})
watch (authenticated, () => {
    loadProducts()
})
function setLoading () { loading.value = true }
function clrLoading () { loading.value = false }
function onHide () {
    visible.value = false
    nextTick(() => {
        formReset ()
        window.dispatchEvent(new CustomEvent('MMS_CHANGE_PATH', {detail: '/'}))
    })
}
function onLoadModule () {
    if (authenticated.value && active.value) loadProducts()
    nextTick(() => {
        visible.value = true
    })
}
function loadProducts () {
    if (!authenticated.value||!active.value) return
    if (Object.keys(products.value).length) return
    if (!products.value.length) {
        log.info ('Loading product catalogue from Stripe')
        subsStore.init(app, root.value, socket.value).populate(root.value, (response) => {
            response.items.map((item) => {
                products.value[item.name] = {
                    'cost': item.price,
                    'desc': item.desc,
                    'currency': new Intl.NumberFormat(navigator.language, {style: "currency", currency: item.currency}).format(item.price),
                    'id': item.price_id
                }
            })
            setTimeout(() => {
                clrLoading()
            }, 500)
        })
        routeStore.init(app, root.value, socket.value).populate(root.value, () => {
            selected.value = current_prod.value = route_data.value.get(route_ids.value[0]).plan
        })
    } else {
        clrLoading()
    }
}
function onClickChange () {
    let message = '<div style="padding-left: 3em;padding-right:3em;max-width:500px">'+
                        'This will change your subscription to <b>' + new_plan.value.toUpperCase() + '</b> billed at <b>'+ new_price.value + '</b> per month. ' +
                        'Are you sure you wish to continue?'+
                    '</div>'
    confirm.require({
        group: 'templating',
        header: 'Change your Subscription',
        message: message,
        rejectProps: {
            label: 'No, Cancel this',
            icon: 'pi pi-times',
            outlined: true,
            size: 'small',
            severity: 'success'
        },
        acceptProps: {
            label: 'Yes, Continue',
            icon: 'pi pi-check',
            size: 'small',
            severity: 'danger'
        },
        accept: () => {
            setLoading()
            let params = {plan: new_plan.value, price_id: product.value.id}
            subsStore.call (root.value, 'change_subscription', params, (response) => {
                log.info (response)
                if (!response||!response.ok) error.value = 'Error calling "change subscription", please contact support@madpenguin.uk'
                else clrLoading()
                selected.value = current_plan.value
            })
        },
        reject: () => {
            console.log("Reject")
        }
    });
}

function onClickCancel () {
    log.info('Cancel Subscription')
}
function onClickReinstate () {
    log.info('Reinstate Subscription')
    let message = '<div style="padding-left: 1em;padding-right:1em;max-width:550px">'+
                        'This will reverse your cancellation and re-instate your subscription such that it will renew automatically in <b>' + days_remaining.value + '</b> days. '+
                        'Are you sure you wish to continue?'+
                    '</div>'
    confirm.require({
        group: 'templating',
        header: 'Reverse your Cancellation',
        message: message,
        accept: () => {
            setLoading()
            let params = {plan: new_plan.value, price_id: product.value.id}
            subsStore.call (root.value, 'reverse_cancellation', params, (response) => {
                log.info (response)
                if (!response||!response.ok) error.value = 'Error calling "reverse_cancellation", please contact support@madpenguin.uk'
                else clrLoading()
            })
        },
        reject: () => {
            log.debug ('Abort reversal')
        }
    });
}
async function onClickChangePayment () {
    setLoading()
    subsStore.call (root.value, 'update_payment_details', {}, (response) => {
        log.info (response)
        if (!response||!response.ok) error.value = 'Error calling "change subscription", please contact support@madpenguin.uk'
        else clrLoading()
        selected.value = current_plan.value
    })
    const fetchClientSecret = async () => {
        let response = await subsStore.sync_call (root.value, 'update_payment_details', {})
        return response.client_secret;
    }
    const onFormComplete = async () => formReset()
    const stripe = Stripe(pub_api_key)
    checkout.value = await stripe.initEmbeddedCheckout({fetchClientSecret, onComplete: onFormComplete})
    checkout.value.mount( '#checkout' )
}
function formReset () {
    if (checkout.value) {
        checkout.value.unmount( '#checkout')
        checkout.value.destroy()
        checkout.value = null
    }
}
function onClickProduct (key) {
    if (selected.value == key) {
        log.warn ('No Change!', selected.value, key)
        return
    }
    if ((current_prod.value != 'free') && (key != 'free')) {
        let old_cost = parseFloat(products.value[current_prod.value].cost)
        let new_cost = parseFloat(products.value[key].cost)
        log.warning ('Old=', old_cost, ' New=', new_cost, ' Days=', days_since.value, ' Bool=', new_cost < old_cost)
        if ((new_cost < old_cost) && (days_since.value < 1)) {
            log.warn ('Attempt to downgrade too soon!')
            confirm.require({
                group: 'okbox',
                header: 'Too Soon!',
                message: 'You must wait at least 24h following an upgrade before you can downgrade!',
            });
            return
        }
    }
    selected.value = key
    if (checkout.value) formReset()
    if (current_prod.value == 'free' && change_prod.value) startSubscription()
}
async function startSubscription () {
    const fetchClientSecret = async () => {
        let response = await subsStore.sync_call (root.value, 'create_checkout_session', {price_id: product.value.id})
        return response.client_secret;
    }
    const onFormComplete = async () => {
        current_prod.value = selected.value
        formReset()
    }
    const stripe = Stripe(pub_api_key)
    checkout.value = await stripe.initEmbeddedCheckout({fetchClientSecret, onComplete: onFormComplete})
    checkout.value.mount( '#checkout' )
}
</script>

<script>
const namespace = 'stripe'
const mixin = OrbitComponentMixin(namespace, defineStore)
const opt = ref(null)
const app = ref(null)

export default defineComponent({
    name: namespace,
    install (vue, options) {
        mixin.install (vue, options, this, app, opt)
    },
    bootstrap () {
        return mixin.bootstrap (pkg.version)
    },
})
</script>

<style scoped>
.product {
    cursor: pointer;
    border: 1px solid #999;
    border-radius: 6px;
    font-size: 1.05em;
    font-weight:600;
    padding: 1em;
    padding-top: 0.55em;
    padding-bottom: 0.5em;
    background-color:white;
    text-align: center;
}
.name {
    text-transform: capitalize;
}
.price {
    color:#888;
}
.products {
    display:flex;
    flex-direction:row
}
ul.products {
    margin-block-start: 0;
    padding-inline-start: 0;
}
ul.products li {
    list-style: none;
}
ul.products li:not(:nth-child(4)) {
    margin-right: 1em;
}
.product.selected {
    background-color: rgb(46, 111, 185);
    color: white;
    font-weight: 600;
}
.product.selected .price {
    color: #ddd;
}
.product-description {
    margin-bottom: 1em;
    font-size: 1.0em;
    color: teal;

}
#checkout {
    height: auto;
    overflow-y: auto;
    background-color: white;
    border-radius: 8px;
    padding-top: 1em;
    padding-bottom: 1em;
}
.card-text {
    font-size: 1.2em;
    padding-left:8em;
    padding-right:8em;
    text-align: justify;
    flex:0;
} 
.dialog-body {
    height: 28em;
    display:flex;
    flex-direction: column;
}
.dialog-content {
    display: flex;
    flex-direction: column;
    height: 100%;
}
.caption-class {
    text-transform: uppercase;
    color: teal;
    font-weight: 600;
}
.wizard {
    width:100%;
    height:8em;
    border-top-left-radius:8px;
    border-top-right-radius:8px;
}
.spin-wrapper {
    height: 100%;
    width:100%;
}
.spinner {
    text-align: center;
    position: absolute;
    top: 45%;
    min-height: 300px;
    width:95%;
}
.loading {
    font-weight: 800;
    visibility: visible;
}
.revert {
    margin-top: 2em;
    text-align: center;
    color:rgb(206, 32, 76);
    font-size: 1.1em;
}
</style>

<style>
.subscription-dialog .p-dialog-header, .subscription-dialog .p-dialog-content {
    background-color: #e5e5e5;
}

.subscription-dialog .p-card-caption {
    text-align: center;
}
.subscription-dialog .p-card-content {
    flex:1;
}
.subscription-dialog .p-card-body {
    display:flex;
    flex-direction: column;
    flex:1;
}
.subscription-dialog .p-card-body .p-card-body {
    height:100%;
}
.subscription-dialog {
    margin-bottom: 0.4em;
}
.subscription-dialog .p-card .p-card-content {
    padding: 0;
}
.subscription-dialog .p-card-title {
    line-height: 2em;
}
.subscription-dialog .p-card-body .p-card-content {
    height: 100%
}
div.p-dialog.p-component.p-ripple-disabled.p-confirm-dialog.okbox {
    background-color: white;
}
.okbox div.p-dialog-footer {
    height:0;
    visibility: hidden;
}
</style>