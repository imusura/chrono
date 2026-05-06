<script setup lang="ts">
import { ref, computed } from 'vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import AppLayout from '@/components/layout/AppLayout.vue'
import { useAuthStore } from '@/stores/auth'
import { useTimeEntries } from '@/composables/useTimeEntries'
import { minutesToHm, entryMinutes } from '@/lib/format'
import type { StoreTimeEntryPayload, UpdateTimeEntryPayload } from '@/types'
import DaySheet from '@/components/time/DaySheet.vue'

const auth = useAuthStore()

const now = new Date()
const year = ref(now.getFullYear())
const month = ref(now.getMonth() + 1)

const { query, entriesByDate, storeMutation, updateMutation, destroyMutation } = useTimeEntries(year, month)

const monthLabel = computed(() =>
  new Date(year.value, month.value - 1, 1).toLocaleDateString('hr-HR', {
    month: 'long',
    year: 'numeric',
  }),
)

const days = computed(() => {
  const result: { date: string; iso: Date }[] = []
  const d = new Date(year.value, month.value - 1, 1)
  while (d.getMonth() === month.value - 1) {
    result.push({ date: d.toISOString().slice(0, 10), iso: new Date(d) })
    d.setDate(d.getDate() + 1)
  }
  return result
})

const prevMonth = () => {
  if (month.value === 1) { year.value--; month.value = 12 } else { month.value-- }
}
const nextMonth = () => {
  if (month.value === 12) { year.value++; month.value = 1 } else { month.value++ }
}

const isWeekend = (d: Date) => d.getDay() === 0 || d.getDay() === 6

const contractedMinutes = computed(() =>
  Math.round((auth.user?.contracted_hours ?? 7.5) * 60),
)

const dayMinutes = (date: string) =>
  (entriesByDate.value.get(date) ?? []).reduce(
    (sum, e) => sum + entryMinutes(e.started_at, e.ended_at),
    0,
  )

const dayStatus = (date: string) => {
  const entries = entriesByDate.value.get(date)
  if (!entries || entries.length === 0) return 'empty'
  const mins = dayMinutes(date)
  return mins >= contractedMinutes.value ? 'met' : 'partial'
}

const selectedDate = ref<string | null>(null)

const openDay = (date: string) => { selectedDate.value = date }
const closeSheet = () => { selectedDate.value = null }

const sheetEntries = computed(() =>
  selectedDate.value ? (entriesByDate.value.get(selectedDate.value) ?? []) : [],
)

const handleStore = (payload: StoreTimeEntryPayload) => storeMutation.mutate(payload)
const handleUpdate = (id: number, payload: UpdateTimeEntryPayload) =>
  updateMutation.mutate({ id, payload })
const handleDestroy = (id: number) => destroyMutation.mutate(id)

const isToday = (date: string) => date === now.toISOString().slice(0, 10)
</script>

<template>
  <AppLayout>
  <div class="flex flex-col h-full">
    <div class="flex items-center justify-between px-6 py-4 border-b">
      <h1 class="text-lg font-semibold capitalize">{{ monthLabel }}</h1>
      <div class="flex gap-1">
        <Button variant="outline" size="icon" @click="prevMonth">
          <ChevronLeft class="h-4 w-4" />
        </Button>
        <Button variant="outline" size="icon" @click="nextMonth">
          <ChevronRight class="h-4 w-4" />
        </Button>
      </div>
    </div>

    <div class="flex-1 overflow-y-auto">
      <template v-if="query.isLoading.value">
        <div class="px-6 py-3 space-y-2">
          <Skeleton v-for="i in 20" :key="i" class="h-12 w-full rounded-lg" />
        </div>
      </template>

      <template v-else>
        <div
          v-for="(day, index) in days"
          :key="day.date"
          :class="[index > 0 && day.iso.getDay() === 1 ? 'mt-3' : '']"
        >
          <button
            class="w-full flex items-center gap-4 px-6 py-3 text-left hover:bg-muted/50 transition-colors"
            :class="isWeekend(day.iso) ? 'opacity-50' : ''"
            @click="openDay(day.date)"
          >
            <div class="w-10 flex-shrink-0 text-center">
              <p class="text-xs text-muted-foreground uppercase">
                {{ day.iso.toLocaleDateString('hr-HR', { weekday: 'short' }) }}
              </p>
              <p
                class="text-sm font-semibold leading-tight"
                :class="isToday(day.date) ? 'text-primary' : ''"
              >
                {{ day.iso.getDate() }}
              </p>
            </div>

            <div class="flex-1 min-w-0">
              <div v-if="dayStatus(day.date) !== 'empty'" class="flex flex-wrap gap-1">
                <span
                  v-for="entry in entriesByDate.get(day.date)"
                  :key="entry.id"
                  class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full border"
                  :style="entry.activity.color ? { borderColor: entry.activity.color, color: entry.activity.color } : {}"
                >
                  {{ entry.activity.name }}
                </span>
              </div>
              <p v-else class="text-sm text-muted-foreground">—</p>
            </div>

            <div class="flex-shrink-0 text-right">
              <template v-if="dayStatus(day.date) !== 'empty'">
                <p
                  class="text-sm font-semibold tabular-nums"
                  :class="dayStatus(day.date) === 'met' ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'"
                >
                  {{ minutesToHm(dayMinutes(day.date)) }}
                </p>
                <p class="text-xs text-muted-foreground">/ {{ minutesToHm(contractedMinutes) }}</p>
              </template>
            </div>
          </button>
        </div>
      </template>
    </div>

    <DaySheet
      :open="selectedDate !== null"
      :date="selectedDate ?? ''"
      :entries="sheetEntries"
      :contracted-minutes="contractedMinutes"
      @update:open="(v) => { if (!v) closeSheet() }"
      @store="handleStore"
      @update="handleUpdate"
      @destroy="handleDestroy"
    />
  </div>
  </AppLayout>
</template>
