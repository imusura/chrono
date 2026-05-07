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
import { Pencil, Trash2, Plus, Mail } from 'lucide-vue-next'
import { useAdminUsers } from '@/composables/useAdminUsers'
import { useAdminRoles } from '@/composables/useAdminRoles'
import InviteDialog from '@/components/admin/InviteDialog.vue'
import { decimalHoursToHm, hmToDecimalHours } from '@/lib/format'
import type { AdminUser } from '@/types'
import { useI18n } from 'vue-i18n'

const props = defineProps<{
  organisationId?: number
}>()

const { t } = useI18n()
const orgIdRef = computed(() => props.organisationId)
const { query, storeMutation, updateMutation, destroyMutation } = useAdminUsers(orgIdRef)
const { query: rolesQuery } = useAdminRoles(orgIdRef)

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

const formatHours = (h: number) => `${decimalHoursToHm(h)}h/day`

const roleName = (id: number) => rolesQuery.data.value?.find((r) => r.id === id)?.name ?? ''
const roleColor = (id: number) => rolesQuery.data.value?.find((r) => r.id === id)?.color ?? '#888'
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-4">
      <p class="text-sm text-muted-foreground">{{ t('users.description') }}</p>
      <div class="flex gap-2">
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

    <div v-if="query.isLoading.value" class="space-y-2">
      <Skeleton v-for="i in 3" :key="i" class="h-12 w-full rounded-lg" />
    </div>

    <div v-else-if="!query.data.value?.length" class="rounded-lg border border-dashed px-4 py-10 text-center">
      <p class="text-sm font-medium mb-1">{{ t('users.noUsers') }}</p>
      <p class="text-xs text-muted-foreground mb-4">{{ t('users.noUsersHint') }}</p>
      <Button size="sm" @click="openCreate">
        <Plus class="h-4 w-4 mr-1" />
        {{ t('users.addFirst') }}
      </Button>
    </div>

    <div v-else class="rounded-lg border divide-y">
      <div
        v-for="user in query.data.value"
        :key="user.id"
        class="px-4 py-3"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium">{{ user.name }}</p>
            <p class="text-xs text-muted-foreground">
              {{ user.email }} &middot; {{ decimalHoursToHm(user.contracted_hours) }}h/day
              <span v-if="user.is_admin"> &middot; {{ t('users.admin') }}</span>
            </p>
          </div>
          <div class="flex gap-1">
            <Button variant="ghost" size="icon" @click="openEdit(user)">
              <Pencil class="h-4 w-4" />
            </Button>
            <Button variant="ghost" size="icon" class="text-destructive hover:text-destructive" @click="openDelete(user)">
              <Trash2 class="h-4 w-4" />
            </Button>
          </div>
        </div>
        <div v-if="user.role_ids.length" class="mt-2 flex flex-wrap gap-1.5">
          <span
            v-for="id in user.role_ids"
            :key="id"
            class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium text-white"
            :style="{ backgroundColor: roleColor(id) }"
          >
            {{ roleName(id) }}
          </span>
        </div>
      </div>
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
