<script setup lang="ts">
import { ref } from 'vue'
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/components/ui/sheet'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import { Plus, Pencil, Trash2 } from 'lucide-vue-next'
import type { TimeEntry, StoreTimeEntryPayload, UpdateTimeEntryPayload } from '@/types'
import { minutesToHm, entryMinutes } from '@/lib/format'
import TimeEntryDialog from './TimeEntryDialog.vue'

const props = defineProps<{
  open: boolean
  date: string
  entries: TimeEntry[]
  contractedMinutes: number
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
  store: [payload: StoreTimeEntryPayload]
  update: [id: number, payload: UpdateTimeEntryPayload]
  destroy: [id: number]
}>()

const dialogOpen = ref(false)
const editingEntry = ref<TimeEntry | null>(null)

const totalMinutes = () =>
  props.entries.reduce((sum, e) => sum + entryMinutes(e.started_at, e.ended_at), 0)

const formattedDate = (dateStr: string) =>
  new Date(dateStr + 'T00:00:00').toLocaleDateString('hr-HR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
  })

const openAdd = () => {
  editingEntry.value = null
  dialogOpen.value = true
}

const openEdit = (entry: TimeEntry) => {
  editingEntry.value = entry
  dialogOpen.value = true
}

const handleSave = (payload: StoreTimeEntryPayload | UpdateTimeEntryPayload) => {
  if (editingEntry.value) {
    emit('update', editingEntry.value.id, payload as UpdateTimeEntryPayload)
  } else {
    emit('store', payload as StoreTimeEntryPayload)
  }
  dialogOpen.value = false
}
</script>

<template>
  <Sheet :open="open" @update:open="emit('update:open', $event)">
    <SheetContent side="right" class="w-full sm:max-w-[480px] flex flex-col gap-0 p-0">
      <SheetHeader class="px-6 pt-6 pb-4">
        <SheetTitle class="capitalize">{{ formattedDate(date) }}</SheetTitle>
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
            <p class="text-xs text-muted-foreground">{{ entry.started_at }} – {{ entry.ended_at }}</p>
            <p v-if="entry.notes" class="text-xs text-muted-foreground mt-0.5 line-clamp-1">{{ entry.notes }}</p>
          </div>
          <div class="text-sm font-medium tabular-nums text-muted-foreground">
            {{ minutesToHm(entryMinutes(entry.started_at, entry.ended_at)) }}
          </div>
          <div class="flex gap-1">
            <Button variant="ghost" size="icon" class="h-7 w-7" @click="openEdit(entry)">
              <Pencil class="h-3.5 w-3.5" />
            </Button>
            <Button variant="ghost" size="icon" class="h-7 w-7 text-destructive hover:text-destructive" @click="emit('destroy', entry.id)">
              <Trash2 class="h-3.5 w-3.5" />
            </Button>
          </div>
        </div>

        <p v-if="entries.length === 0" class="text-sm text-muted-foreground text-center py-8">
          No entries yet.
        </p>
      </div>

      <Separator />

      <div class="px-6 py-4 flex items-center justify-between">
        <div class="text-sm">
          <span class="font-semibold">{{ minutesToHm(totalMinutes()) }}</span>
          <span class="text-muted-foreground"> / {{ minutesToHm(contractedMinutes) }}</span>
        </div>
        <Button size="sm" @click="openAdd">
          <Plus class="h-4 w-4 mr-1" />
          Add entry
        </Button>
      </div>
    </SheetContent>
  </Sheet>

  <TimeEntryDialog
    :open="dialogOpen"
    :date="date"
    :entry="editingEntry"
    @update:open="dialogOpen = $event"
    @save="handleSave"
  />
</template>
