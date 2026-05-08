<script setup lang="ts">
import { ref, computed } from 'vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import AppLayout from '@/components/layout/AppLayout.vue'
import { useAuthStore } from '@/stores/auth'
import { useTimeEntries } from '@/composables/useTimeEntries'
import { useNonWorkingDays } from '@/composables/useNonWorkingDays'
import { minutesToHm, toIsoDate, timeToOffset, formatMonthYear, formatWeekdayShort } from '@/lib/format'
import type { StoreTimeEntryPayload, UpdateTimeEntryPayload } from '@/types'
import DaySheet from '@/components/time/DaySheet.vue'
import { useI18n } from 'vue-i18n'

const auth = useAuthStore()
const { t, locale } = useI18n()

const now = new Date()
const year = ref(now.getFullYear())
const month = ref(now.getMonth() + 1)

const { query, entriesByDate, storeMutation, updateMutation, destroyMutation, copyDayMutation } = useTimeEntries(year, month)
const { query: nwdQuery, nonWorkingDaySet } = useNonWorkingDays(year)

const nonWorkingDayMap = computed(() => {
  const map = new Map<string, string>()
  for (const d of nwdQuery.data.value ?? []) map.set(d.date, d.name)
  return map
})

const monthLabel = computed(() => formatMonthYear(year.value, month.value))

const days = computed(() => {
  const result: { date: string; iso: Date }[] = []
  const d = new Date(year.value, month.value - 1, 1)
  while (d.getMonth() === month.value - 1) {
    result.push({ date: toIsoDate(d), iso: new Date(d) })
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
const isNonWorking = (date: string) => nonWorkingDaySet.value.has(date)

const timeEntryMode = computed(() => auth.user?.time_entry_mode ?? 'range')

const contractedMinutes = computed(() =>
  Math.round((auth.user?.contracted_hours ?? 7.5) * 60),
)

const dayMinutes = (date: string) =>
  (entriesByDate.value.get(date) ?? []).reduce(
    (sum: number, e) => sum + e.duration_minutes,
    0,
  )

const dayStatus = (date: string) => {
  const entries = entriesByDate.value.get(date)
  if (!entries || entries.length === 0) return 'empty'
  const mins = dayMinutes(date)
  return mins >= contractedMinutes.value ? 'met' : 'partial'
}

const isDimmed = (date: string, iso: Date) => isWeekend(iso) || isNonWorking(date)

const monthExpectedMinutes = computed(() =>
  days.value.filter(({ date, iso }) => !isWeekend(iso) && !isNonWorking(date)).length * contractedMinutes.value,
)

const monthLoggedMinutes = computed(() =>
  days.value.reduce((sum, { date }) => sum + dayMinutes(date), 0),
)

const monthProgress = computed(() =>
  monthExpectedMinutes.value > 0
    ? Math.min(1, monthLoggedMinutes.value / monthExpectedMinutes.value)
    : 0,
)

const selectedDate = ref<string | null>(null)

const openDay = (date: string) => { selectedDate.value = date }
const closeSheet = () => { selectedDate.value = null }

const sheetEntries = computed(() =>
  selectedDate.value ? (entriesByDate.value.get(selectedDate.value) ?? []) : [],
)

const handleStore = (payload: StoreTimeEntryPayload) => storeMutation.mutateAsync(payload)
const handleUpdate = (id: number, payload: UpdateTimeEntryPayload) =>
  updateMutation.mutateAsync({ id, payload })
const handleDestroy = (id: number) => destroyMutation.mutateAsync(id)
const handleCopyDay = (entries: StoreTimeEntryPayload[]) =>
  copyDayMutation.mutateAsync({ entries })

const isToday = (date: string) => date === toIsoDate(now)

// Force re-render of date formatting when locale changes
const weekdayShort = (iso: Date) => formatWeekdayShort(iso)
</script>

<template>
  <AppLayout full-bleed>
  <div class="flex flex-col h-full">
    <div class="flex items-center justify-between px-3 sm:px-6 py-4 border-b">
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

    <div class="px-3 sm:px-6 py-3 border-b space-y-2">
      <div class="flex items-baseline justify-between text-sm">
        <span class="text-muted-foreground">
          <span class="font-semibold text-foreground tabular-nums">{{ minutesToHm(monthLoggedMinutes) }}</span>
          {{ t('time.logged') }}
          <span class="font-semibold text-foreground tabular-nums">{{ minutesToHm(monthExpectedMinutes) }}</span>
          {{ t('time.expected') }}
        </span>
        <span
          class="tabular-nums font-semibold text-sm"
          :class="monthLoggedMinutes >= monthExpectedMinutes
            ? 'text-emerald-600 dark:text-emerald-400'
            : 'text-muted-foreground'"
        >
          {{ monthLoggedMinutes >= monthExpectedMinutes ? '+' : '' }}{{ minutesToHm(Math.abs(monthExpectedMinutes - monthLoggedMinutes)) }}
          {{ monthLoggedMinutes >= monthExpectedMinutes ? t('time.over') : t('time.remaining') }}
        </span>
      </div>
      <div class="h-1.5 rounded-full bg-muted overflow-hidden">
        <div
          class="h-full rounded-full transition-all duration-500"
          :class="monthProgress >= 1 ? 'bg-emerald-500' : 'bg-primary'"
          :style="{ width: `${monthProgress * 100}%` }"
        />
      </div>
    </div>

    <!-- Time ruler (range mode only) -->
    <div v-if="timeEntryMode === 'range'" class="hidden sm:flex items-end px-6 pt-2 border-b">
      <div class="w-10 shrink-0 mr-4" />
      <div class="w-28 shrink-0 mr-4" />
      <div class="flex-1 relative h-4 min-w-0">
        <template v-for="h in [5,6,7,8,9,10,11,12,13,14,15,16,17,18]" :key="h">
          <span
            class="absolute text-[10px] text-muted-foreground/60 tabular-nums leading-none"
            :style="{ left: `${((h - 5) / 13) * 100}%`, transform: 'translateX(-50%)' }"
          >{{ h }}h</span>
        </template>
      </div>
    </div>

    <div class="flex-1 overflow-y-auto relative">
      <!-- Continuous vertical grid lines across all rows (range mode only, desktop only) -->
      <div v-if="timeEntryMode === 'range'" class="hidden sm:block absolute inset-0 pointer-events-none" aria-hidden="true" style="left: calc(1.5rem + 2.5rem + 1rem + 7rem + 1rem); right: 1.5rem;">
        <template v-for="h in [6,7,8,9,10,11,12,13,14,15,16,17]" :key="h">
          <div
            class="absolute inset-y-0 w-px bg-border"
            :style="{ left: `${((h - 5) / 13) * 100}%` }"
          />
        </template>
      </div>

      <template v-if="query.isLoading.value">
        <div class="px-3 sm:px-6 py-3 space-y-2">
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
            class="relative w-full flex items-center gap-3 sm:gap-4 px-3 sm:px-6 py-3 text-left transition-colors"
            :class="[
              isNonWorking(day.date) ? 'bg-rose-100 dark:bg-rose-950/40' :
              isWeekend(day.iso) ? 'bg-muted hover:bg-muted' :
              'hover:bg-muted/50',
              isDimmed(day.date, day.iso) ? 'opacity-60' : '',
            ]"
            @click="openDay(day.date)"
          >
            <div class="w-10 flex-shrink-0 text-center">
              <p class="text-xs text-muted-foreground uppercase">
                {{ weekdayShort(day.iso) }}
              </p>
              <p
                class="text-sm font-semibold leading-tight"
                :class="isToday(day.date) ? 'text-primary' : ''"
              >
                {{ day.iso.getDate() }}
              </p>
            </div>

            <div class="w-16 sm:w-28 flex-shrink-0 flex items-baseline gap-1">
              <template v-if="dayStatus(day.date) !== 'empty'">
                <span
                  class="text-sm font-bold tabular-nums"
                  :class="dayStatus(day.date) === 'met'
                    ? 'text-emerald-600 dark:text-emerald-400'
                    : 'text-amber-600 dark:text-amber-400'"
                >{{ minutesToHm(dayMinutes(day.date)) }}</span>
                <span class="hidden sm:inline text-xs text-muted-foreground">/ {{ minutesToHm(contractedMinutes) }}</span>
              </template>
            </div>

            <div class="flex-1 min-w-0 relative">
              <div class="relative h-8 rounded bg-muted/40 overflow-hidden">
                <!-- Range mode: time-positioned blocks -->
                <template v-if="dayStatus(day.date) !== 'empty' && timeEntryMode === 'range'">
                  <div
                    v-for="entry in entriesByDate.get(day.date)"
                    :key="entry.id"
                    class="absolute inset-y-0 rounded-sm overflow-hidden flex flex-col justify-center px-1.5"
                    :style="{
                      left: `${timeToOffset(entry.started_at ?? '00:00') * 100}%`,
                      width: `${(timeToOffset(entry.ended_at ?? '00:00') - timeToOffset(entry.started_at ?? '00:00')) * 100}%`,
                      backgroundColor: entry.activity.color ?? 'hsl(var(--primary))',
                      opacity: '0.85',
                    }"
                    :title="`${entry.activity.name} ${entry.started_at}–${entry.ended_at}`"
                  >
                    <span class="text-white text-xs font-medium leading-tight truncate">{{ entry.activity.name }}</span>
                    <span class="text-white/80 text-[10px] leading-tight truncate">{{ minutesToHm(entry.duration_minutes) }}</span>
                  </div>
                </template>
                <!-- Duration mode: proportional stacked bars -->
                <div v-else-if="dayStatus(day.date) !== 'empty' && timeEntryMode === 'duration'" class="absolute inset-0 flex">
                  <div
                    v-for="entry in entriesByDate.get(day.date)"
                    :key="entry.id"
                    class="h-full flex items-center justify-center overflow-hidden px-1"
                    :style="{
                      width: `${(entry.duration_minutes / dayMinutes(day.date)) * 100}%`,
                      backgroundColor: entry.activity.color ?? 'hsl(var(--primary))',
                      opacity: '0.85',
                    }"
                    :title="`${entry.activity.name} – ${minutesToHm(entry.duration_minutes)}`"
                  >
                    <span class="text-white text-[10px] font-medium leading-tight truncate">{{ entry.activity.name }}</span>
                  </div>
                </div>
                <span
                  v-else-if="isNonWorking(day.date)"
                  class="absolute inset-0 flex items-center px-2 text-xs text-muted-foreground italic"
                >{{ nonWorkingDayMap.get(day.date) }}</span>
              </div>
            </div>

          </button>
        </div>
      </template>
    </div>

    <DaySheet
      :open="selectedDate !== null"
      :date="selectedDate ?? ''"
      :time-entry-mode="timeEntryMode"
      :entries="sheetEntries"
      :contracted-minutes="contractedMinutes"
      :holiday-name="selectedDate ? nonWorkingDayMap.get(selectedDate) : undefined"
      :entries-by-date="entriesByDate"
      :on-store="handleStore"
      :on-update="handleUpdate"
      :on-destroy="handleDestroy"
      :on-copy-day="handleCopyDay"
      @update:open="(v) => { if (!v) closeSheet() }"
    />
  </div>
  </AppLayout>
</template>
