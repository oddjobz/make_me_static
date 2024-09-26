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
const message       = '<p>It looks like Wordpress is running over HTTP rather than HTTPS, unfortunatley this Plugin can only work over HTTPS connecitons.</p>'+
                        '<p>If your site is running behind a Proxy or VPN, please make sure your "site address" reflects your public HTTPS address</p>'
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
        header: `Wordpress Configuration Issue`,
        links: [
            {link: "wp-admin/options-general.php", html: "Check your WordPress Address (URL)"},
            {link: "", html: "Check your URL Address is public"},
            {link: "", html: "Verify you're not just running locally"}
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
