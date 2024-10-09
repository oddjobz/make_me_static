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

const routeStore    = useRouteStore();
const confirm       = useConfirm();
const log           = useLogger()
const terms         = '<p>In order to use this software you must agree to our Terms and Conditions of use for the Make Me Static Service</p>'+
                      '<p>Note that this service is <b>FREE</b> to use unless you <b>specifically</b> upgrade to a paid tier and provide credit card details.</p>'
const playground    =   '<p>This Demo WordPress instance is running <b>inside</b> your browser hence is not visible on the Internet. '+
                        'As a result we will scan the <b>Make Me Static</b> WordPress instance. This will make a <b>real</b> static copy of the site which will respond if you assign a <b>real</b> domain name to it.</p>'+
                        '<p>Demo Terms - <b>please do not try to abuse the demo</b></p>'

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
    let links = location.hostname == 'playground.wordpress.net' ? [] : [{link: "https://madpenguin.uk/terms-and-conditions/", html: "Make Me Static - Terms and Conditions of Use"}]
    confirm.require({
        group: 'confirmtac',
        message: location.hostname == 'playground.wordpress.net' ? playground : terms,
        header: `Terms and Conditions`,
        links: links,
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
