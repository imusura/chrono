<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useQueryClient } from '@tanstack/vue-query'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Skeleton } from '@/components/ui/skeleton'
import { Checkbox } from '@/components/ui/checkbox'
import { Separator } from '@/components/ui/separator'
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
import { Pencil, Trash2, Plus, Layers } from 'lucide-vue-next'
import { useAdminRoles } from '@/composables/useAdminRoles'
import { useAdminActivities } from '@/composables/useAdminActivities'
import ColorPicker from '@/components/admin/ColorPicker.vue'
import type { Role } from '@/types'
import { useI18n } from 'vue-i18n'

const props = defineProps<{
  organisationId?: number
}>()

const { t } = useI18n()
const orgIdRef = computed(() => props.organisationId)
const queryClient = useQueryClient()
const { query, storeMutation, updateMutation, destroyMutation } = useAdminRoles(orgIdRef)
const { query: activitiesQuery, storeMutation: storeActivity, updateMutation: updateActivity } = useAdminActivities(orgIdRef)

const roleDialogOpen = ref(false)
const editingRole = ref<Role | null>(null)
const roleName = ref('')
const roleColor = ref('#6366f1')
const roleNameError = ref('')

const openCreateRole = () => {
  editingRole.value = null
  roleName.value = ''
  roleColor.value = '#6366f1'
  roleNameError.value = ''
  roleDialogOpen.value = true
}

const openEditRole = (role: Role) => {
  editingRole.value = role
  roleName.value = role.name
  roleColor.value = role.color
  roleNameError.value = ''
  roleDialogOpen.value = true
}

watch(roleDialogOpen, (open) => {
  if (!open) roleNameError.value = ''
})

const submitRole = async () => {
  roleNameError.value = ''
  try {
    if (editingRole.value) {
      await updateMutation.mutateAsync({ id: editingRole.value.id, payload: { name: roleName.value, color: roleColor.value } })
    } else {
      await storeMutation.mutateAsync({
        name: roleName.value,
        color: roleColor.value,
        ...(props.organisationId ? { organisation_id: props.organisationId } : {}),
      })
    }
    roleDialogOpen.value = false
  } catch (e: unknown) {
    const err = e as { errors?: Record<string, string[]> }
    if (err?.errors?.name) roleNameError.value = err.errors.name[0]
  }
}

const deleteDialogOpen = ref(false)
const deleting = ref<Role | null>(null)

const openDelete = (role: Role) => {
  deleting.value = role
  deleteDialogOpen.value = true
}

const confirmDelete = async () => {
  if (!deleting.value) return
  await destroyMutation.mutateAsync(deleting.value.id)
  deleteDialogOpen.value = false
  deleting.value = null
}

const activitiesDialogOpen = ref(false)
const managingRole = ref<Role | null>(null)
const selectedActivityIds = ref<number[]>([])

const showNewActivityForm = ref(false)
const newActivityName = ref('')
const newActivityColor = ref('#10b981')
const newActivityError = ref('')
const newActivityPending = ref(false)

const openManageActivities = (role: Role) => {
  managingRole.value = role
  selectedActivityIds.value = [...role.activity_ids]
  showNewActivityForm.value = false
  newActivityName.value = ''
  newActivityColor.value = '#10b981'
  newActivityError.value = ''
  activitiesDialogOpen.value = true
}

watch(activitiesDialogOpen, (open) => {
  if (!open) {
    showNewActivityForm.value = false
    newActivityError.value = ''
  }
})

const submitActivities = async () => {
  if (!managingRole.value) return

  const role = managingRole.value
  const all = activitiesQuery.data.value ?? []
  await Promise.all(
    all.map((activity) => {
      const shouldHave = selectedActivityIds.value.includes(activity.id)
      const hasNow = activity.role_ids.includes(role.id)
      if (shouldHave === hasNow) return Promise.resolve()
      const newRoleIds = shouldHave
        ? [...activity.role_ids, role.id]
        : activity.role_ids.filter((id) => id !== role.id)
      return updateActivity.mutateAsync({
        id: activity.id,
        payload: { name: activity.name, color: activity.color, is_active: activity.is_active, role_ids: newRoleIds },
      })
    }),
  )

  await queryClient.invalidateQueries({ queryKey: ['admin-roles'] })
  activitiesDialogOpen.value = false
}

const submitNewActivity = async () => {
  if (!managingRole.value || !newActivityName.value.trim()) return
  newActivityError.value = ''
  newActivityPending.value = true
  try {
    const created = await storeActivity.mutateAsync({
      name: newActivityName.value.trim(),
      color: newActivityColor.value,
      is_active: true,
      role_ids: [managingRole.value.id],
      ...(props.organisationId ? { organisation_id: props.organisationId } : {}),
    })
    selectedActivityIds.value.push(created.id)
    showNewActivityForm.value = false
    newActivityName.value = ''
    newActivityColor.value = '#10b981'
  } catch (e: unknown) {
    const err = e as { errors?: Record<string, string[]> }
    if (err?.errors?.name) newActivityError.value = err.errors.name[0]
  } finally {
    newActivityPending.value = false
  }
}

const activityName = (id: number) =>
  activitiesQuery.data.value?.find((a) => a.id === id)?.name ?? ''

const activityColor = (id: number) =>
  activitiesQuery.data.value?.find((a) => a.id === id)?.color ?? '#888'

const usedRoleColors = computed(() =>
  (query.data.value ?? [])
    .filter((r) => r.id !== editingRole.value?.id)
    .map((r) => ({ color: r.color, label: r.name })),
)

const usedActivityColors = computed(() =>
  (activitiesQuery.data.value ?? []).map((a) => ({ color: a.color, label: a.name })),
)
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-4">
      <p class="text-sm text-muted-foreground">{{ t('roles.description') }}</p>
      <Button size="sm" @click="openCreateRole">
        <Plus class="h-4 w-4 mr-1" />
        {{ t('roles.newRole') }}
      </Button>
    </div>

    <div v-if="query.isLoading.value" class="space-y-2">
      <Skeleton v-for="i in 3" :key="i" class="h-16 w-full rounded-lg" />
    </div>

    <div v-else-if="!query.data.value?.length" class="rounded-lg border border-dashed px-4 py-10 text-center">
      <p class="text-sm font-medium mb-1">{{ t('roles.noRoles') }}</p>
      <p class="text-xs text-muted-foreground mb-4">{{ t('roles.noRolesHint') }}</p>
      <Button size="sm" @click="openCreateRole">
        <Plus class="h-4 w-4 mr-1" />
        {{ t('roles.createFirst') }}
      </Button>
    </div>

    <div v-else class="rounded-lg border divide-y">
      <div
        v-for="role in query.data.value"
        :key="role.id"
        class="px-4 py-3"
      >
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <span class="h-4 w-4 rounded-full shrink-0" :style="{ backgroundColor: role.color }" />
            <span class="text-sm font-medium">{{ role.name }}</span>
          </div>
          <div class="flex gap-1">
            <Button variant="ghost" size="sm" class="text-muted-foreground gap-1.5" @click="openManageActivities(role)">
              <Layers class="h-3.5 w-3.5" />
              {{ t('roles.activities') }}
            </Button>
            <Button variant="ghost" size="icon" @click="openEditRole(role)">
              <Pencil class="h-4 w-4" />
            </Button>
            <Button variant="ghost" size="icon" class="text-destructive hover:text-destructive" @click="openDelete(role)">
              <Trash2 class="h-4 w-4" />
            </Button>
          </div>
        </div>
        <div v-if="role.activity_ids.length" class="mt-2 flex flex-wrap gap-1.5 pl-7">
          <span
            v-for="id in role.activity_ids"
            :key="id"
            class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium text-white"
            :style="{ backgroundColor: activityColor(id) }"
          >
            {{ activityName(id) }}
          </span>
        </div>
        <p v-else class="mt-1.5 pl-7 text-xs text-muted-foreground">{{ t('roles.noActivities') }}</p>
      </div>
    </div>

    <!-- Role create/edit dialog -->
    <Dialog :open="roleDialogOpen" @update:open="roleDialogOpen = $event">
      <DialogContent class="sm:max-w-sm">
        <DialogHeader>
          <DialogTitle>{{ editingRole ? t('roles.dialog.edit') : t('roles.dialog.new') }}</DialogTitle>
        </DialogHeader>
        <div class="grid gap-4 py-2">
          <div class="grid gap-1.5">
            <Label for="role-name">{{ t('roles.dialog.name') }}</Label>
            <Input id="role-name" v-model="roleName" @keydown.enter="submitRole" />
            <p v-if="roleNameError" class="text-sm text-destructive">{{ roleNameError }}</p>
          </div>
          <div class="grid gap-1.5">
            <Label>{{ t('roles.dialog.color') }}</Label>
            <ColorPicker v-model="roleColor" :used-colors="usedRoleColors" />
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" @click="roleDialogOpen = false">{{ t('roles.dialog.cancel') }}</Button>
          <Button :disabled="storeMutation.isPending.value || updateMutation.isPending.value" @click="submitRole">
            {{ t('roles.dialog.save') }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Manage activities dialog -->
    <Dialog :open="activitiesDialogOpen" @update:open="activitiesDialogOpen = $event">
      <DialogContent class="sm:max-w-sm">
        <DialogHeader>
          <DialogTitle>{{ t('roles.activitiesDialog.title', { name: managingRole?.name }) }}</DialogTitle>
        </DialogHeader>

        <div class="grid gap-3 py-2">
          <div v-if="activitiesQuery.data.value?.length" class="space-y-2">
            <div
              v-for="activity in activitiesQuery.data.value"
              :key="activity.id"
              class="flex items-center gap-2"
            >
              <Checkbox
                :id="`act-${activity.id}`"
                :model-value="selectedActivityIds.includes(activity.id)"
                @update:model-value="(v) => v ? selectedActivityIds.push(activity.id) : selectedActivityIds.splice(selectedActivityIds.indexOf(activity.id), 1)"
              />
              <Label :for="`act-${activity.id}`" class="cursor-pointer flex items-center gap-2">
                <span class="h-3 w-3 rounded-full shrink-0" :style="{ backgroundColor: activity.color }" />
                {{ activity.name }}
              </Label>
            </div>
          </div>
          <p v-else class="text-sm text-muted-foreground">{{ t('roles.activitiesDialog.noActivities') }}</p>

          <Separator />

          <div v-if="showNewActivityForm" class="grid gap-3">
            <div class="grid gap-1.5">
              <Label for="new-act-name">{{ t('roles.activitiesDialog.activityName') }}</Label>
              <Input id="new-act-name" v-model="newActivityName" :placeholder="t('roles.activitiesDialog.activityNamePlaceholder')" @keydown.enter="submitNewActivity" />
              <p v-if="newActivityError" class="text-sm text-destructive">{{ newActivityError }}</p>
            </div>
            <div class="grid gap-1.5">
              <Label>{{ t('roles.activitiesDialog.color') }}</Label>
              <ColorPicker v-model="newActivityColor" :used-colors="usedActivityColors" />
            </div>
            <div class="flex gap-2">
              <Button size="sm" :disabled="newActivityPending || !newActivityName.trim()" @click="submitNewActivity">
                {{ t('roles.activitiesDialog.addActivity') }}
              </Button>
              <Button size="sm" variant="ghost" @click="showNewActivityForm = false">{{ t('roles.activitiesDialog.cancel') }}</Button>
            </div>
          </div>

          <Button v-else variant="outline" size="sm" class="w-full" @click="showNewActivityForm = true">
            <Plus class="h-4 w-4 mr-1" />
            {{ t('roles.activitiesDialog.newActivity') }}
          </Button>
        </div>

        <DialogFooter>
          <Button variant="outline" @click="activitiesDialogOpen = false">{{ t('roles.activitiesDialog.cancel') }}</Button>
          <Button :disabled="updateActivity.isPending.value" @click="submitActivities">
            {{ t('roles.activitiesDialog.save') }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Delete role dialog -->
    <AlertDialog :open="deleteDialogOpen" @update:open="deleteDialogOpen = $event">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>{{ t('roles.delete.title') }}</AlertDialogTitle>
          <AlertDialogDescription>
            {{ t('roles.delete.description', { name: deleting?.name }) }}
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>{{ t('roles.delete.cancel') }}</AlertDialogCancel>
          <AlertDialogAction
            class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
            :disabled="destroyMutation.isPending.value"
            @click="confirmDelete"
          >
            {{ t('roles.delete.confirm') }}
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </div>
</template>
