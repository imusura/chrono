<script setup lang="ts">
import { computed, ref } from 'vue'
import AppLayout from '@/components/layout/AppLayout.vue'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Label } from '@/components/ui/label'
import RolesTab from '@/components/admin/RolesTab.vue'
import ActivitiesTab from '@/components/admin/ActivitiesTab.vue'
import UsersTab from '@/components/admin/UsersTab.vue'
import CalendarTab from '@/components/admin/CalendarTab.vue'
import { useAuthStore } from '@/stores/auth'
import { useOrganisations } from '@/composables/useOrganisations'
import { useAdminRoles } from '@/composables/useAdminRoles'
import { useAdminActivities } from '@/composables/useAdminActivities'
import { useAdminUsers } from '@/composables/useAdminUsers'
import { CheckCircle2, Circle } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'

const auth = useAuthStore()
const { t } = useI18n()
const isSuperAdmin = computed(() => auth.isSuperAdmin)

const { query: orgsQuery } = useOrganisations()
const selectedOrgId = ref<number | undefined>(undefined)

const orgId = computed(() =>
  isSuperAdmin.value ? selectedOrgId.value : auth.user?.organisation_id ?? undefined,
)

const { query: rolesQuery } = useAdminRoles(orgId)
const { query: activitiesQuery } = useAdminActivities(orgId)
const { query: usersQuery } = useAdminUsers(orgId)

const hasRoles = computed(() => (rolesQuery.data.value?.length ?? 0) > 0)
const hasActivities = computed(() => (activitiesQuery.data.value?.length ?? 0) > 0)
const hasUsers = computed(() => (usersQuery.data.value?.length ?? 0) > 0)
const showChecklist = computed(() => !hasRoles.value || !hasActivities.value || !hasUsers.value)

const activeTab = ref('users')
</script>

<template>
  <AppLayout>
    <div class="max-w-5xl mx-auto">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold">{{ t('organisation.title') }}</h1>

        <div v-if="isSuperAdmin" class="flex items-center gap-2">
          <Label class="text-sm text-muted-foreground shrink-0">{{ t('organisation.viewing') }}</Label>
          <Select
            :model-value="selectedOrgId ? String(selectedOrgId) : undefined"
            @update:model-value="selectedOrgId = $event ? Number($event) : undefined"
          >
            <SelectTrigger class="w-48">
              <SelectValue :placeholder="t('organisation.pickOrg')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem
                v-for="org in orgsQuery.data.value"
                :key="org.id"
                :value="String(org.id)"
              >
                {{ org.name }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      <div v-if="isSuperAdmin && !selectedOrgId" class="rounded-lg border border-dashed px-4 py-12 text-center text-sm text-muted-foreground">
        {{ t('organisation.selectOrgPrompt') }}
      </div>

      <template v-else>
        <div v-if="showChecklist" class="mb-6 rounded-lg border bg-muted/40 px-4 py-4">
          <p class="text-sm font-medium mb-3">{{ t('organisation.checklist.intro') }}</p>
          <ol class="space-y-2">
            <li
              class="flex items-center gap-2 text-sm cursor-pointer"
              :class="hasRoles ? 'text-muted-foreground line-through' : 'font-medium'"
              @click="activeTab = 'roles'"
            >
              <CheckCircle2 v-if="hasRoles" class="h-4 w-4 text-green-500 shrink-0" />
              <Circle v-else class="h-4 w-4 shrink-0" />
              {{ t('organisation.checklist.createRoles') }}
            </li>
            <li
              class="flex items-center gap-2 text-sm cursor-pointer"
              :class="hasActivities ? 'text-muted-foreground line-through' : hasRoles ? 'font-medium' : 'text-muted-foreground'"
              @click="hasRoles && (activeTab = 'activities')"
            >
              <CheckCircle2 v-if="hasActivities" class="h-4 w-4 text-green-500 shrink-0" />
              <Circle v-else class="h-4 w-4 shrink-0" />
              {{ t('organisation.checklist.createActivities') }}
            </li>
            <li
              class="flex items-center gap-2 text-sm cursor-pointer"
              :class="hasUsers ? 'text-muted-foreground line-through' : hasRoles && hasActivities ? 'font-medium' : 'text-muted-foreground'"
              @click="hasRoles && hasActivities && (activeTab = 'users')"
            >
              <CheckCircle2 v-if="hasUsers" class="h-4 w-4 text-green-500 shrink-0" />
              <Circle v-else class="h-4 w-4 shrink-0" />
              {{ t('organisation.checklist.addUsers') }}
            </li>
          </ol>
        </div>

        <Tabs v-model="activeTab">
          <TabsList class="mb-4">
            <TabsTrigger value="users" class="gap-2">
              {{ t('organisation.tabs.users') }}
              <span v-if="usersQuery.data.value?.length" class="rounded-full bg-muted px-1.5 py-0.5 text-xs font-medium tabular-nums leading-none">
                {{ usersQuery.data.value.length }}
              </span>
            </TabsTrigger>
            <TabsTrigger value="roles" class="gap-2">
              {{ t('organisation.tabs.roles') }}
              <span v-if="rolesQuery.data.value?.length" class="rounded-full bg-muted px-1.5 py-0.5 text-xs font-medium tabular-nums leading-none">
                {{ rolesQuery.data.value.length }}
              </span>
            </TabsTrigger>
            <TabsTrigger value="activities" class="gap-2">
              {{ t('organisation.tabs.activities') }}
              <span v-if="activitiesQuery.data.value?.length" class="rounded-full bg-muted px-1.5 py-0.5 text-xs font-medium tabular-nums leading-none">
                {{ activitiesQuery.data.value.length }}
              </span>
            </TabsTrigger>
            <TabsTrigger value="calendar">{{ t('organisation.tabs.calendar') }}</TabsTrigger>
          </TabsList>

          <TabsContent value="users">
            <UsersTab :organisation-id="orgId" />
          </TabsContent>

          <TabsContent value="roles">
            <RolesTab :organisation-id="orgId" />
          </TabsContent>

          <TabsContent value="activities">
            <ActivitiesTab :organisation-id="orgId" />
          </TabsContent>

          <TabsContent value="calendar">
            <CalendarTab :organisation-id="orgId" />
          </TabsContent>
        </Tabs>
      </template>
    </div>
  </AppLayout>
</template>
