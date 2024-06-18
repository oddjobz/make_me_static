<!--
    Terms and Conditions Component
    Present a dialog box and do not continue unless user wants to accept
    terms and conditions. Acceptance is stored on the directory server.    
    If the flag is cleared on the server (maybe because of key changes
    in the ts and cs, this will automatically activate)
-->
<template>
    <div class="full-screen" />
</template>

<script setup>
import { computed, watch, onMounted } from 'vue';
import { useConfirm } from "primevue/useconfirm";
import { useRouteStore } from '@/stores/routeStore.js';
import { useLogger } from './OrbitLogger.js'

const routeStore    = useRouteStore(window.pinia);
const confirm       = useConfirm();
const log           = useLogger()
const message       = '<p>In order to use this software you must agree to our general terms and conditions and terms and conditions specific to this service.'+
                      ' Note that this service is <b>FREE</b> to use <b>UNLESS</b> you specifically upgrade to a paid tier and provide credit card details.</p>'
const props         = defineProps(['answer', 'checked', 'root'])
const emit          = defineEmits(['terms-rejected', 'terms-accepted'])
const checked       = computed(() => props.checked)
const answer        = computed(() => props.answer)
const aclass        = computed(() => "p-button-success" + (checked.value ? '' : ' hidden'))

onMounted(async () => {
    doConfirm ()
})
watch (answer, (curr,prev) => {
    doConfirm ()
})
function doConfirm () {
    log.debug ("** DO CONFIRM **", answer, answer.value)
    if (answer.value) return
    confirm.require({
        group: 'confirmtac',
        message: message,
        header: `Terms and Conditions`,
        links: [
            {link: "https://madpenguin.uk/terms-of-service/", html: "MadPenguin's Terms of Service"},
            {link: "https://live.madpenguin.uk/mms-terms-and-conditions/", html: "Make Me Static Terms of Use"}
        ],
        icon: "pi pi-refresh",
        acceptLabel: "Accept",
        rejectLabel: "Decline",
        class: ['notify-content'],
        acceptClass: aclass,
        accept: () => {
            routeStore.confirm_tacs (props.root, true)
            emit ('terms-accepted')
        },
        reject: () => {
            emit ('terms-rejected')
        }
    });
}
</script>

<style scoped>
.full-screen {
    height:100%;
    width:100%;
}
</style>
