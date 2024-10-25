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
const message       = '<p>It looks like your permalink structure is set to <b>plain</b> or <b>dynamic</b>, which does not work well with static sites or with search engines.</p>'+
                        '<p>To resolve this issue, please select one of the following links;</p>'
const props         = defineProps(['root'])
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
    confirm.require({
        group: 'confirmtac',
        message: message,
        header: `Permalinks not Configured`,
        links: [
            {link: "/wp-admin/options-permalink.php"        , html: "Select a Permalink structure, not 'plain' or '/index.php'"},
            {link: "mailto:support@madpenguin.uk"           , html: "Email our Support department"},
            {link: "https://support.madpenguin.uk"          , html: "Get Help via our Support Forums"}
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
