<script setup lang="ts">
import { ref } from 'vue'
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/components/ui/sheet'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import { Plus, Pencil, Trash2 } from 'lucide-vue-next'
import type { TimeEntry, StoreTimeEntryPayload, UpdateTimeEntryPayload, TimeEntryMode } from '@/types'
import { minutesToHm, formatDayLong } from '@/lib/format'
import TimeEntryDialog from './TimeEntryDialog.vue'
import type { AxiosError } from 'axios'
import { useI18n } from 'vue-i18n'

const props = defineProps<{
  open: boolean
  date: string
  timeEntryMode: TimeEntryMode
  entries: TimeEntry[]
  contractedMinutes: number
  holidayName?: string
  onStore: (payload: StoreTimeEntryPayload) => Promise<unknown>
  onUpdate: (id: number, payload: UpdateTimeEntryPayload) => Promise<unknown>
  onDestroy: (id: number) => Promise<unknown>
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
}>()

const { t } = useI18n()

const dialogOpen = ref(false)
const editingEntry = ref<TimeEntry | null>(null)
const dialogRef = ref<InstanceType<typeof TimeEntryDialog> | null>(null)

const totalMinutes = () =>
  props.entries.reduce((sum, e) => sum + e.duration_minutes, 0)

const openAdd = () => {
  editingEntry.value = null
  dialogOpen.value = true
}

const openEdit = (entry: TimeEntry) => {
  editingEntry.value = entry
  dialogOpen.value = true
}

const handleSave = async (payload: StoreTimeEntryPayload | UpdateTimeEntryPayload) => {
  try {
    if (editingEntry.value) {
      await props.onUpdate(editingEntry.value.id, payload as UpdateTimeEntryPayload)
    } else {
      await props.onStore(payload as StoreTimeEntryPayload)
    }
    dialogOpen.value = false
  } catch (err) {
    const axiosErr = err as AxiosError<{ errors: Record<string, string[]> }>
    if (axiosErr.response?.status === 422) {
      dialogRef.value?.setErrors(axiosErr)
    }
  }
}
</script>

<template>
  <Sheet :open="open" @update:open="emit('update:open', $event)">
    <SheetContent side="right" class="w-full sm:max-w-[480px] flex flex-col gap-0 p-0">
      <SheetHeader class="px-6 pt-6 pb-4">
        <SheetTitle class="capitalize">{{ formatDayLong(date) }}</SheetTitle>
        <span v-if="holidayName" class="inline-flex items-center self-start rounded-md bg-rose-100 dark:bg-rose-950/40 px-2 py-0.5 text-xs font-medium text-rose-700 dark:text-rose-300">
          {{ holidayName }}
        </span>
        <div class="flex items-baseline gap-1.5 pt-1">
          <span
            class="text-2xl font-bold tabular-nums"
            :class="totalMinutes() >= contractedMinutes
              ? 'text-emerald-600 dark:text-emerald-400'
              : 'text-amber-600 dark:text-amber-400'"
          >{{ minutesToHm(totalMinutes()) }}</span>
          <span class="text-sm text-muted-foreground">/ {{ minutesToHm(contractedMinutes) }}</span>
        </div>
      </SheetHeader>

      <Separator />

      <div class="flex-1 overflow-y-auto px-6 py-4 space-y-3">
        <div
          v-for="entry in entries"
          :key="entry.id"
          class="flex items-center gap-3 rounded-lg border p-3"
        >
          <div
            class="w-2 self-stretch rounded-full flex-shrink-0"
            :style="{ backgroundColor: entry.activity.color ?? '#94a3b8' }"
          />
          <div class="flex-1 min-w-0">
            <p class="font-medium text-sm truncate">{{ entry.activity.name }}</p>
            <p v-if="timeEntryMode === 'range' && entry.started_at && entry.ended_at" class="text-xs text-muted-foreground">
              {{ entry.started_at }} – {{ entry.ended_at }}
            </p>
            <p v-if="entry.notes" class="text-xs text-muted-foreground mt-0.5 line-clamp-1">{{ entry.notes }}</p>
          </div>
          <div class="text-sm font-medium tabular-nums text-muted-foreground">
            {{ minutesToHm(entry.duration_minutes) }}
          </div>
          <div class="flex gap-1">
            <Button variant="ghost" size="icon" class="h-7 w-7" @click="openEdit(entry)">
              <Pencil class="h-3.5 w-3.5" />
            </Button>
            <Button variant="ghost" size="icon" class="h-7 w-7 text-destructive hover:text-destructive" @click="onDestroy(entry.id)">
              <Trash2 class="h-3.5 w-3.5" />
            </Button>
          </div>
        </div>

        <p v-if="entries.length === 0" class="text-sm text-muted-foreground text-center py-8">
          {{ t('time.noEntries') }}
        </p>
      </div>

      <Separator />

      <div class="px-6 py-4">
        <Button class="w-full" @click="openAdd">
          <Plus class="h-4 w-4 mr-1" />
          {{ t('daySheet.addEntry') }}
        </Button>
      </div>
    </SheetContent>
  </Sheet>

  <TimeEntryDialog
    ref="dialogRef"
    :open="dialogOpen"
    :date="date"
    :time-entry-mode="timeEntryMode"
    :entry="editingEntry"
    :day-entries="entries"
    :contracted-minutes="contractedMinutes"
    @update:open="dialogOpen = $event"
    @save="handleSave"
  />
</template>
