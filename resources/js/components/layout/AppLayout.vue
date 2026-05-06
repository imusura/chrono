<script setup lang="ts">
import { useRoute } from 'vue-router'
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar'
import { Separator } from '@/components/ui/separator'
import AppSidebar from './AppSidebar.vue'
import UserMenu from './UserMenu.vue'

const route = useRoute()
</script>

<template>
  <SidebarProvider>
    <AppSidebar />

    <SidebarInset class="overflow-hidden">
      <header class="flex h-14 shrink-0 items-center justify-between border-b border-border/60 px-4 bg-card/80 backdrop-blur-sm">
        <div class="flex items-center gap-2">
          <SidebarTrigger />
          <Separator orientation="vertical" class="mr-2 !h-4" />
          <span class="text-sm font-medium text-muted-foreground">
            {{ route.meta.title ?? route.name }}
          </span>
        </div>

        <UserMenu />
      </header>

      <main class="flex-1 min-h-0 overflow-hidden p-4 sm:p-6 flex flex-col">
        <slot />
      </main>
    </SidebarInset>
  </SidebarProvider>
</template>
