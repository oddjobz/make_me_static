<!-- 
  More Orbit boilerplate. (this is created by orbit-cli)
  Orbit-i-o Makes an initial connection for authentication
  Orbit-dir loads up our App
 -->
<template>
  <orbit-i-o />
  <orbit-dir />
</template>

<script>
import { defineComponent } from 'vue';
import { OrbitIO } from 'orbit-component-base';
import OrbitDir from '@/components/mmsdir.vue'

export default defineComponent({
  components: {OrbitIO, OrbitDir}
})
</script>