<script setup lang="ts">
import { useRouter } from 'vue-router'
import { LogOut, Sun, Moon, Monitor } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { useAppearanceStore } from '@/stores/appearance'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuSub,
  DropdownMenuSubContent,
  DropdownMenuSubTrigger,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'

const router = useRouter()
const authStore = useAuthStore()
const appearanceStore = useAppearanceStore()

const handleLogout = async () => {
  await authStore.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <Button variant="ghost" size="sm" class="gap-2">
        <div class="flex size-7 items-center justify-center rounded-full bg-primary/10 text-primary text-xs font-semibold">
          {{ authStore.user?.name?.charAt(0)?.toUpperCase() }}
        </div>
        <span class="hidden sm:inline text-sm">{{ authStore.user?.name }}</span>
      </Button>
    </DropdownMenuTrigger>
    <DropdownMenuContent align="end" class="w-48">
      <DropdownMenuLabel>
        <div class="flex flex-col">
          <span>{{ authStore.user?.name }}</span>
          <span class="text-xs font-normal text-muted-foreground">
            {{ authStore.user?.email }}
          </span>
        </div>
      </DropdownMenuLabel>
      <DropdownMenuSeparator />
      <DropdownMenuSub>
        <DropdownMenuSubTrigger>
          <Sun class="mr-2 size-4 dark:hidden" />
          <Moon class="mr-2 size-4 hidden dark:block" />
          Theme
        </DropdownMenuSubTrigger>
        <DropdownMenuSubContent>
          <DropdownMenuItem @click="appearanceStore.theme = 'light'" :class="{ 'bg-accent': appearanceStore.theme === 'light' }">
            <Sun class="mr-2 size-4" />
            Light
          </DropdownMenuItem>
          <DropdownMenuItem @click="appearanceStore.theme = 'dark'" :class="{ 'bg-accent': appearanceStore.theme === 'dark' }">
            <Moon class="mr-2 size-4" />
            Dark
          </DropdownMenuItem>
          <DropdownMenuItem @click="appearanceStore.theme = 'system'" :class="{ 'bg-accent': appearanceStore.theme === 'system' }">
            <Monitor class="mr-2 size-4" />
            System
          </DropdownMenuItem>
        </DropdownMenuSubContent>
      </DropdownMenuSub>
      <DropdownMenuSeparator />
      <DropdownMenuItem @click="handleLogout">
        <LogOut class="mr-2 size-4" />
        Log out
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>
