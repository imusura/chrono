<script setup lang="ts">
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useI18n } from 'vue-i18n'
import UserMenu from './UserMenu.vue'

defineProps<{ fullBleed?: boolean }>()

const route = useRoute()
const auth = useAuthStore()
const { t } = useI18n()
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <header class="h-14 shrink-0 border-b bg-card/80 backdrop-blur-sm flex items-center px-6 gap-8">
      <span class="font-semibold text-sm">Chrono</span>

      <nav class="flex items-center gap-1">
        <RouterLink
          :to="{ name: 'time' }"
          class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors"
          :class="route.name === 'time' ? 'bg-accent text-accent-foreground' : 'text-muted-foreground hover:text-foreground hover:bg-accent/50'"
        >
          {{ t('nav.myTime') }}
        </RouterLink>

        <RouterLink
          v-if="auth.isAdmin || auth.isSuperAdmin"
          :to="{ name: 'admin.organisation' }"
          class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors"
          :class="route.name === 'admin.organisation' ? 'bg-accent text-accent-foreground' : 'text-muted-foreground hover:text-foreground hover:bg-accent/50'"
        >
          {{ t('nav.organisation') }}
        </RouterLink>

        <RouterLink
          v-if="auth.isSuperAdmin"
          :to="{ name: 'admin.organisations' }"
          class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors"
          :class="route.name === 'admin.organisations' ? 'bg-accent text-accent-foreground' : 'text-muted-foreground hover:text-foreground hover:bg-accent/50'"
        >
          {{ t('nav.organisations') }}
        </RouterLink>
      </nav>

      <div class="ml-auto">
        <UserMenu />
      </div>
    </header>

    <main :class="fullBleed ? 'flex-1 min-h-0 flex flex-col overflow-hidden' : 'flex-1 min-h-0 overflow-auto p-6'">
      <slot />
    </main>
  </div>
</template>
