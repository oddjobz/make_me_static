<!-- Template for MMS Directory Application -->
<!-- Scoped CSS only, see theme.css and admin.css for globally scoped CSS -->

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
        <div class="spin-wrapper" v-else-if="state==2">
            <TermsAndConditions :checked="ischecked" :root="root" :answer="answer" @terms-rejected="state=3" @terms-accepted="state=1"/>
        </div>
        <div class="unauthorized" v-else-if="state==3">
            <div class="head">N O T &nbsp;&nbsp;&nbsp; A L L O W E D</div>
            <p class="text">
                <div>Sorry, but you must Accept the Terms and Conditions before you can use this Software.</div>
            </p>
        </div>
        <div class="unauthorized" v-else-if="state==4">
            <div class="head">N O T &nbsp;&nbsp;&nbsp; A L L O W E D</div>
            <p class="text">
                <div>Something went wrong authenticating your account or session</div>
                <div>Please try logging out of Wordpress and logging back in, if that</div>
                <div>doesn't work please contact technical support: support@madpenguin.uk</div>
            </p>
        </div>
        <div class="unauthorized" v-else-if="state==5">
            <div class="head">N O T &nbsp;&nbsp;&nbsp; A L L O W E D</div>
            <p class="text">
                <div>Something went wrong authenticating your account or session</div>
                <div>It looks like the code is trying to load a module from an unauthorized location</div>
            </p>
        </div>
        <div class="unauthorized" v-else-if="state!=0">
            <div class="head">N O T &nbsp;&nbsp;&nbsp; A L L O W E D</div>
            <p class="text">
                <div>Something went wrong authenticating your account or session</div>
                <div>This looks like a software bug, please report code: {{ state }} to support@madpenguin.uk</div>
            </p>
        </div>
        <div class="main-display" v-show="state==0">
            <div id="make-me-static-crawler" :style="app_style" />
        </div>
    </section>
</template>

<script setup>
import ProgressSpinner from 'primevue/progressspinner';
import { defineComponent, ref, watch, computed, inject, onMounted, toRaw, readonly } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useLogger } from './OrbitLogger.js'
import TermsAndConditions from "@/components/termsandconditions.vue";
import ConfirmDialog from 'primevue/confirmdialog';
import Checkbox from 'primevue/checkbox';
import pkg from '../../package.json';
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
const active        = computed(() => socket.value.connected)
const authenticated = computed(() => connection.authenticated)
//
//  Application Specific
//
const ischecked     = computed(() => checked.value)
const answer        = computed(() => !route||!route.value ? null : route.value.answer)
const have_app      = ref(false)
const have_route    = ref(false)
const unauthorized  = ref(false)
const checked       = ref(false)
const root          = computed(() => {
    return opt && opt.router ? opt.router.currentRoute.value.meta.root : null
})
const app_style     = computed(() => have_app.value ? "height:100%;width:100%" : "height:0;width:0")
const auth1         = computed(() => connection.authenticated)
const auth2         = ref(false)
const state         = ref(1)
const loaded        = ref(false)
const apiurl        = ref(window.MMS_API_Settings.apiurl)
const nonce         = ref(null)
const log           = useLogger()
const crawler_app   = ref(null)
//
//  Wait for the Vue Router to come ready
//
onMounted(async () => {
    log.debug ('Loading MMS DIR service: ', pkg.version)
    nonce.value = window.MMS_API_Settings.nonce
    await vrouter.isReady()
    log.warn('Authenticated=', auth1.value)
    log.warn('Auth2=', auth2.value)
    log.warn('State=', state.value)
    log.warn('Active=', active.value)
    if (auth1.value) registerWithWordpress()
})
//
//  The value of "route" here is reactive and comes from the directory server. However
//  we need a read-only copy inside the crawler APP which is connected to a different back-end.
//  To enable this, whenever the reactive value of route changes, emit it to the crawler
//  app to emulate direct reactivity.
//
const emitRoute = () => {
    window.dispatchEvent(new CustomEvent('MMS_NEW_ROUTE', {detail: route.value}))
}
//
//  watch auth1 - this happens when we make a new connection to the directory server
//  so we need to register the connection with Wordpress. This is a form of Nonce which
//  doesn't expose anything that's not already public.
//
watch (auth1, () => {
    log.warn ('Event: Auth1')
    registerWithWordpress()
})
//  watch auth2 - this happens when we make a virtual connection to the directory application
//  and means we're able to load data from the service, i.e. our route and license info. So,
//  do just that, grab our route object.
//
watch (auth2, () => {
    log.warn ('Event: Auth2')
    loadRoute()
})

watch (socket, () => {
    log.warn ('Event: Socket')
})

watch (vroute, (curr) => {
    log.error ('Route Change: ', curr)
    if (curr.path == '/') {
        log.info ('Loading MMSDIR Module')
        onLoadModule()
    }
})

//
//  watch route - this will tell us which MMS crawler service to connect to. This may change
//  dynamically if a license changes or a server is under excessive load. License changes are UI
//  reactivel, but a crawler change required loading the UI module for that specific crawler, as
//  crawlers may deploy different versions of the software.
//
watch (route, (curr, prev) => {
    if (curr && (!prev || (curr.url != prev.url))) {
        //
        //  If we've accepted the terms and conditions, then load the crawler
        //  otherwise, go to the unauthorized page
        //
        if (route.value.answer) loadCrawler (); else state.value = 2
    }
    //
    //  Pass the route object on to the VUE loaded crawler
    //
    emitRoute()
})

function onLoadModule () {
    //
    //  This is what kicks off a load event when we come to the crawler page
    //
    log.debug ('onLoadModule')
    if (!active.value) return log.debug ('not Active')           // socket is not active yet
    if (!authenticated.value) return log.debug ('not Auth')     // we're not authenticated yet
    log.debug ('Socket is: ', socket.value)
    registerWithWordpress ()
}


//
//  Register our host_id with Wordpress so the MMS service can validate
//  this host_id is allowed to scan the site.
//
function registerWithWordpress () {
    if (loaded.value||!auth1.value) return
    const url = new URL(apiurl.value + 'make_me_static/v1/register_host');
    url.searchParams.set('host_id', connection.hostid);
    url.searchParams.set('site', window.MMS_API_Settings.uuid);
    //
    //  We're passing our own UUID (essentially a password) together with our own
    //  host_id (which is our PKI secured token) to the WP JSON API. (secured by a Nonce)
    //
    fetch(url.href, {method: 'GET', headers: {'X-WP-Nonce': nonce.value}})
    .then (async (response) => {
        switch (response.status) {
            case 200:
                //
                //  We're good, make the APP connection and flag "auth2"
                //
                window.MMS_API_Settings.host_id = connection.hostid
                let app = await plugin (opt, namespace, socket);
                if (socket.value.connected) loadRoute ()
                else app.events.authenticated = () => auth2.value = true
                break;
            default:
                log.error('Access Denied trying to register with Wordpress => ', response.status)
                state.value = 4;
        }
    })
    .catch ((error) => {
        log.error(error)
    })
}
//
//  loadRoute - attempt to populate the routeStore cache, assuming this works
//  tell the workflow we've completed this stage. This connection is authenticated
//  so if it fails, we're not allowed to do this.
//
function loadRoute () {
    if (!route_ids.value.length) {
        let store = routeStore.init(app, root.value, socket.value)
        store.populate(root.value, (response) => {
            if (!response || !response.ok || !route_ids.value.length) {
                log.error ("failed to populate routeStore", response)
                unauthorized.value = true
                return
            }
            have_route.value = true
        })
    } else {
        loadCrawler()
    }
}
//
//  loadCrawler - load up the current version of the crawler UI from the
//  crawler associated with the user's license. This allows the account
//  to be upgraded, or switched between crawlers in real-time. (and allows
//  for different crawlers running different versions of the software)
//

function loadCrawler () {
    //
    //  This will hold a reference to our SCRIPT tag
    //
    let script
    //
    //  Set the spinner in motion
    //
    state.value = 1
    if (!window.make_me_static_crawlers) window.make_me_static_crawlers = new Map()
    //
    //  If crawler_app is set, we already have a crawler UI on the screen
    //  so we need to clean it up.
    //
    if (crawler_app.value) {
        //
        //  Unmount the Vue Application
        //
        crawler_app.value.unmount()
        //
        //  Remove the SCRIPT tag
        //
        script = document.getElementById('make-me-static-crawler-app')
        if (script != null) script.parentNode.removeChild( script )
    }
    //
    if (!route.value.url.endsWith('madpenguin.uk')) {
        state.value = 5
        log.error ('blocked load from: ', route.value.url)
        return
    }
    window.MMS_API_Settings.crawler = route.value.url
    //
    //  Generate a URL to load, if we're on a development server then we'll be loading
    //  in DEV mode, otherwise it will be a minified asset.
    //
    const url = new URL(route.value.url);

    log.error("Settings>", window.MMS_API_Settings)


    url.pathname = window.MMS_API_Settings.crawler == "https://mms-crawler-dev.madpenguin.uk" ? 'src/main.js' : 'assets/index.js'
    //
    //  This is our (very simple but effective) loader
    //
    script = document.createElement('script');
    script.addEventListener('load', () => {
        //
        //  Each crawler populates "mms_crawlers[url]" with a factory that can be used
        //  to generate new instances of the UI.
        //
        const factory = window.make_me_static_crawlers.get(MMS_API_Settings.crawler)
        //
        //  Create a new instance of our VUE.js application
        //
        crawler_app.value = factory.create()
        //
        //  Mount it inside the current window
        //
        crawler_app.value.mount('#make-me-static-crawler')
        have_app.value = true;
        //
        //  Cleat the spinner
        //
        state.value = 0;
        //
        //  Pass the current "route" info to the APP
        //
        setTimeout(() => emitRoute(), 500)
    });
    //
    //  This could happen ...
    //
    script.addEventListener('error', () => {
        log.error('failed to load:', url)
    });
    //
    //  Make sure our SCRIPT tag has the right attributes
    //
    script.type="module"
    script.id = "mms-crawler-app";
    script.src = url
    //
    //  Add our new TAG containing the mounted APP to the DOM
    //
    document.body.appendChild(script);
}
</script>

<script>
//
//  This is mostly Orbit boilerplate for loading an app.
//
import { defineStore } from 'pinia';
import { OrbitComponentMixin } from '@/../node_modules/orbit-component-base';

const namespace = 'mmsdir'
const mixin = OrbitComponentMixin(namespace, defineStore)
const opt = ref(null)
const app = ref(null)

export default defineComponent({
    name: namespace,
    install (vue, options) {
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
.main-display {
    width:100%;
    height: 100%;
}
.spin-wrapper {
    height: calc(100vh - 64px);
    width:100%;
}
.spinner {
    text-align: center;
    position: absolute;
    top: 40%;
    min-height: 300px;
    width:99%;
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
