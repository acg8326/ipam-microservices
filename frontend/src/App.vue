<script setup lang="ts">
import { onMounted } from 'vue'
import { useAuthStore } from '@/stores'
import ToastContainer from '@/components/ToastContainer.vue'

const authStore = useAuthStore()

onMounted(() => {
  authStore.initialize()
})
</script>

<template>
  <router-view />
  <ToastContainer />
</template>

<style>
/* Global styles handled in style.css */
</style>
