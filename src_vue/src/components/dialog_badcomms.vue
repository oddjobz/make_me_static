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
const message       = "<p>We're having problems communicating with the Crawler service. Please can you make sure that your JSON API is enabled and that nothing in your configuration in re-writing or interfering with query strings.</p>"+
                        "<p>If you need help, please contact us using one of the links below</p>"
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
        header: `Authentication Issue`,
        links: [
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
