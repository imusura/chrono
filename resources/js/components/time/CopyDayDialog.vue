<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { ChevronLeft, ChevronRight, AlertCircle } from 'lucide-vue-next'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Label } from '@/components/ui/label'
import type { TimeEntry, StoreTimeEntryPayload } from '@/types'
import { minutesToHm, toIsoDate, formatMonthYear, formatWeekdayShort } from '@/lib/format'
import { useI18n } from 'vue-i18n'

const props = defineProps<{
  open: boolean
  sourceDate: string
  sourceEntries: TimeEntry[]
  entriesByDate: Map<string, TimeEntry[]>
  nonWorkingDayMap: Map<string, string>
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
  copy: [entries: StoreTimeEntryPayload[]]
}>()

const { t } = useI18n()

const sourceDateObj = computed(() => new Date(props.sourceDate + 'T00:00:00'))

const navYear = ref(0)
const navMonth = ref(0)

const showWeekends = ref(false)
const selectedDates = ref(new Set<string>())
const copying = ref(false)

watch(() => props.open, (open) => {
  if (open) {
    const d = sourceDateObj.value
    navYear.value = d.getFullYear()
    navMonth.value = d.getMonth() + 1
    selectedDates.value = new Set()
    showWeekends.value = false
    copying.value = false
  }
})

const prevMonth = () => {
  if (navMonth.value === 1) { navYear.value--; navMonth.value = 12 } else { navMonth.value-- }
}
const nextMonth = () => {
  if (navMonth.value === 12) { navYear.value++; navMonth.value = 1 } else { navMonth.value++ }
}

const monthLabel = computed(() => formatMonthYear(navYear.value, navMonth.value))

const isWeekend = (d: Date) => d.getDay() === 0 || d.getDay() === 6

const monthDays = computed(() => {
  const result: { date: string; iso: Date; isSource: boolean }[] = []
  const d = new Date(navYear.value, navMonth.value - 1, 1)
  while (d.getMonth() === navMonth.value - 1) {
    const iso = new Date(d)
    const date = toIsoDate(iso)
    if (showWeekends.value || !isWeekend(iso) || date === props.sourceDate) {
      result.push({ date, iso, isSource: date === props.sourceDate })
    }
    d.setDate(d.getDate() + 1)
  }
  return result
})

const dayMinutes = (date: string) =>
  (props.entriesByDate.get(date) ?? []).reduce((sum, e) => sum + e.duration_minutes, 0)

const toggle = (date: string) => {
  const next = new Set(selectedDates.value)
  if (next.has(date)) next.delete(date)
  else next.add(date)
  selectedDates.value = next
}

const selectableDays = computed(() => monthDays.value.filter(d => !d.isSource))

const toggleAll = () => {
  if (selectedDates.value.size === selectableDays.value.length) {
    selectedDates.value = new Set()
  } else {
    selectedDates.value = new Set(selectableDays.value.map(d => d.date))
  }
}

const allChecked = computed(() =>
  selectableDays.value.length > 0 && selectedDates.value.size === selectableDays.value.length,
)

const daysWithExisting = computed(() =>
  [...selectedDates.value].filter(d => dayMinutes(d) > 0),
)

const sourceTotalMinutes = computed(() =>
  props.sourceEntries.reduce((sum, e) => sum + e.duration_minutes, 0),
)

const handleCopy = async () => {
  if (!selectedDates.value.size || copying.value) return
  copying.value = true

  const entries: StoreTimeEntryPayload[] = []
  for (const date of selectedDates.value) {
    for (const entry of props.sourceEntries) {
      entries.push({
        activity_id: entry.activity_id,
        date,
        started_at: entry.started_at ?? undefined,
        ended_at: entry.ended_at ?? undefined,
        duration_minutes: entry.duration_minutes,
        notes: entry.notes ?? undefined,
      })
    }
  }

  emit('copy', entries)
}
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="sm:max-w-md flex flex-col max-h-[90vh]">
      <DialogHeader class="shrink-0">
        <DialogTitle>{{ t('copyDay.title') }}</DialogTitle>
        <p class="text-sm text-muted-foreground">
          {{ t('copyDay.subtitle', { count: sourceEntries.length, entryWord: t('copyDay.entryWord', sourceEntries.length), total: minutesToHm(sourceTotalMinutes), date: formatWeekdayShort(sourceDateObj) + ' ' + sourceDateObj.getDate() }) }}
        </p>
      </DialogHeader>

      <!-- Month navigator -->
      <div class="shrink-0 flex items-center justify-between py-2">
        <Button variant="ghost" size="icon" class="h-8 w-8" @click="prevMonth">
          <ChevronLeft class="h-4 w-4" />
        </Button>
        <span class="text-sm font-medium capitalize">{{ monthLabel }}</span>
        <Button variant="ghost" size="icon" class="h-8 w-8" @click="nextMonth">
          <ChevronRight class="h-4 w-4" />
        </Button>
      </div>

      <!-- Show weekends + select all row -->
      <div class="shrink-0 flex items-center justify-between border-t border-b py-2">
        <div class="flex items-center gap-2">
          <Checkbox
            id="show-weekends"
            v-model="showWeekends"
          />
          <Label for="show-weekends" class="text-xs text-muted-foreground cursor-pointer">
            {{ t('copyDay.showWeekends') }}
          </Label>
        </div>
        <button
          type="button"
          class="text-xs text-muted-foreground hover:text-foreground transition-colors"
          @click="toggleAll"
        >
          {{ allChecked ? t('copyDay.deselectAll') : t('copyDay.selectAll') }}
        </button>
      </div>

      <!-- Day list -->
      <div class="flex-1 overflow-y-auto min-h-0">
        <div v-if="monthDays.length === 0" class="py-8 text-center text-sm text-muted-foreground">
          {{ t('copyDay.noDays') }}
        </div>
        <div
          v-for="day in monthDays"
          :key="day.date"
          class="flex items-center gap-3 px-1 py-1.5 rounded-md"
          :class="[
            day.isSource
              ? 'opacity-40 cursor-default'
              : props.nonWorkingDayMap.has(day.date)
                ? 'bg-rose-100 dark:bg-rose-950/40 opacity-60 hover:opacity-80 cursor-pointer'
                : isWeekend(day.iso)
                  ? 'bg-muted opacity-60 hover:opacity-80 cursor-pointer'
                  : 'hover:bg-muted/50 cursor-pointer',
          ]"
          @click="!day.isSource && toggle(day.date)"
        >
          <Checkbox
            v-if="!day.isSource"
            :model-value="selectedDates.has(day.date)"
            @update:model-value="toggle(day.date)"
            @click.stop
          />
          <div v-else class="size-4 shrink-0" />
          <div class="flex-1 flex items-center justify-between select-none">
            <span class="text-sm capitalize">
              {{ formatWeekdayShort(day.iso) }}, {{ day.iso.getDate() }}
            </span>
            <span v-if="day.isSource" class="text-xs text-muted-foreground italic">{{ t('copyDay.sourceDay') }}</span>
            <span v-else-if="props.nonWorkingDayMap.has(day.date)" class="text-xs text-muted-foreground italic">
              {{ props.nonWorkingDayMap.get(day.date) }}
            </span>
            <span v-else-if="dayMinutes(day.date) > 0" class="text-xs text-amber-600 dark:text-amber-400 tabular-nums">
              {{ minutesToHm(dayMinutes(day.date)) }}
            </span>
          </div>
        </div>
      </div>

      <!-- Warning -->
      <div
        v-if="daysWithExisting.length > 0 && selectedDates.size > 0"
        class="shrink-0 flex items-start gap-2 rounded-md bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 px-3 py-2 text-xs text-amber-700 dark:text-amber-300"
      >
        <AlertCircle class="h-3.5 w-3.5 mt-0.5 shrink-0" />
        <span>{{ t('copyDay.warning', daysWithExisting.length, { count: daysWithExisting.length }) }}</span>
      </div>

      <DialogFooter class="shrink-0">
        <Button variant="outline" @click="emit('update:open', false)">{{ t('common.cancel') }}</Button>
        <Button
          :disabled="selectedDates.size === 0 || copying"
          @click="handleCopy"
        >
          {{ copying ? t('copyDay.copying') : t('copyDay.copy', selectedDates.size, { count: selectedDates.size }) }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
