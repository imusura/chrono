<script setup lang="ts">
import { computed, ref, watch } from 'vue'
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
import { useAdminActivities } from '@/composables/useAdminActivities'
import ColorPicker from '@/components/admin/ColorPicker.vue'
import type { Activity } from '@/types'
import { useI18n } from 'vue-i18n'

const props = defineProps<{
  organisationId?: number
}>()

const { t } = useI18n()
const orgIdRef = computed(() => props.organisationId)
const { query, storeMutation, updateMutation, destroyMutation } = useAdminActivities(orgIdRef)

const dialogOpen = ref(false)
const editing = ref<Activity | null>(null)
const name = ref('')
const color = ref('#10b981')
const isActive = ref(true)
const nameError = ref('')

const deleteDialogOpen = ref(false)
const deleting = ref<Activity | null>(null)

const openCreate = () => {
  editing.value = null
  name.value = ''
  color.value = '#10b981'
  isActive.value = true
  nameError.value = ''
  dialogOpen.value = true
}

const openEdit = (activity: Activity) => {
  editing.value = activity
  name.value = activity.name
  color.value = activity.color
  isActive.value = activity.is_active
  nameError.value = ''
  dialogOpen.value = true
}

const openDelete = (activity: Activity) => {
  deleting.value = activity
  deleteDialogOpen.value = true
}

watch(dialogOpen, (open) => {
  if (!open) nameError.value = ''
})

const submit = async () => {
  nameError.value = ''
  const payload = {
    name: name.value,
    color: color.value,
    is_active: isActive.value,
    role_ids: editing.value?.role_ids ?? [],
  }
  try {
    if (editing.value) {
      await updateMutation.mutateAsync({ id: editing.value.id, payload })
    } else {
      await storeMutation.mutateAsync({
        ...payload,
        ...(props.organisationId ? { organisation_id: props.organisationId } : {}),
      })
    }
    dialogOpen.value = false
  } catch (e: unknown) {
    const err = e as { errors?: Record<string, string[]> }
    if (err?.errors?.name) nameError.value = err.errors.name[0]
  }
}

const usedColors = computed(() =>
  (query.data.value ?? [])
    .filter((a) => a.id !== editing.value?.id)
    .map((a) => ({ color: a.color, label: a.name })),
)

const confirmDelete = async () => {
  if (!deleting.value) return
  await destroyMutation.mutateAsync(deleting.value.id)
  deleteDialogOpen.value = false
  deleting.value = null
}
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-4">
      <p class="text-sm text-muted-foreground">{{ t('activities.description') }}</p>
      <Button size="sm" @click="openCreate">
        <Plus class="h-4 w-4 mr-1" />
        {{ t('activities.newActivity') }}
      </Button>
    </div>

    <div v-if="query.isLoading.value" class="space-y-2">
      <Skeleton v-for="i in 3" :key="i" class="h-12 w-full rounded-lg" />
    </div>

    <div v-else-if="!query.data.value?.length" class="rounded-lg border border-dashed px-4 py-10 text-center">
      <p class="text-sm font-medium mb-1">{{ t('activities.noActivities') }}</p>
      <p class="text-xs text-muted-foreground mb-4">{{ t('activities.noActivitiesHint') }}</p>
      <Button size="sm" @click="openCreate">
        <Plus class="h-4 w-4 mr-1" />
        {{ t('activities.createFirst') }}
      </Button>
    </div>

    <div v-else class="rounded-lg border divide-y">
      <div
        v-for="activity in query.data.value"
        :key="activity.id"
        class="flex items-center justify-between px-4 py-3"
      >
        <div class="flex items-center gap-3">
          <span class="h-4 w-4 rounded-full shrink-0" :style="{ backgroundColor: activity.color }" />
          <div>
            <span class="text-sm font-medium">{{ activity.name }}</span>
            <span v-if="!activity.is_active" class="ml-2 text-xs text-muted-foreground">{{ t('activities.inactive') }}</span>
          </div>
        </div>
        <div class="flex gap-1">
          <Button variant="ghost" size="icon" @click="openEdit(activity)">
            <Pencil class="h-4 w-4" />
          </Button>
          <Button variant="ghost" size="icon" class="text-destructive hover:text-destructive" @click="openDelete(activity)">
            <Trash2 class="h-4 w-4" />
          </Button>
        </div>
      </div>
    </div>

    <Dialog :open="dialogOpen" @update:open="dialogOpen = $event">
      <DialogContent class="sm:max-w-sm">
        <DialogHeader>
          <DialogTitle>{{ editing ? t('activities.dialog.edit') : t('activities.dialog.new') }}</DialogTitle>
        </DialogHeader>
        <div class="grid gap-4 py-2">
          <div class="grid gap-1.5">
            <Label for="activity-name">{{ t('activities.dialog.name') }}</Label>
            <Input id="activity-name" v-model="name" />
            <p v-if="nameError" class="text-sm text-destructive">{{ nameError }}</p>
          </div>
          <div class="grid gap-1.5">
            <Label>{{ t('activities.dialog.color') }}</Label>
            <ColorPicker v-model="color" :used-colors="usedColors" />
          </div>
          <div class="flex items-center gap-2">
            <Checkbox id="activity-active" v-model="isActive" />
            <Label for="activity-active" class="cursor-pointer">{{ t('activities.dialog.active') }}</Label>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" @click="dialogOpen = false">{{ t('activities.dialog.cancel') }}</Button>
          <Button :disabled="storeMutation.isPending.value || updateMutation.isPending.value" @click="submit">
            {{ t('activities.dialog.save') }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <AlertDialog :open="deleteDialogOpen" @update:open="deleteDialogOpen = $event">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>{{ t('activities.delete.title') }}</AlertDialogTitle>
          <AlertDialogDescription>
            {{ t('activities.delete.description', { name: deleting?.name }) }}
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>{{ t('activities.delete.cancel') }}</AlertDialogCancel>
          <AlertDialogAction
            class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
            :disabled="destroyMutation.isPending.value"
            @click="confirmDelete"
          >
            {{ t('activities.delete.confirm') }}
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </div>
</template>
