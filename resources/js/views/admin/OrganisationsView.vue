<script setup lang="ts">
import { ref, watch } from 'vue'
import AppLayout from '@/components/layout/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
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
import { Skeleton } from '@/components/ui/skeleton'
import { Pencil, Trash2, Plus } from 'lucide-vue-next'
import { useOrganisations } from '@/composables/useOrganisations'
import { formatDate } from '@/lib/format'
import type { Organisation, TimeEntryMode } from '@/types'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const { query, storeMutation, updateMutation, destroyMutation } = useOrganisations()

const dialogOpen = ref(false)
const editingOrg = ref<Organisation | null>(null)
const name = ref('')
const nameError = ref('')
const timeEntryMode = ref<TimeEntryMode>('range')
const countryCode = ref('HR')

const deleteDialogOpen = ref(false)
const deletingOrg = ref<Organisation | null>(null)

const openCreate = () => {
  editingOrg.value = null
  name.value = ''
  nameError.value = ''
  timeEntryMode.value = 'range'
  countryCode.value = 'HR'
  dialogOpen.value = true
}

const openEdit = (org: Organisation) => {
  editingOrg.value = org
  name.value = org.name
  timeEntryMode.value = org.time_entry_mode
  countryCode.value = org.country_code
  nameError.value = ''
  dialogOpen.value = true
}

const openDelete = (org: Organisation) => {
  deletingOrg.value = org
  deleteDialogOpen.value = true
}

watch(dialogOpen, (open) => {
  if (!open) nameError.value = ''
})

const submit = async () => {
  nameError.value = ''
  try {
    if (editingOrg.value) {
      await updateMutation.mutateAsync({ id: editingOrg.value.id, payload: { name: name.value, time_entry_mode: timeEntryMode.value, country_code: countryCode.value } })
    } else {
      await storeMutation.mutateAsync({ name: name.value, time_entry_mode: timeEntryMode.value, country_code: countryCode.value })
    }
    dialogOpen.value = false
  } catch (e: unknown) {
    const err = e as { errors?: Record<string, string[]> }
    if (err?.errors?.name) nameError.value = err.errors.name[0]
  }
}

const confirmDelete = async () => {
  if (!deletingOrg.value) return
  await destroyMutation.mutateAsync(deletingOrg.value.id)
  deleteDialogOpen.value = false
  deletingOrg.value = null
}
</script>

<template>
  <AppLayout>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-semibold">{{ t('organisations.title') }}</h1>
      <Button @click="openCreate">
        <Plus class="h-4 w-4 mr-1" />
        {{ t('organisations.newOrganisation') }}
      </Button>
    </div>

    <div v-if="query.isLoading.value" class="space-y-2">
      <Skeleton v-for="i in 5" :key="i" class="h-14 w-full rounded-lg" />
    </div>

    <div v-else class="rounded-lg border divide-y">
      <div
        v-for="org in query.data.value"
        :key="org.id"
        class="flex items-center justify-between px-4 py-3"
      >
        <div>
          <div class="flex items-center gap-2">
            <p class="font-medium">{{ org.name }}</p>
            <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-medium ring-1 ring-inset"
              :class="org.time_entry_mode === 'duration'
                ? 'bg-violet-50 text-violet-700 ring-violet-600/20 dark:bg-violet-950/30 dark:text-violet-300'
                : 'bg-sky-50 text-sky-700 ring-sky-600/20 dark:bg-sky-950/30 dark:text-sky-300'"
            >
              {{ org.time_entry_mode === 'duration' ? 'Duration' : 'Start / End' }}
            </span>
            <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-medium ring-1 ring-inset bg-muted text-muted-foreground ring-border">
              {{ org.country_code }}
            </span>
          </div>
          <p class="text-xs text-muted-foreground">{{ t('organisations.created') }} {{ formatDate(org.created_at) }}</p>
        </div>
        <div class="flex gap-1">
          <Button variant="ghost" size="icon" @click="openEdit(org)">
            <Pencil class="h-4 w-4" />
          </Button>
          <Button variant="ghost" size="icon" class="text-destructive hover:text-destructive" @click="openDelete(org)">
            <Trash2 class="h-4 w-4" />
          </Button>
        </div>
      </div>

      <div v-if="!query.data.value?.length" class="px-4 py-8 text-center text-sm text-muted-foreground">
        {{ t('organisations.noOrganisations') }}
      </div>
    </div>

    <Dialog :open="dialogOpen" @update:open="dialogOpen = $event">
      <DialogContent class="sm:max-w-sm">
        <DialogHeader>
          <DialogTitle>{{ editingOrg ? t('organisations.dialog.edit') : t('organisations.dialog.new') }}</DialogTitle>
        </DialogHeader>
        <div class="grid gap-4 py-2">
          <div class="grid gap-1.5">
            <Label for="name">{{ t('organisations.dialog.name') }}</Label>
            <Input id="name" v-model="name" @keydown.enter="submit" />
            <p v-if="nameError" class="text-sm text-destructive">{{ nameError }}</p>
          </div>
          <div class="grid gap-1.5">
            <Label>Time entry mode</Label>
            <Select :model-value="timeEntryMode" @update:model-value="timeEntryMode = $event as TimeEntryMode">
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="range">Start / End time</SelectItem>
                <SelectItem value="duration">Total duration</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="grid gap-1.5">
            <Label for="country-code">{{ t('organisations.dialog.countryCode') }}</Label>
            <Input
              id="country-code"
              v-model="countryCode"
              maxlength="2"
              class="uppercase"
              placeholder="e.g. HR"
              @input="countryCode = (($event.target as HTMLInputElement).value).toUpperCase()"
            />
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" @click="dialogOpen = false">{{ t('organisations.dialog.cancel') }}</Button>
          <Button :disabled="storeMutation.isPending.value || updateMutation.isPending.value" @click="submit">
            {{ t('organisations.dialog.save') }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <AlertDialog :open="deleteDialogOpen" @update:open="deleteDialogOpen = $event">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>{{ t('organisations.delete.title') }}</AlertDialogTitle>
          <AlertDialogDescription>
            {{ t('organisations.delete.description', { name: deletingOrg?.name }) }}
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>{{ t('organisations.delete.cancel') }}</AlertDialogCancel>
          <AlertDialogAction
            class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
            :disabled="destroyMutation.isPending.value"
            @click="confirmDelete"
          >
            {{ t('organisations.delete.confirm') }}
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </AppLayout>
</template>
