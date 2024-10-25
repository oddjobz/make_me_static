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
const message       = '<p>For some reason this account has been disabled. If you think this is a mistake, please email <a href="mailto:support@madpenguin.uk">support@madpenguin.uk</a> and request the account be enabled.'+
                      '<p>To see details regarding why the account may have been disabled, please review our terms.</p>'
const props         = defineProps(['answer', 'checked', 'root'])
const emit          = defineEmits(['terms-rejected', 'terms-accepted'])
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
        header: `Account Disabled`,
        links: [
            {link: "https://madpenguin.uk/terms-and-conditions/", html: "Make Me Static - Terms and Conditions of Use"}
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
