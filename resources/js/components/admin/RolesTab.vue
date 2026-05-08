<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useQueryClient } from '@tanstack/vue-query'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Skeleton } from '@/components/ui/skeleton'
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
import { Pencil, Trash2, Plus } from 'lucide-vue-next'
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

// --- Selected role (right panel) ---
const selectedRoleId = ref<number | null>(null)
const selectedRole = computed(() => query.data.value?.find((r) => r.id === selectedRoleId.value) ?? null)

watch(() => query.data.value, (roles) => {
  if (!roles) return
  if (selectedRoleId.value && !roles.find((r) => r.id === selectedRoleId.value)) {
    selectedRoleId.value = roles[0]?.id ?? null
  }
  if (selectedRoleId.value === null && roles.length) {
    selectedRoleId.value = roles[0].id
  }
}, { immediate: true })

// Per-activity saving state to show spinner on the checkbox being toggled
const savingActivityIds = ref<Set<number>>(new Set())

const toggleActivity = async (activityId: number, checked: boolean) => {
  if (!selectedRole.value) return
  const role = selectedRole.value
  const activity = activitiesQuery.data.value?.find((a) => a.id === activityId)
  if (!activity) return

  savingActivityIds.value = new Set([...savingActivityIds.value, activityId])
  const newRoleIds = checked
    ? [...activity.role_ids, role.id]
    : activity.role_ids.filter((id) => id !== role.id)
  try {
    await updateActivity.mutateAsync({
      id: activityId,
      payload: { name: activity.name, color: activity.color, is_active: activity.is_active, role_ids: newRoleIds },
    })
    await queryClient.invalidateQueries({ queryKey: ['admin-roles'] })
  } finally {
    savingActivityIds.value = new Set([...savingActivityIds.value].filter((id) => id !== activityId))
  }
}

// --- New activity inline form ---
const showNewActivityForm = ref(false)
const newActivityName = ref('')
const newActivityColor = ref('#10b981')
const newActivityError = ref('')
const newActivityPending = ref(false)

const submitNewActivity = async () => {
  if (!selectedRole.value || !newActivityName.value.trim()) return
  newActivityError.value = ''
  newActivityPending.value = true
  try {
    await storeActivity.mutateAsync({
      name: newActivityName.value.trim(),
      color: newActivityColor.value,
      is_active: true,
      role_ids: [selectedRole.value.id],
      ...(props.organisationId ? { organisation_id: props.organisationId } : {}),
    })
    showNewActivityForm.value = false
    newActivityName.value = ''
    newActivityColor.value = '#10b981'
    await queryClient.invalidateQueries({ queryKey: ['admin-roles'] })
  } catch (e: unknown) {
    const err = e as { errors?: Record<string, string[]> }
    if (err?.errors?.name) newActivityError.value = err.errors.name[0]
  } finally {
    newActivityPending.value = false
  }
}

// --- Role create/edit dialog ---
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
      const created = await storeMutation.mutateAsync({
        name: roleName.value,
        color: roleColor.value,
        ...(props.organisationId ? { organisation_id: props.organisationId } : {}),
      })
      selectedRoleId.value = created.id
    }
    roleDialogOpen.value = false
  } catch (e: unknown) {
    const err = e as { errors?: Record<string, string[]> }
    if (err?.errors?.name) roleNameError.value = err.errors.name[0]
  }
}

// --- Delete dialog ---
const deleteDialogOpen = ref(false)
const deleting = ref<Role | null>(null)

const openDelete = (role: Role) => {
  deleting.value = role
  deleteDialogOpen.value = true
}

const confirmDelete = async () => {
  if (!deleting.value) return
  const deletedId = deleting.value.id
  await destroyMutation.mutateAsync(deletedId)
  deleteDialogOpen.value = false
  deleting.value = null
  if (selectedRoleId.value === deletedId) selectedRoleId.value = null
}

// --- Color helpers ---
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

    <!-- Loading -->
    <div v-if="query.isLoading.value" class="space-y-2">
      <Skeleton v-for="i in 3" :key="i" class="h-12 w-full rounded-lg" />
    </div>

    <!-- Empty -->
    <div v-else-if="!query.data.value?.length" class="rounded-lg border border-dashed px-4 py-10 text-center">
      <p class="text-sm font-medium mb-1">{{ t('roles.noRoles') }}</p>
      <p class="text-xs text-muted-foreground mb-4">{{ t('roles.noRolesHint') }}</p>
      <Button size="sm" @click="openCreateRole">
        <Plus class="h-4 w-4 mr-1" />
        {{ t('roles.createFirst') }}
      </Button>
    </div>

    <!-- Split panel -->
    <div v-else class="grid grid-cols-5 gap-4 min-h-64">
      <!-- Left: role list -->
      <div class="col-span-2 rounded-lg border divide-y overflow-hidden">
        <button
          v-for="role in query.data.value"
          :key="role.id"
          type="button"
          class="w-full flex items-center justify-between px-4 py-3 text-left transition-colors hover:bg-muted/50"
          :class="selectedRoleId === role.id ? 'bg-muted' : ''"
          @click="selectedRoleId = role.id"
        >
          <div class="flex items-center gap-3 min-w-0">
            <span class="h-3.5 w-3.5 rounded-full shrink-0" :style="{ backgroundColor: role.color }" />
            <span class="text-sm font-medium truncate">{{ role.name }}</span>
            <span class="text-xs text-muted-foreground shrink-0">{{ role.activity_ids.length }}</span>
          </div>
          <div class="flex gap-0.5 shrink-0 ml-2">
            <Button
              variant="ghost"
              size="icon"
              class="h-7 w-7"
              @click.stop="openEditRole(role)"
            >
              <Pencil class="h-3.5 w-3.5" />
            </Button>
            <Button
              variant="ghost"
              size="icon"
              class="h-7 w-7 text-destructive hover:text-destructive"
              @click.stop="openDelete(role)"
            >
              <Trash2 class="h-3.5 w-3.5" />
            </Button>
          </div>
        </button>
      </div>

      <!-- Right: activities panel -->
      <div class="col-span-3 rounded-lg border p-4 flex flex-col gap-4">
        <template v-if="selectedRole">
          <div class="flex items-center gap-2">
            <span class="h-3.5 w-3.5 rounded-full shrink-0" :style="{ backgroundColor: selectedRole.color }" />
            <p class="text-sm font-medium">{{ selectedRole.name }}</p>
            <span class="text-xs text-muted-foreground">{{ t('roles.activities') }}</span>
          </div>

          <div v-if="activitiesQuery.isLoading.value" class="space-y-2">
            <Skeleton v-for="i in 4" :key="i" class="h-7 w-full rounded" />
          </div>

          <div v-else-if="activitiesQuery.data.value?.length" class="grid grid-cols-2 gap-x-6 gap-y-2">
            <div
              v-for="activity in activitiesQuery.data.value"
              :key="activity.id"
              class="flex items-center gap-2"
            >
              <Checkbox
                :id="`act-${activity.id}`"
                :model-value="selectedRole!.activity_ids.includes(activity.id)"
                :disabled="savingActivityIds.has(activity.id)"
                @update:model-value="(v) => toggleActivity(activity.id, !!v)"
              />
              <Label :for="`act-${activity.id}`" class="cursor-pointer flex items-center gap-2 text-sm">
                <span class="h-3 w-3 rounded-full shrink-0" :style="{ backgroundColor: activity.color }" />
                {{ activity.name }}
              </Label>
            </div>
          </div>

          <p v-else class="text-sm text-muted-foreground">{{ t('roles.activitiesDialog.noActivities') }}</p>

          <!-- New activity inline form -->
          <div v-if="showNewActivityForm" class="rounded-md border p-3 grid gap-3">
            <div class="grid gap-1.5">
              <Label for="new-act-name">{{ t('roles.activitiesDialog.activityName') }}</Label>
              <Input
                id="new-act-name"
                v-model="newActivityName"
                :placeholder="t('roles.activitiesDialog.activityNamePlaceholder')"
                @keydown.enter="submitNewActivity"
              />
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
              <Button size="sm" variant="ghost" @click="showNewActivityForm = false; newActivityName = ''; newActivityError = ''">
                {{ t('roles.activitiesDialog.cancel') }}
              </Button>
            </div>
          </div>

          <div class="mt-auto pt-2 border-t">
            <Button variant="outline" size="sm" @click="showNewActivityForm = true; newActivityError = ''">
              <Plus class="h-4 w-4 mr-1" />
              {{ t('roles.activitiesDialog.newActivity') }}
            </Button>
          </div>
        </template>
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
