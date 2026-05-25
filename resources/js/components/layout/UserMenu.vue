<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { LogOut, Sun, Moon, Monitor, Languages, Building2, Clock, MessageSquare, CalendarDays, Palmtree, Inbox } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { useAppearanceStore } from '@/stores/appearance'
import { useLocaleStore } from '@/stores/locale'
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
import { useI18n } from 'vue-i18n'
import FeedbackDialog from '@/components/feedback/FeedbackDialog.vue'

const router = useRouter()
const authStore = useAuthStore()
const appearanceStore = useAppearanceStore()
const localeStore = useLocaleStore()
const { t } = useI18n()

const feedbackOpen = ref(false)

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
      <DropdownMenuItem as-child>
        <RouterLink :to="{ name: 'time' }" class="flex items-center w-full">
          <Clock class="mr-2 size-4" />
          {{ t('nav.myTime') }}
        </RouterLink>
      </DropdownMenuItem>
      <DropdownMenuItem as-child>
        <RouterLink :to="{ name: 'year' }" class="flex items-center w-full">
          <CalendarDays class="mr-2 size-4" />
          {{ t('nav.yearOverview') }}
        </RouterLink>
      </DropdownMenuItem>
      <DropdownMenuItem as-child>
        <RouterLink :to="{ name: 'leave' }" class="flex items-center w-full">
          <Palmtree class="mr-2 size-4" />
          {{ t('nav.leave') }}
        </RouterLink>
      </DropdownMenuItem>
      <template v-if="authStore.isAdmin || authStore.isSuperAdmin">
        <DropdownMenuItem as-child>
          <RouterLink :to="{ name: 'admin.leave-approvals' }" class="flex items-center w-full">
            <Inbox class="mr-2 size-4" />
            {{ t('nav.leaveApprovals') }}
          </RouterLink>
        </DropdownMenuItem>
        <DropdownMenuItem as-child>
          <RouterLink :to="{ name: 'admin.organisation' }" class="flex items-center w-full">
            <Building2 class="mr-2 size-4" />
            {{ t('nav.organisation') }}
          </RouterLink>
        </DropdownMenuItem>
        <DropdownMenuItem v-if="authStore.isSuperAdmin" as-child>
          <RouterLink :to="{ name: 'admin.organisations' }" class="flex items-center w-full">
            <Building2 class="mr-2 size-4" />
            {{ t('nav.organisations') }}
          </RouterLink>
        </DropdownMenuItem>
      </template>
      <DropdownMenuSeparator />
      <DropdownMenuSub>
        <DropdownMenuSubTrigger>
          <Sun class="mr-2 size-4 dark:hidden" />
          <Moon class="mr-2 size-4 hidden dark:block" />
          {{ t('userMenu.theme') }}
        </DropdownMenuSubTrigger>
        <DropdownMenuSubContent>
          <DropdownMenuItem @click="appearanceStore.theme = 'light'" :class="{ 'bg-accent': appearanceStore.theme === 'light' }">
            <Sun class="mr-2 size-4" />
            {{ t('userMenu.light') }}
          </DropdownMenuItem>
          <DropdownMenuItem @click="appearanceStore.theme = 'dark'" :class="{ 'bg-accent': appearanceStore.theme === 'dark' }">
            <Moon class="mr-2 size-4" />
            {{ t('userMenu.dark') }}
          </DropdownMenuItem>
          <DropdownMenuItem @click="appearanceStore.theme = 'system'" :class="{ 'bg-accent': appearanceStore.theme === 'system' }">
            <Monitor class="mr-2 size-4" />
            {{ t('userMenu.system') }}
          </DropdownMenuItem>
        </DropdownMenuSubContent>
      </DropdownMenuSub>
      <DropdownMenuSub>
        <DropdownMenuSubTrigger>
          <Languages class="mr-2 size-4" />
          {{ t('userMenu.language') }}
        </DropdownMenuSubTrigger>
        <DropdownMenuSubContent>
          <DropdownMenuItem @click="localeStore.current = 'hr'" :class="{ 'bg-accent': localeStore.current === 'hr' }">
            🇭🇷 Hrvatski
          </DropdownMenuItem>
          <DropdownMenuItem @click="localeStore.current = 'en'" :class="{ 'bg-accent': localeStore.current === 'en' }">
            🇬🇧 English
          </DropdownMenuItem>
        </DropdownMenuSubContent>
      </DropdownMenuSub>
      <DropdownMenuSeparator />
      <DropdownMenuItem @click="feedbackOpen = true">
        <MessageSquare class="mr-2 size-4" />
        {{ t('feedback.title') }}
      </DropdownMenuItem>
      <DropdownMenuSeparator />
      <DropdownMenuItem @click="handleLogout">
        <LogOut class="mr-2 size-4" />
        {{ t('userMenu.logout') }}
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>

  <FeedbackDialog v-model:open="feedbackOpen" />
</template>
