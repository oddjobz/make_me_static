<!--
    Option to change UUID if URL has changed
-->
<template>
    <div class="full-screen" />
</template>

<script setup>
import { computed, watch, onMounted } from 'vue';
import { useConfirm } from "primevue/useconfirm";
import { useRouteStore } from '@/stores/routeStore.js';
import { useLogger } from './OrbitLogger.js'
import ProgressSpinner from 'primevue/progressspinner';

const routeStore    = useRouteStore();
const confirm       = useConfirm();
const log           = useLogger()
const props         = defineProps(['root', 'error'])
const emit          = defineEmits(['regenerate'])
const message       = "<p>Your <b>makemestatic</b> account is tagged to your site URL and it looks like your site URL has changed. "+
                      "In order to continue you will either need to restore your old URL, or click below to generate a new <b>makemestatic</b> UUID.<p>"+
                      "<p>If you have a paid subscription, once you have created a new UUID, please email your URL to <b>support@makemestatic.com</b> to transfer the subscription to the new UUID.</p>"+
                      "<p>For more informtation:</p>"
const rclass        = computed(() => "p-button-success hidden")

onMounted(async () => {
    doConfirm ()
})
function doConfirm () {
    confirm.require({
        group: 'confirmtac',
        message: message,
        header: `Site URL has Changed`,
        links: [
            {link: "mailto:support@madpenguin.uk"           , html: "Email our Support department"},
            {link: "https://support.madpenguin.uk"          , html: "Via our Support Forums"}
        ],
        icon: "pi pi-refresh",
        acceptLabel: "Generate new UUID",
        accept: () => {
            emit ('regenerate')
        },
        class: ['notify-content'],
        rejectClass: rclass
    });
}
</script>

<style scoped>
.full-screen {
    height:100%;
    width:100%;
}
</style>
