<template>
    <section class="content stripe">
        <Dialog v-model:visible="visible" modal header="Subscription Management" style="width:1050px;max-height:95%" class="subscription-dialog">

            <ConfirmDialog group="templating">
                <template #message="slotProps">
                    <div class="flex flex-col items-center w-full gap-4 border-b border-surface-200 dark:border-surface-700">
                        <i :class="slotProps.message.icon" class="!text-6xl text-primary-500"></i>
                        <p style="font-size: 1.1em" v-html="slotProps.message.message" />
                    </div>
                </template>
            </ConfirmDialog>

            <div v-show="loading" style="width:100%;height: 527px;padding-top:20%;display:flex" class="card flex justify-center">
                <ProgressSpinner style="width: 100px; height: 100px" strokeWidth="8" fill="transparent"
                    animationDuration=".5s" aria-label="Custom ProgressSpinner" v-if="!error"/>
                <div v-else style="width:100%">
                    <div style="color: red;text-align:center;font-weight:600;font-size:1.2em">{{ error }}</div>
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
                <div id="checkout"></div>
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
                                            <div>
                                                This subscription can be upgraded or downgraded at any time. When a subscription is changed
                                                any fees or refunds are included pro-rata in the next card transaction.
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
                                                at the end of the current billing cycle in <b>{{ days_remaining }}</b> days time. No more charges will be
                                                applied to your card.
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

import pkg from '../../package.json';

const confirm           = useConfirm();
const log               = useLogger()
const plugin            = inject('$orbitPlugin')
const connection        = inject('$connection')
const socket            = ref({connected:false})
const subsStore         = useSubsStore()
const routeStore        = useRouteStore()
const active            = computed(() => socket.value.connected)
const authenticated     = computed(() => connection.authenticated)
const visible           = computed(() => subsStore.items.length>0)
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
const loading           = ref(false)
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
const wizard_image = computed(() => {
    return `${window.MMS_API_Settings.crawler}/wizard.jpeg`
})

onMounted(async () => {
    console.log("!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!")
    console.log("Opt=", opt)
    console.log("NS=", namespace)
    await plugin (opt, namespace, socket);
    let node = document.createElement ('script')
    node.setAttribute ('src', 'https://js.stripe.com/v3/')
    document.head.appendChild (node)
})
watch (subscription, () => {
    log.info('Subscription:', subscription.value)
})
watch (current_plan, (value) => {
    log.info('Plan Change: ', value)
    current_prod.value = value
    selected.value = value
})
watch (authenticated, () => {
    subsStore.init(app, root.value, socket.value).populate(root.value, (response) => {
        response.items.map((item) => {
            products.value[item.name] = {
                'cost': item.price,
                'desc': item.desc,
                'currency': new Intl.NumberFormat(navigator.language, {style: "currency", currency: item.currency}).format(item.price),
                'id': item.price_id
            }
        })
    })
    routeStore.init(app, root.value, socket.value).populate(root.value, (response) => {
        selected.value = response.data[0].plan
        current_prod.value = response.data[0].plan
    })
})

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
}
.card-text {
    font-size: 1.2em;
    padding-left:8em;
    padding-right:8em;
    text-align: justify;
    flex:0;
} 
.p-card-title {
    color: red;
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
</style>

<style>
.subscription-dialog .p-card-body {
    height: 100%;
}
.subscription-dialog .p-card-caption {
    text-align: center;
}
.subscription-dialog .p-card-content {
    height: 100%;
}
.subscription-dialog {
    margin-bottom: 0.4em;
    background-color: #f2f2f2!important;
}

</style>





<!-- <div v-if="show_cancellation">
                <Card>
                    <template #title style="color:red">Cancel your Subscription</template>
                    <template #content>
                        <p class="m-0 card-text">
                            Cancelling your subscription will prevent the system from renewing your subscription
                            at the end of the current billing cycle. In this instance, no more charges will be taken from
                            your card and in <b>{{ route.days }}</b> days you will revert to the free tier service.
                        </p>
                        <p class="m-0 card-text">
                            Are you sure you wish to cancel?
                        </p><br/>
                        <Button 
                            class="p-button" 
                            severity="danger" 
                            icon="pi pi-times-circle" 
                            label="&nbsp;Cancel Subscription&nbsp;&nbsp; "
                            @click="onClickCancel">
                        </Button>&nbsp;&nbsp;
                        <Button 
                            class="p-button" 
                            severity="info" 
                            icon="pi pi-arrow-circle-left" 
                            label="&nbsp;Abort&nbsp;&nbsp; "
                            @click="onClickAbort">
                        </Button>
                    </template>
                </Card>
            </div>
            <div v-if="show_upgrade">
                Upgrade Options!
            </div>  -->


                    <!-- <Button 
                        class="p-button" 
                        severity="help" 
                        icon="pi pi-sparkles" 
                        label="&nbsp;Change&nbsp;&nbsp; "
                        @click="onClickSubscribe"
                        :disabled="!change_prod"
                        v-if="!checkout">
                    </Button> -->
