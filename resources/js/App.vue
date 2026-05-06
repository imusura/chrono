<script setup lang="ts">
import { RouterView } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { Toaster } from '@/components/ui/sonner'

const authStore = useAuthStore()
</script>

<template>
  <Toaster position="top-right" :duration="5000" rich-colors close-button />
  <template v-if="authStore.isReady">
    <RouterView />
  </template>
  <div v-else class="flex min-h-screen items-center justify-center">
    <div class="text-muted-foreground">Loading...</div>
  </div>
</template>
