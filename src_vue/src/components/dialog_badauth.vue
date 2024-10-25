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
const message       = '<p>Unfortunately we have failed to load the User Interface from your designated Crawler. This is likely either to be an issue with the crawler in question, or your connection to the crawler.</p>'+
                        '<p>It is a transient error, please wait a few minutes and try again. If this issue does not resolve itself, please report the issue to us via one of the links below.</p>'
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
        header: `Communications Issue`,
        links: [
            {link: "https://makemestatic.com/service-status", html: "The MakeMeStatic service Status page"},
            {link: "mailto:support@madpenguin.uk"           , html: "Email our Support department"},
            {link: "https://support.madpenguin.uk"          , html: "Via our Support Forums"}
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
