<script setup lang="ts">
import UserMenu from './UserMenu.vue'

defineProps<{ fullBleed?: boolean }>()
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <header class="h-14 shrink-0 border-b bg-card/80 backdrop-blur-sm flex items-center px-6">
      <RouterLink :to="{ name: 'time' }" class="font-semibold text-sm">Chrono</RouterLink>
      <div class="ml-auto">
        <UserMenu />
      </div>
    </header>

    <main :class="fullBleed ? 'flex-1 min-h-0 flex flex-col overflow-hidden' : 'flex-1 min-h-0 overflow-auto p-6'">
      <slot />
    </main>
  </div>
</template>
