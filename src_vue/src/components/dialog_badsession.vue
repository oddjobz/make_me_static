<!--
    Account Disabled Component
    Give the user some bad news ...
-->
<template>
    <div class="full-screen" />
</template>

<script setup>
import { computed, watch, onMounted } from 'vue';
import { useConfirm } from "primevue/useconfirm";
import { useRouteStore } from '@/stores/routeStore.js';
import { useLogger } from './OrbitLogger.js'

const routeStore    = useRouteStore();
const confirm       = useConfirm();
const log           = useLogger()
const message       = '<p>Sorry, but it looks like there is a problem with the current session. We rely on a Wordpress Session being active and the session we were using has expired.</p>'+
                        '<p>Please try reloading the page, or simply visit the Wordpress Dashboard and come back to re-initialise a new session</p>'
const props         = defineProps(['root'])
const checked       = computed(() => props.checked)
const answer        = computed(() => props.answer)
const aclass        = computed(() => "p-button-success" + (checked.value ? '' : ' hidden'))

// Convert this to be an error box

onMounted(async () => {
    doConfirm ()
})
watch (answer, (curr,prev) => {
    doConfirm ()
})
function doConfirm () {
    confirm.require({
        group: 'confirmtac',
        message: message,
        header: `Session Expired`,
        links: [
            // {link: "https://madpenguin.uk/terms-and-conditions/", html: "Make Me Static - Terms and Conditions of Use"}
        ],
        icon: "pi pi-refresh",
        rejectLabel: "I Understand",
        class: ['notify-content'],
        acceptClass: aclass
    });
}
</script>

<style scoped>
.full-screen {
    height:100%;
    width:100%;
}
</style>
