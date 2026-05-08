<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Skeleton } from '@/components/ui/skeleton'
import { Switch } from '@/components/ui/switch'
import { Checkbox } from '@/components/ui/checkbox'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog'
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import { Pencil, Trash2, Plus, Mail, Search, ShieldCheck, Clock } from 'lucide-vue-next'
import { useAdminUsers } from '@/composables/useAdminUsers'
import { useAdminRoles } from '@/composables/useAdminRoles'
import { useAdminInvitations } from '@/composables/useAdminInvitations'
import InviteDialog from '@/components/admin/InviteDialog.vue'
import { decimalHoursToHm, hmToDecimalHours } from '@/lib/format'
import type { AdminUser, PendingInvitation } from '@/types'
import { useI18n } from 'vue-i18n'

type UserRow = { kind: 'user'; data: AdminUser }
type InviteRow = { kind: 'invite'; data: PendingInvitation }

const props = defineProps<{
  organisationId?: number
}>()

const { t } = useI18n()
const orgIdRef = computed(() => props.organisationId)
const { query, storeMutation, updateMutation, destroyMutation } = useAdminUsers(orgIdRef)
const { query: rolesQuery } = useAdminRoles(orgIdRef)
const { query: invitationsQuery, destroyMutation: revokeInvitation } = useAdminInvitations(orgIdRef)

const dialogOpen = ref(false)
const editing = ref<AdminUser | null>(null)
const name = ref('')
const email = ref('')
const password = ref('')
const contractedHoursHm = ref('8:00')
const isAdmin = ref(false)
const selectedRoleIds = ref<number[]>([])
const errors = ref<Record<string, string>>({})

const deleteDialogOpen = ref(false)
const deleting = ref<AdminUser | null>(null)
const inviteDialogOpen = ref(false)

const openCreate = () => {
  editing.value = null
  name.value = ''
  email.value = ''
  password.value = ''
  contractedHoursHm.value = '8:00'
  isAdmin.value = false
  selectedRoleIds.value = []
  errors.value = {}
  dialogOpen.value = true
}

const openEdit = (user: AdminUser) => {
  editing.value = user
  name.value = user.name
  email.value = user.email
  password.value = ''
  contractedHoursHm.value = decimalHoursToHm(user.contracted_hours)
  isAdmin.value = user.is_admin
  selectedRoleIds.value = [...user.role_ids]
  errors.value = {}
  dialogOpen.value = true
}

const openDelete = (user: AdminUser) => {
  deleting.value = user
  deleteDialogOpen.value = true
}

watch(dialogOpen, (open) => {
  if (!open) errors.value = {}
})

const submit = async () => {
  errors.value = {}
  const contracted_hours = hmToDecimalHours(contractedHoursHm.value)
  try {
    if (editing.value) {
      await updateMutation.mutateAsync({
        id: editing.value.id,
        payload: {
          name: name.value,
          email: email.value,
          password: password.value || null,
          contracted_hours,
          is_admin: isAdmin.value,
          role_ids: selectedRoleIds.value,
        },
      })
    } else {
      await storeMutation.mutateAsync({
        name: name.value,
        email: email.value,
        password: password.value,
        contracted_hours,
        is_admin: isAdmin.value,
        role_ids: selectedRoleIds.value,
        ...(props.organisationId ? { organisation_id: props.organisationId } : {}),
      })
    }
    dialogOpen.value = false
  } catch (e: unknown) {
    const err = e as { errors?: Record<string, string[]> }
    if (err?.errors) {
      Object.entries(err.errors).forEach(([k, v]) => {
        errors.value[k] = v[0]
      })
    }
  }
}

const confirmDelete = async () => {
  if (!deleting.value) return
  await destroyMutation.mutateAsync(deleting.value.id)
  deleteDialogOpen.value = false
  deleting.value = null
}

const roleName = (id: number) => rolesQuery.data.value?.find((r) => r.id === id)?.name ?? ''
const roleColor = (id: number) => rolesQuery.data.value?.find((r) => r.id === id)?.color ?? '#888'

const search = ref('')

const rows = computed<(UserRow | InviteRow)[]>(() => {
  const q = search.value.trim().toLowerCase()
  const users: UserRow[] = (query.data.value ?? [])
    .filter((u) => !q || u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q))
    .map((u) => ({ kind: 'user', data: u }))
  const invites: InviteRow[] = (invitationsQuery.data.value ?? [])
    .filter((i) => !q || i.email.toLowerCase().includes(q))
    .map((i) => ({ kind: 'invite', data: i }))
  return [...users, ...invites].sort((a, b) => {
    const nameA = a.kind === 'user' ? a.data.name : a.data.email
    const nameB = b.kind === 'user' ? b.data.name : b.data.email
    return nameA.localeCompare(nameB)
  })
})

const hasData = computed(() => (query.data.value?.length ?? 0) + (invitationsQuery.data.value?.length ?? 0) > 0)
const isLoading = computed(() => query.isLoading.value || invitationsQuery.isLoading.value)
</script>

<template>
  <div>
    <div class="flex items-center gap-3 mb-4">
      <p class="text-sm text-muted-foreground shrink-0">{{ t('users.description') }}</p>
      <div class="relative flex-1">
        <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-muted-foreground pointer-events-none" />
        <Input v-model="search" class="pl-8 h-8 text-sm" :placeholder="t('users.search')" />
      </div>
      <div class="flex gap-2 shrink-0">
        <Button size="sm" variant="outline" @click="inviteDialogOpen = true">
          <Mail class="h-4 w-4 mr-1" />
          {{ t('users.invite') }}
        </Button>
        <Button size="sm" @click="openCreate">
          <Plus class="h-4 w-4 mr-1" />
          {{ t('users.newUser') }}
        </Button>
      </div>
    </div>

    <div v-if="isLoading" class="space-y-2">
      <Skeleton v-for="i in 3" :key="i" class="h-10 w-full rounded-lg" />
    </div>

    <div v-else-if="!hasData" class="rounded-lg border border-dashed px-4 py-10 text-center">
      <p class="text-sm font-medium mb-1">{{ t('users.noUsers') }}</p>
      <p class="text-xs text-muted-foreground mb-4">{{ t('users.noUsersHint') }}</p>
      <Button size="sm" @click="openCreate">
        <Plus class="h-4 w-4 mr-1" />
        {{ t('users.addFirst') }}
      </Button>
    </div>

    <div v-else-if="!rows.length" class="rounded-lg border border-dashed px-4 py-8 text-center text-sm text-muted-foreground">
      {{ t('users.noResults') }}
    </div>

    <div v-else class="rounded-lg border overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b bg-muted/40">
            <th class="px-4 py-2.5 text-left font-medium text-muted-foreground">{{ t('users.table.name') }}</th>
            <th class="px-4 py-2.5 text-left font-medium text-muted-foreground">{{ t('users.table.role') }}</th>
            <th class="px-4 py-2.5 text-left font-medium text-muted-foreground w-24">{{ t('users.table.hours') }}</th>
            <th class="px-4 py-2.5 text-left font-medium text-muted-foreground w-32">{{ t('users.table.status') }}</th>
            <th class="px-4 py-2.5 w-20" />
          </tr>
        </thead>
        <tbody class="divide-y">
          <template v-for="row in rows" :key="row.kind === 'user' ? `u-${row.data.id}` : `i-${row.data.id}`">
            <!-- Active user row -->
            <tr v-if="row.kind === 'user'" class="hover:bg-muted/30 transition-colors">
              <td class="px-4 py-3">
                <p class="font-medium">{{ row.data.name }}</p>
                <p class="text-xs text-muted-foreground">{{ row.data.email }}</p>
              </td>
              <td class="px-4 py-3">
                <div v-if="row.data.role_ids.length" class="flex flex-wrap gap-1">
                  <span
                    v-for="id in row.data.role_ids"
                    :key="id"
                    class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium text-white"
                    :style="{ backgroundColor: roleColor(id) }"
                  >
                    {{ roleName(id) }}
                  </span>
                </div>
                <span v-else class="text-xs text-muted-foreground">—</span>
              </td>
              <td class="px-4 py-3 text-muted-foreground tabular-nums">
                {{ decimalHoursToHm(row.data.contracted_hours) }}h
              </td>
              <td class="px-4 py-3">
                <span
                  v-if="row.data.is_admin"
                  class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary"
                >
                  <ShieldCheck class="h-3 w-3" />
                  {{ t('users.admin') }}
                </span>
              </td>
              <td class="px-4 py-3">
                <div class="flex gap-0.5 justify-end">
                  <Button variant="ghost" size="icon" class="h-7 w-7" @click="openEdit(row.data)">
                    <Pencil class="h-3.5 w-3.5" />
                  </Button>
                  <Button variant="ghost" size="icon" class="h-7 w-7 text-destructive hover:text-destructive" @click="openDelete(row.data)">
                    <Trash2 class="h-3.5 w-3.5" />
                  </Button>
                </div>
              </td>
            </tr>

            <!-- Pending invite row -->
            <tr v-else class="hover:bg-muted/30 transition-colors opacity-70">
              <td class="px-4 py-3">
                <p class="text-muted-foreground italic">{{ row.data.email }}</p>
              </td>
              <td class="px-4 py-3">
                <div v-if="row.data.role_ids.length" class="flex flex-wrap gap-1">
                  <span
                    v-for="id in row.data.role_ids"
                    :key="id"
                    class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium text-white"
                    :style="{ backgroundColor: roleColor(id) }"
                  >
                    {{ roleName(id) }}
                  </span>
                </div>
                <span v-else class="text-xs text-muted-foreground">—</span>
              </td>
              <td class="px-4 py-3 text-muted-foreground tabular-nums">
                {{ decimalHoursToHm(parseFloat(row.data.contracted_hours)) }}h
              </td>
              <td class="px-4 py-3">
                <span class="inline-flex items-center gap-1 rounded-full bg-amber-500/10 px-2 py-0.5 text-xs font-medium text-amber-600">
                  <Clock class="h-3 w-3" />
                  {{ t('users.pending') }}
                </span>
              </td>
              <td class="px-4 py-3">
                <div class="flex gap-0.5 justify-end">
                  <Button
                    variant="ghost"
                    size="icon"
                    class="h-7 w-7 text-destructive hover:text-destructive"
                    :disabled="revokeInvitation.isPending.value"
                    @click="revokeInvitation.mutate(row.data.id)"
                  >
                    <Trash2 class="h-3.5 w-3.5" />
                  </Button>
                </div>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <Dialog :open="dialogOpen" @update:open="dialogOpen = $event">
      <DialogContent class="sm:max-w-sm">
        <DialogHeader>
          <DialogTitle>{{ editing ? t('users.dialog.edit') : t('users.dialog.new') }}</DialogTitle>
        </DialogHeader>
        <div class="grid gap-4 py-2">
          <div class="grid gap-1.5">
            <Label for="user-name">{{ t('users.dialog.name') }}</Label>
            <Input id="user-name" v-model="name" />
            <p v-if="errors.name" class="text-sm text-destructive">{{ errors.name }}</p>
          </div>
          <div class="grid gap-1.5">
            <Label for="user-email">{{ t('users.dialog.email') }}</Label>
            <Input id="user-email" type="email" v-model="email" />
            <p v-if="errors.email" class="text-sm text-destructive">{{ errors.email }}</p>
          </div>
          <div class="grid gap-1.5">
            <Label for="user-password">{{ editing ? t('users.dialog.newPassword') : t('users.dialog.password') }}</Label>
            <Input id="user-password" type="password" v-model="password" />
            <p v-if="errors.password" class="text-sm text-destructive">{{ errors.password }}</p>
          </div>
          <div class="grid gap-1.5">
            <Label for="user-hours">{{ t('users.dialog.contractedHours') }}</Label>
            <Input id="user-hours" v-model="contractedHoursHm" placeholder="8:00" />
            <p v-if="errors.contracted_hours" class="text-sm text-destructive">{{ errors.contracted_hours }}</p>
          </div>
          <div v-if="rolesQuery.data.value?.length" class="grid gap-2">
            <Label>{{ t('users.dialog.role') }}</Label>
            <div class="space-y-2">
              <div
                v-for="role in rolesQuery.data.value"
                :key="role.id"
                class="flex items-center gap-2"
              >
                <Checkbox
                  :id="`user-role-${role.id}`"
                  :model-value="selectedRoleIds.includes(role.id)"
                  @update:model-value="(v) => v ? selectedRoleIds.push(role.id) : selectedRoleIds.splice(selectedRoleIds.indexOf(role.id), 1)"
                />
                <Label :for="`user-role-${role.id}`" class="cursor-pointer flex items-center gap-2">
                  <span class="h-3 w-3 rounded-full shrink-0" :style="{ backgroundColor: role.color }" />
                  {{ role.name }}
                </Label>
              </div>
            </div>
          </div>
          <div class="flex items-center justify-between">
            <Label for="user-admin" class="cursor-pointer">{{ t('users.dialog.admin') }}</Label>
            <Switch id="user-admin" v-model="isAdmin" />
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" @click="dialogOpen = false">{{ t('users.dialog.cancel') }}</Button>
          <Button :disabled="storeMutation.isPending.value || updateMutation.isPending.value" @click="submit">
            {{ t('users.dialog.save') }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <InviteDialog
      :open="inviteDialogOpen"
      :organisation-id="props.organisationId"
      @update:open="inviteDialogOpen = $event"
    />

    <AlertDialog :open="deleteDialogOpen" @update:open="deleteDialogOpen = $event">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>{{ t('users.delete.title') }}</AlertDialogTitle>
          <AlertDialogDescription>
            {{ t('users.delete.description', { name: deleting?.name }) }}
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>{{ t('users.delete.cancel') }}</AlertDialogCancel>
          <AlertDialogAction
            class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
            :disabled="destroyMutation.isPending.value"
            @click="confirmDelete"
          >
            {{ t('users.delete.confirm') }}
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </div>
</template>
