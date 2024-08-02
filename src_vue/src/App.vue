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
import { useRouter } from 'vue-router';
import { useLogger } from '@/components/OrbitLogger.js';
//
const connection    = inject('$connection')
//
//
//
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
    await vrouter.isReady()
    window.addEventListener('MMS_CHANGE_PATH', (payload) => {
        vrouter.push({path: payload.detail})
    })
})
watch (route, (curr, prev) => {
    window.dispatchEvent(new CustomEvent('MMS_NEW_ROUTE', {detail: route.value}))
})
</script>

<script>
export default defineComponent({

})
</script>


