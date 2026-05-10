<script setup lang="ts">
import { ref, computed } from 'vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import AppLayout from '@/components/layout/AppLayout.vue'
import YearHeatmap from '@/components/year/YearHeatmap.vue'
import { useAuthStore } from '@/stores/auth'
import { useYearOverview } from '@/composables/useYearOverview'
import { minutesToHm, toIsoDate } from '@/lib/format'
import { leaveColorByKey, leaveColorFor, LEAVE_COLOR_ORDER, HOLIDAY_SWATCH } from '@/lib/leaveColors'
import { useI18n } from 'vue-i18n'

const auth = useAuthStore()
const { t } = useI18n()

const now = new Date()
const year = ref(now.getFullYear())

const { query } = useYearOverview(year)

const contractedMinutes = computed(() =>
  query.data.value?.contracted_minutes ?? Math.round((auth.user?.contracted_hours ?? 7.5) * 60),
)

const yearStart = computed(() => new Date(year.value, 0, 1))
const yearEnd = computed(() => new Date(year.value, 11, 31))

const quarters = computed(() => [
  { start: new Date(year.value, 0, 1),  end: new Date(year.value, 2, 31) },
  { start: new Date(year.value, 3, 1),  end: new Date(year.value, 5, 30) },
  { start: new Date(year.value, 6, 1),  end: new Date(year.value, 8, 30) },
  { start: new Date(year.value, 9, 1),  end: new Date(year.value, 11, 31) },
])

const stats = computed(() => {
  const data = query.data.value
  if (!data) return null

  const today = toIsoDate(new Date())
  let workDaysLogged = 0
  let totalMinutes = 0
  let missingDays = 0
  let holidayCount = 0
  const leaveByName = new Map<string, number>()

  const cursor = new Date(yearStart.value)
  while (cursor <= yearEnd.value) {
    const date = toIsoDate(cursor)
    const dow = cursor.getDay()
    const isWeekend = dow === 0 || dow === 6
    const info = data.days[date]

    if (date <= today && (!data.first_activity_date || date >= data.first_activity_date)) {
      if (info?.non_working) {
        holidayCount++
      } else if (info?.leave) {
        leaveByName.set(info.leave, (leaveByName.get(info.leave) ?? 0) + 1)
      } else if (!isWeekend) {
        if ((info?.minutes ?? 0) > 0) {
          workDaysLogged++
          totalMinutes += info!.minutes
        } else {
          missingDays++
        }
      }
    }

    cursor.setDate(cursor.getDate() + 1)
  }

  return { workDaysLogged, totalMinutes, missingDays, holidayCount, leaveByName }
})

const leaveTypeStats = computed(() => {
  if (!stats.value) return []
  const entries = Array.from(stats.value.leaveByName.entries()).map(([name, count]) => ({
    name,
    count,
    color: leaveColorFor(name),
  }))
  return entries.sort((a, b) => {
    const ai = LEAVE_COLOR_ORDER.findIndex((k) => k === a.color.key)
    const bi = LEAVE_COLOR_ORDER.findIndex((k) => k === b.color.key)
    const ax = ai === -1 ? Number.MAX_SAFE_INTEGER : ai
    const bx = bi === -1 ? Number.MAX_SAFE_INTEGER : bi
    if (ax !== bx) return ax - bx
    return a.name.localeCompare(b.name)
  })
})

const legendChips = computed(() => [
  { key: 'full', label: t('year.legendFull'), swatches: ['bg-emerald-500'] },
  {
    key: 'partial',
    label: t('year.legendPartial'),
    swatches: [
      'bg-orange-200 dark:bg-orange-900',
      'bg-orange-300 dark:bg-orange-700',
      'bg-orange-400 dark:bg-orange-500',
    ],
  },
  { key: 'missing', label: t('year.legendMissing'), swatches: ['bg-red-400/80 dark:bg-red-600/70'] },
  ...LEAVE_COLOR_ORDER.map((k) => {
    const c = leaveColorByKey(k)
    return { key: k as string, label: t(c.legendKey), swatches: [c.swatch] }
  }),
  { key: 'holiday', label: t('year.legendHoliday'), swatches: [HOLIDAY_SWATCH] },
  { key: 'weekend', label: t('year.legendWeekend'), swatches: ['bg-muted/40'] },
])

const selectedLegend = ref<string | null>(null)
const toggleLegend = (key: string) => {
  selectedLegend.value = selectedLegend.value === key ? null : key
}

const isCurrentYear = computed(() => year.value === now.getFullYear())
</script>

<template>
  <AppLayout>
    <div class="max-w-6xl mx-auto">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold">{{ t('year.title') }}</h1>
        <div class="flex items-center gap-1">
          <Button
            v-if="!isCurrentYear"
            variant="outline"
            size="sm"
            class="mr-2"
            @click="year = now.getFullYear()"
          >
            {{ t('year.thisYear') }}
          </Button>
          <Button variant="ghost" size="icon" @click="year--" :aria-label="t('year.prevYear')">
            <ChevronLeft class="size-4" />
          </Button>
          <div class="w-16 text-center font-semibold text-sm">{{ year }}</div>
          <Button variant="ghost" size="icon" @click="year++" :aria-label="t('year.nextYear')">
            <ChevronRight class="size-4" />
          </Button>
        </div>
      </div>

      <Skeleton v-if="query.isPending.value" class="h-32 w-full mb-6" />

      <div v-else-if="query.data.value" class="space-y-6">
        <div class="bg-card border rounded-lg p-4 sm:p-6 overflow-x-auto">
          <div class="hidden sm:block">
            <YearHeatmap
              :overview="query.data.value"
              :range-start="yearStart"
              :range-end="yearEnd"
              :contracted-minutes="contractedMinutes"
              :show-month-labels="true"
              :highlighted-key="selectedLegend"
            />
          </div>
          <div class="sm:hidden space-y-4">
            <div v-for="(q, i) in quarters" :key="i">
              <div class="text-xs text-muted-foreground mb-1">Q{{ i + 1 }}</div>
              <YearHeatmap
                :overview="query.data.value"
                :range-start="q.start"
                :range-end="q.end"
                :contracted-minutes="contractedMinutes"
                :show-month-labels="true"
                :highlighted-key="selectedLegend"
              />
            </div>
          </div>
        </div>

        <div class="bg-card border rounded-lg p-4 sm:p-6">
          <div class="flex items-center justify-between mb-3">
            <div class="text-sm font-medium">{{ t('year.legend') }}</div>
            <button
              v-if="selectedLegend"
              type="button"
              class="text-xs text-muted-foreground hover:text-foreground transition cursor-pointer"
              @click="selectedLegend = null"
            >
              {{ t('year.legendClear') }}
            </button>
          </div>
          <div class="flex flex-wrap gap-x-2 gap-y-1.5 text-xs">
            <button
              v-for="chip in legendChips"
              :key="chip.key"
              type="button"
              class="flex items-center gap-2 rounded px-1.5 py-1 transition cursor-pointer"
              :class="[
                selectedLegend === chip.key
                  ? 'ring-1 ring-foreground/40 bg-muted/60'
                  : 'hover:bg-muted/50',
                selectedLegend && selectedLegend !== chip.key ? 'opacity-50' : '',
              ]"
              @click="toggleLegend(chip.key)"
            >
              <span class="flex items-center gap-1">
                <span
                  v-for="(sw, i) in chip.swatches"
                  :key="i"
                  class="size-3 rounded-[2px]"
                  :class="sw"
                ></span>
              </span>
              <span>{{ chip.label }}</span>
            </button>
          </div>
        </div>

        <div v-if="stats" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
          <div class="bg-card border rounded-lg p-4">
            <div class="text-xs text-muted-foreground">{{ t('year.statsLogged') }}</div>
            <div class="text-xl font-semibold mt-1">{{ stats.workDaysLogged }}</div>
            <div class="text-xs text-muted-foreground mt-1">{{ minutesToHm(stats.totalMinutes) }}</div>
          </div>
          <div class="bg-card border rounded-lg p-4">
            <div class="text-xs text-muted-foreground">{{ t('year.statsMissing') }}</div>
            <div class="text-xl font-semibold mt-1 text-red-500">{{ stats.missingDays }}</div>
          </div>
          <div
            v-for="lt in leaveTypeStats"
            :key="lt.name"
            class="bg-card border rounded-lg p-4"
          >
            <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
              <span class="size-2.5 rounded-[2px]" :class="lt.color.swatch"></span>
              <span>{{ lt.name }}</span>
            </div>
            <div class="text-xl font-semibold mt-1">{{ lt.count }}</div>
          </div>
          <div class="bg-card border rounded-lg p-4">
            <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
              <span class="size-2.5 rounded-[2px]" :class="HOLIDAY_SWATCH"></span>
              <span>{{ t('year.statsHolidays') }}</span>
            </div>
            <div class="text-xl font-semibold mt-1">{{ stats.holidayCount }}</div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
