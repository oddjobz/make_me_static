<!--
    This is the directory application - not hugely complicated.
    * Includes a "read terms" gateway for first time users
    * Dynamically loads the UI from an appropriate server based on license/route
-->

<template>
    <section class="content mmsdir">
        <ConfirmDialog :closable=false group="confirmtac">
            <template #message="slotProps">
                <div style="display:flex;flex-direction: column">
                    <div style="width:600px" v-html="slotProps.message.message"/>
                    <ul style="margin-top: 5px;margin-bottom: 5px">
                        <li v-for="item in slotProps.message.links">
                            <a :href="item.link" target="_blank">{{ item.html }}</a>
                        </li>
                    </ul>
                    <div style="margin-top: 7px">
                        <div class="card flex flex-wrap justify-content-center gap-3" style="position:fixed">
                            <Checkbox style="text-align: center"
                                v-model="checked"
                                :binary="true"
                                id="agree"
                                aria-describedby="checked-help" />
                            <label class="agree" for="agree">I Agree to these terms of use </label>
                        </div>                        
                    </div>
                </div>
            </template>
        </ConfirmDialog>
        <div class="spin-wrapper" v-if="state==1">
            <div class="spinner">
                <ProgressSpinner style="width:70px;height:70px;visibility: visible" strokeWidth="8"/>
                <div class="loading">L O A D I N G ...</div>
            </div>
        </div>
        <div class="spin-wrapper" v-if="state==2">
            <TermsAndConditions :checked="ischecked" :root="root" :answer="answer" @terms-rejected="state=3" @terms-accepted="state=1"/>
        </div>
        <div class="unauthorized" v-if="state==3">
            <div class="head">N O T &nbsp;&nbsp;&nbsp; A L L O W E D</div>
            <p class="text">
                <div>Sorry, but you must Accept the Terms and Conditions before you can use this Software.</div>
            </p>
        </div>
        <div class="unauthorized" v-if="state==4">
            <div class="head">N O T &nbsp;&nbsp;&nbsp; A L L O W E D</div>
            <p class="text">
                <div>Something went wrong authenticating your account or session</div>
                <div>Please try logging out of Wordpress and logging back in, if that</div>
                <div>doesn't work please contact technical support: support@madpenguin.uk</div>
            </p>
        </div>
        <div class="spin-wrapper">
            <div id="mms-crawler" :style="app_style" />
        </div>
    </section>
</template>

<script setup>
import ProgressSpinner from 'primevue/progressspinner';
import { defineComponent, ref, watch, computed, inject, onMounted, toRaw, readonly } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import TermsAndConditions from "@/components/termsandconditions.vue";
import ConfirmDialog from 'primevue/confirmdialog';
import Checkbox from 'primevue/checkbox';
import pkg from '../../package.json';
import { useLogger } from './OrbitLogger.js'
//
//  RouteStore
//
import { useRouteStore } from '@/stores/routeStore.js';
const routeStore = useRouteStore(window.pinia);
const route_data    = computed(() => routeStore.data)
const route_ids     = computed(() => routeStore.ids(root.value))
const route         = computed(() => route_ids.value.length ? route_data.value.get(route_ids.value[0]) : null)
//
//  VUE Router
//
const vroute        = useRoute()
const vrouter       = useRouter()
//
//  Orbit Globals
//
const plugin        = inject('$orbitPlugin')
const connection    = inject('$connection')
const socket        = ref({connected:false})
//
//  Application Specific
//
const ischecked     = computed(() => checked.value)
const answer        = computed(() => !route||!route.value ? null : route.value.answer)
const have_app      = ref(false)
const have_route    = ref(false)
const unauthorized  = ref(false)
const checked       = ref(false)
const root          = computed(() => opt && opt.router ? opt.router.currentRoute.value.meta.root : null)
const app_style     = computed(() => have_app.value ? "height:100%;width:100%" : "height:0;width:0")
const auth1         = computed(() => connection.authenticated)
const auth2         = ref(false)
const state         = ref(1)
const loaded        = ref(false)
const apiurl        = ref(window.MMS_API_Settings.apiurl)
const nonce         = ref(window.MMS_API_Settings.nonce)
const log           = useLogger()
const crawler_app   = ref(null)
//
//  Wait for the Vue Router to come ready
//
onMounted(async () => {
    log.debug('Waiting for VUE Router')
    await vrouter.isReady()
    log.debug('Ready')
})

const emitRoute = () => {
    window.dispatchEvent(new CustomEvent('MMS_NEW_ROUTE', {detail: route.value}))
}

//
//  These are the triggers that change our state
//
watch (auth1, () => {
    log.debug('Register with Wordpress')
    registerWithWordpress()
})  // Initial Connection to Orbit
watch (auth2, () => {
    log.debug('Load the Route Store')
    loadRoute()
})  // Connection to Direcory App
watch (route, (curr, prev) => {
    if (!prev || (curr.url != prev.url)) {
        log.debug('Load the Crawler', curr.url, prev)
        if (route.value.answer) loadCrawler (); else state.value = 2
    } else {
        log.debug("No Change")
    }
    emitRoute()
})
//
//  Register our host_id with Wordpress to the MMS service can validate
//  this host_id is allowed to scan the site.
//
function registerWithWordpress () {
    if (loaded.value||!auth1.value) return
    const auth = vroute.query.key ? vroute.query.key : ''
    let params = {method: 'GET', headers: {'X-WP-Nonce': nonce.value}}
    const url = new URL(apiurl.value + 'mms/v1/register_host');
    const host_id = connection.hostid
    let conn = null
    url.searchParams.set('key', auth);
    url.searchParams.set('host_id', host_id);
    url.searchParams.set('site', window.MMS_API_Settings.uuid);
    fetch(url.href, params).then (async (response) => {
        switch (response.status) {
            case 200:
                log.debug('Registered with Wordpress')
                window.make_me_static = window.MMS_API_Settings
                window.make_me_static.host_id = host_id
                conn = await plugin (opt, namespace, socket);
                conn.events.authenticated = () => auth2.value = true
                break;
            case 403:
                log.error('Access Denied trying to register with Wordpress')
                state.value = 4;
                break;
            default:
                log.error('Access Denied trying to register with Wordpress')
                log.error("ERROR: Status = ", response.status)
        }
    })
    .catch ((error) => {
        log.error(error)
    })
}
//
//  Initialise out DataStore (for routes) and populate 
//
function loadRoute () {
    let store = routeStore.init(app, root.value, socket.value)
    window.routeStore = store  
    store.validate(root.value, (response) => {
        if (!response||!response.ok) {
            log.error("validation failed", response)
            unauthorized.value = true
            return
        }
        store.populate(root.value, (response) => {
            if (!response || !response.ok || !route_ids.value.length) {
                log("populate failed", response)
                unauthorized.value = true
                return
            }
            have_route.value = true
        })
    })
}
//
//  Load up the relevant MMS crawler. Unfortunately this needs to be dynamic.
//  The UI loads off the crawler in question, and the crawler is chosen based
//  on the license and route return from the directory server. Each crawler
//  can run a different version of the software which means different versions
//  can be in service at the same time. Given the nature of crawler this is
//  absolutely necessary for sorting glitches and edge cases for specific sites.
//  
//  TODO :: beef up logging / error detection
//
// function reLoadCrawler () {
//     var script = document.getElementById('mms-crawler-app')
//     if (script != null) {
//         state.value = 1
//         log.debug ('Unmounting old version')
//         window.mms_crawler_app.unmount()
//         log.warn ('Removing old tag', script)
//         script.parentNode.removeChild( script )
//         setTimeout (() => {
//             loadCrawler()
//         },1)
//     } else loadCrawler ()
// }

function loadCrawler () {
    let application = null
    state.value = 1
    if (!window.mms_crawlers) {
        window.mms_crawlers = new Map()
    }
    const oldKey = MMS_API_Settings.crawler
    if (crawler_app.value) {
        log.info ('Cleaning up previous crawler instance')
        crawler_app.value.unmount()
        var script = document.getElementById('mms-crawler-app')
        if (script != null) {
            log.info ('Removing old tag')
            script.parentNode.removeChild( script )
        }
    }
    window.make_me_static.crawler = route.value.url
    window.MMS_API_Settings.crawler = route.value.url
    const url = new URL(route.value.url);
    url.pathname = window.MMS_API_Settings.crawler == "https://mms-crawler-dev.madpenguin.uk" ? 'src/main.js' : 'assets/index.js'
    const js = document.createElement('script');
    js.addEventListener('load', () => {
        const factory = window.mms_crawlers.get(MMS_API_Settings.crawler)
        crawler_app.value = factory.create()
        crawler_app.value.mount('#mms-crawler')
        have_app.value = true;
        state.value = 0;
        setTimeout(() => emitRoute(), 500)
    });
    js.addEventListener('error', () => {
        log.error('failed to load:', url)
    });
    js.type="module"
    js.id = "mms-crawler-app";
    js.src = url
    document.body.appendChild(js);
    log.debug('..loading..')


    // log.warn ('Loading new version => ', route.value.url)
    // var script = document.getElementById('mms-crawler-app')
    // if (script != null) {
    //     const key = MMS_API_Settings.crawler
    //     log.info ('Unmounting old version', key)
    //     console.log("Crawlers>", mms_crawlers)
    //     console.log("HAS: ", window.mms_crawlers.has(key))
    //     let app = window.mms_crawlers.get(key)
    //     app.unmount()
    //     log.warn ('Removing old tag')
    //     script.parentNode.removeChild( script )
    // }
    // window.make_me_static.crawler = route.value.url
    // window.MMS_API_Settings.crawler = route.value.url
    // const url = new URL(route.value.url);
    // url.pathname = window.MMS_API_Settings.crawler == "https://mms-crawler-dev.madpenguin.uk" ? 'src/main.js' : 'assets/index.js'
    // const key = MMS_API_Settings.crawler
    // // if (window.mms_crawlers.has(key)) {
    // //     log.warn('experimental reload')
    // //     let app = window.mms_crawlers.get(key)
    // //     app.mount('#mms-crawler')
    // // } else {
    // const js = document.createElement('script');
    // js.addEventListener('load', () => {
    //     const key = MMS_API_Settings.crawler
    //     log.debug("Crawler loaded", key)
    //     console.log("Crawlers>", mms_crawlers)
    //     console.log("HAS: ", window.mms_crawlers.has(key))
    //     let app = window.mms_crawlers.get(key)
    //     log.info ('Mount => ', app)
    //     app.mount('#mms-crawler')
    //     have_app.value = true;
    //     state.value = 0;
    //     emitRoute()
    // });
    // js.addEventListener('error', () => {
    //     log.error('failed to load:', url)
    // });
    // js.type="module"
    // js.id = "mms-crawler-app";
    // js.src = url
    // document.body.appendChild(js);
    // log.debug('..loading..')
}

</script>

<script>
//
//  This is mostly Orbit boilerplate for loading an app.
//
import { defineStore } from 'pinia';
import { OrbitComponentMixin } from '@/../node_modules/orbit-component-base';
import { useLogger } from './OrbitLogger.js'

const namespace = 'mmsdir'
const mixin = OrbitComponentMixin(namespace, defineStore)
const opt = ref(null)
const app = ref(null)
const log = useLogger()

export default defineComponent({
    name: namespace,
    install (vue, options) {
        log.debug ('Loading directory service: ', pkg.version)
        mixin.install (vue, options, this, app, opt)
    },
})
</script>

<style scoped>
.agree {
    margin-top: 2px;
    margin-left: 0.8em;
}
.mmsdir {
    height:100%;
}
.spin-wrapper {
    height: 100%;
    width: 100%;
}
.spinner {
    text-align: center;
    height: 100%;
    width: 100%;
    position: absolute;
    top: 40%;
}
.loading {
    font-weight: 800;
    visibility: visible;
}
.unauthorized {
    height:100%;
    position: absolute;
    top: 50%;
    width: 100%;
    text-align: center;
    font-size: 1.2em;
    font-weight: 500;
}
.unauthorized .head {
    font-size: 2em;
    font-weight: 800;
}
.unauthorized .text {
    font-size: 1em;
    color: maroon;
}
</style>
