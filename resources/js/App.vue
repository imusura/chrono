<script setup lang="ts">
import { RouterView } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { Toaster } from '@/components/ui/sonner'

const authStore = useAuthStore()
</script>

<template>
  <Toaster position="top-right" :duration="5000" rich-colors close-button />
  <RouterView v-if="authStore.isReady" />
  <div v-else class="flex min-h-screen items-center justify-center">
    <div class="text-muted-foreground">Loading...</div>
  </div>
</template>
