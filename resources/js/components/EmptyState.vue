<script setup lang="ts">
import type { Component } from 'vue'
import { Inbox } from 'lucide-vue-next'

withDefaults(defineProps<{
  icon?: Component
  title?: string
  description?: string
}>(), {
  icon: () => Inbox,
  title: 'Nothing here yet',
  description: '',
})
</script>

<template>
  <div class="flex flex-col items-center gap-3 py-12 text-muted-foreground">
    <div class="flex size-12 items-center justify-center rounded-xl bg-primary/10">
      <component :is="icon" class="size-6 text-primary" />
    </div>
    <div class="space-y-1 text-center">
      <p class="text-sm font-medium text-foreground">{{ title }}</p>
      <p v-if="description" class="text-sm">{{ description }}</p>
      <slot />
    </div>
  </div>
</template>
