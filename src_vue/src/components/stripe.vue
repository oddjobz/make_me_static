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

            <div v-show="!loading">
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
                                            <div style="margin-bottom: 0.5em">
                                                This subscription can be upgraded or downgraded at any time. When a subscription is changed
                                                any fees or refunds are included pro-rata in the next card transaction.
                                            </div>
                                            <div>
                                                Downgrades are available no sooner than 24h after an upgrade.
                                            </div>
                                        </p>
                                        <div style="flex:1"></div>
                                        <div style="text-align:center" v-if="has_subscription">
                                            <Button
                                                style="width:15em"
                                                class="p-button" 
                                                severity="danger" 
                                                icon="pi pi-trash" 
                                                label="Cancel Subscription"
                                                @click="onClickCancel">
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                                <div v-else style="height:100%">
                                    <div class="dialog-content">
                                        <p class="m-0 card-text" style="flex:1">
                                            <div v-if="!has_autorenew" style="text-align:center;color:red;font-size: 1.1em">
                                                This service has been cancelled and will revert to a free subscription in <b>{{ days_remaining }}</b> days.
                                            </div>
                                        </p>
                                        <div style="text-align:center" v-if="has_subscription && has_autorenew">
                                            <Button
                                                style="width:15em"
                                                class="p-button" 
                                                severity="warn" 
                                                icon="pi pi-credit-card" 
                                                label="Change Payment Details"
                                                @click="onClickChangePayment">
                                            </Button>
                                        </div>
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
                                <template #title>Change your subscription to <span class="caption-class">{{ new_plan }}</span></template>
                                <template #content>
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
import { defineComponent, ref, watch, computed, inject, onMounted } from 'vue';
import { defineStore } from 'pinia';
import { OrbitComponentMixin, useLogger } from '@/../node_modules/orbit-component-base';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Card from 'primevue/card';
import ConfirmDialog from 'primevue/confirmdialog';
import ProgressSpinner from 'primevue/progressspinner';
import { useConfirm } from "primevue/useconfirm";
import { useSubsStore } from '@/stores/subsStore.js';
import { useRouteStore } from '@/stores/routeStore.js';
import { useRoute, useRouter } from 'vue-router';
//
//  VUE Router
//
const vroute        = useRoute()
const vrouter       = useRouter()
//
//
import pkg from '../../package.json';
//
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
// const visible           = computed(() => subsStore.items.length>0)
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
const show_cancellation = ref(false)
const show_upgrade      = ref(false)
const loading           = ref(true)
const error             = ref(null)
const route             = computed(() => route_data.value.get(route_ids.value[0]))
const changed           = computed(() => current_prod.value != selected.value)
const new_plan          = computed(() => selected.value)
const new_price         = computed(() => selected.value in products.value ? products.value[selected.value].currency : '')
const current_plan      = computed(() => route.value ? route.value.plan : '')
const current_price     = computed(() => products.value[current_plan.value].currency)
const has_autorenew     = computed(() => route.value && route.value.autorenew ? true : false)
const subscription      = computed(() => route.value && route.value.sub? route.value.sub : null)
const has_subscription  = computed(() => route.value && route.value.sub ? true : false)
const days_remaining    = computed(() => {
    if (!route.value) return 0
    return route.value.days - parseInt((new Date() - new Date(route.value.when * 1000))/1000/60/60/24) - 1
})
const wizard_image = computed(() => pkg.parameters.host + '/wizard.jpeg')

onMounted(async () => {
    if (!opt.router) return vrouter.go()
    log.debug ('* Loading subscription service: ', pkg.version, opt)
    await vrouter.isReady()
    await plugin (opt, namespace, socket);
    if (typeof Stripe === 'undefined') {
        log.debug ('Loading Stripe API')
        let node = document.createElement ('script')
        node.setAttribute ('src', 'https://js.stripe.com/v3/')
        document.head.appendChild (node)
    }
    onLoadModule ()
    //     if (authenticated.value && active.value) {
    //         loadProducts()
    //     }
    // }
    // visible.value = true
})

// watch (authenticated, () => {
//     console.log("Auth=", authenticated.value)
//     console.log("Active=", active.value)
//     if (authenticated.value && active.value) loadProducts()
// })
watch (vroute, (curr) => {
    log.error ('Stripe Route Change: ', curr)
    if (curr.path == '/stripe') {
        log.info ('Loading MMSDIR Module')
        onLoadModule()
    }
})


watch (subscription, () => {
    log.info('Subscription:', subscription.value)
})
watch (current_plan, (value) => {
    log.info('Plan Change: ', value)
    current_prod.value = value
    selected.value = value
})
watch (socket, () => {
    console.log("SOCKET CHANGED!")
    loadProducts()
})
watch (authenticated, () => {
    console.log("SOCKET CHANGED!")
    loadProducts()
})

function onHide () {
    visible.value = false
    window.dispatchEvent(new CustomEvent('MMS_CHANGE_PATH', {detail: '/'}))
}

function onLoadModule () {
    log.info ('Load Module here!')
    if (authenticated.value && active.value) loadProducts()
    visible.value = true
}

function loadProducts () {
    if (!authenticated.value||!active.value) return
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

function setLoading () { loading.value = true }
function clrLoading () { loading.value = false }

function onClickChange () {
    let message = '<div style="text-align:center">'+
                        'This will change your subscription to <b>' + new_plan.value.toUpperCase() + '</b> billed at <b>'+ new_price.value + '</b> /month<br/>' +
                        '<br/>Are you sure you wish to continue?'+
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
async function onClickChangePayment () {
    log.info('Change Payment Details')
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
    const onFormComplete = async () => {
        // current_prod.value = selected.value
        formReset()
    }
    const stripe = Stripe(pub_api_key)
    checkout.value = await stripe.initEmbeddedCheckout({fetchClientSecret, onComplete: onFormComplete})
    checkout.value.mount( '#checkout' )
}

function formReset () {
    checkout.value.unmount( '#checkout')
    checkout.value.destroy()
    checkout.value = null
    // if (change_prod.value) onClickSubscribe()
}

function onClickProduct (key) {
    selected.value = key
    if (checkout.value) formReset()
    log.error(current_prod.value)
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
async function startCancellation () {
    show_cancellation.value = true
}
async function startUpgrade () {
    show_upgrade.value = true
}
async function onClickSubscribe () {
    // if (current_prod.value == 'free')
    //     startSubscription ()
    // else if (selected.value == 'free')
    //     startCancellation ()
    // else
    //     startUpgrade()
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
    font-size: 1.1em;
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
</style>

<style>
.subscription-dialog .p-dialog-header, .subscription-dialog .p-dialog-content {
    background-color: #e5e5e5;
}

.subscription-dialog .p-card-caption {
    text-align: center;
}
.subscription-dialog .p-card-content {
    height: 100%;
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

</style>