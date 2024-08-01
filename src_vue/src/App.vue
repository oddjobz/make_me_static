<!-- 
  More Orbit boilerplate. (this is created by orbit-cli)
  Orbit-i-o Makes an initial connection for authentication
  Orbit-dir loads up our App
 -->
<template>
  <orbit-i-o />
    <router-view v-slot="{ Component }">
        <keep-alive>
            <component :is="Component" :key="$route.fullPath"/>
        </keep-alive>
    </router-view>
</template>

<script setup>
import { defineComponent, onMounted, ref, computed, inject, watch } from 'vue';
import { RouterView } from 'vue-router';
import { OrbitIO } from 'orbit-component-base';
import { useRoute, useRouter } from 'vue-router';
import { useLogger } from '@/components/OrbitLogger.js';
import pkg from '../package.json';
//
const connection    = inject('$connection')
const authenticated = computed(() => connection.authenticated)
//
//
//
const vroute        = useRoute()
const vrouter       = useRouter()
const log           = useLogger()
const root          = computed(() => vrouter.currentRoute.value.meta.root)
//
//  RouteStore
//
import { useRouteStore } from '@/stores/routeStore.js';
const routeStore    = useRouteStore(window.pinia);
const route_data    = computed(() => routeStore.data)
const route_ids     = computed(() => routeStore.ids(root.value))
const route         = computed(() => route_ids.value.length ? route_data.value.get(route_ids.value[0]) : null)

onMounted(async () => {
    log.debug ('Loading directory service version ', pkg.version)
    await vrouter.isReady()
    console.log (pkg)
    // log.error ('Path>', vrouter.currentRoute.value.fullPath)
    // if (vrouter.currentRoute.value.fullPath == '/stripe') vrouter.push('/')
    window.addEventListener('MMS_CHANGE_PATH', (payload) => {
        log.warn ('Request to change path: ', payload)
        vrouter.push({path: payload.detail})
    })
})
watch (route, (curr, prev) => {
    log.info ('Reporting Route Change')
    window.dispatchEvent(new CustomEvent('MMS_NEW_ROUTE', {detail: route.value}))
})

</script>


<script>
export default defineComponent({

})
</script>


