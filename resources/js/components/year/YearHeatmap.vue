<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { toIsoDate, formatDayLong, minutesToHm } from '@/lib/format'
import { leaveColorFor, HOLIDAY_SWATCH } from '@/lib/leaveColors'
import type { YearOverview, YearDay } from '@/types'
import { useI18n } from 'vue-i18n'

const props = defineProps<{
  overview: YearOverview
  rangeStart: Date
  rangeEnd: Date
  contractedMinutes: number
  showMonthLabels?: boolean
  highlightedKey?: string | null
}>()

const { t, locale } = useI18n()
const router = useRouter()
const today = toIsoDate(new Date())

const weeks = computed(() => {
  const firstDay = new Date(props.rangeStart)
  while (firstDay.getDay() !== 1) {
    firstDay.setDate(firstDay.getDate() - 1)
  }

  const lastDay = new Date(props.rangeEnd)
  while (lastDay.getDay() !== 0) {
    lastDay.setDate(lastDay.getDate() + 1)
  }

  const result: Array<Array<{ date: string; iso: Date; inRange: boolean }>> = []
  const cursor = new Date(firstDay)

  while (cursor <= lastDay) {
    const week: Array<{ date: string; iso: Date; inRange: boolean }> = []
    for (let i = 0; i < 7; i++) {
      const inRange = cursor >= props.rangeStart && cursor <= props.rangeEnd
      week.push({
        date: toIsoDate(cursor),
        iso: new Date(cursor),
        inRange,
      })
      cursor.setDate(cursor.getDate() + 1)
    }
    result.push(week)
  }

  return result
})

const flatCells = computed(() => weeks.value.flat())

const gridStyle = computed(() => ({
  gridTemplateColumns: `auto repeat(${weeks.value.length}, minmax(0, 1fr))`,
}))

const monthLabels = computed(() => {
  const labels: Array<{ index: number; label: string }> = []
  let lastMonth = -1
  weeks.value.forEach((week, idx) => {
    const middayOfWeek = week[3]?.iso ?? week[0].iso
    if (middayOfWeek.getMonth() !== lastMonth) {
      lastMonth = middayOfWeek.getMonth()
      const label = middayOfWeek.toLocaleDateString(locale.value, { month: 'short' })
      labels.push({ index: idx, label })
    }
  })
  return labels
})

const isWeekend = (iso: Date) => iso.getDay() === 0 || iso.getDay() === 6

const dayInfo = (date: string): YearDay | null => props.overview.days[date] ?? null

const cellState = (date: string, iso: Date, inRange: boolean) => {
  if (!inRange) return 'outside'
  if (props.overview.first_activity_date && date < props.overview.first_activity_date) return 'pre-activity'

  const info = dayInfo(date)
  if (info?.non_working) return 'holiday'
  if (info?.leave) return 'leave'
  if (isWeekend(iso)) return 'weekend'

  if (date > today) return 'future'

  const minutes = info?.minutes ?? 0
  if (minutes === 0) return 'missing'
  if (minutes >= props.contractedMinutes) return 'full'
  const ratio = minutes / props.contractedMinutes
  if (ratio >= 0.75) return 'partial-3'
  if (ratio >= 0.5) return 'partial-2'
  return 'partial-1'
}

const legendKeyForCell = (state: ReturnType<typeof cellState>, date: string): string | null => {
  if (state === 'full') return 'full'
  if (state === 'partial-1' || state === 'partial-2' || state === 'partial-3') return 'partial'
  if (state === 'missing') return 'missing'
  if (state === 'holiday') return 'holiday'
  if (state === 'weekend') return 'weekend'
  if (state === 'leave') return leaveColorFor(dayInfo(date)?.leave).key
  return null
}

const cellClass = (state: ReturnType<typeof cellState>, date: string) => {
  switch (state) {
    case 'outside':
    case 'future':
    case 'pre-activity':
      return 'bg-transparent'
    case 'weekend':
      return 'bg-muted/40'
    case 'holiday':
      return HOLIDAY_SWATCH
    case 'leave':
      return leaveColorFor(dayInfo(date)?.leave).swatch
    case 'missing':
      return 'bg-red-400/80 dark:bg-red-600/70'
    case 'partial-1':
      return 'bg-orange-200 dark:bg-orange-900'
    case 'partial-2':
      return 'bg-orange-300 dark:bg-orange-700'
    case 'partial-3':
      return 'bg-orange-400 dark:bg-orange-500'
    case 'full':
      return 'bg-emerald-500 dark:bg-emerald-500'
  }
}

const cellTitle = (date: string, iso: Date, inRange: boolean): string => {
  if (!inRange) return ''
  const info = dayInfo(date)
  const lines: string[] = [formatDayLong(date)]
  if (info?.non_working) {
    lines.push(`${t('year.holiday')}: ${info.non_working}`)
  } else if (info?.leave) {
    lines.push(info.leave)
  } else if (isWeekend(iso)) {
    lines.push(t('year.weekend'))
  } else if (date > today) {
    return ''
  } else {
    const minutes = info?.minutes ?? 0
    if (minutes > 0) {
      lines.push(`${minutesToHm(minutes)} / ${minutesToHm(props.contractedMinutes)}`)
    } else if (props.overview.first_activity_date && date < props.overview.first_activity_date) {
      // skip
    } else {
      lines.push(t('year.noLog'))
    }
  }
  return lines.join('\n')
}

const handleCellClick = (date: string, inRange: boolean) => {
  if (!inRange) return
  if (date > today) return
  if (props.overview.first_activity_date && date < props.overview.first_activity_date) return

  const [y, m] = date.split('-').map(Number)
  router.push({ name: 'time', query: { year: String(y), month: String(m), day: date } })
}
</script>

<template>
  <div class="grid gap-[2px] text-xs w-full" :style="gridStyle">
    <div></div>
    <template v-if="showMonthLabels">
      <div
        v-for="(_, idx) in weeks"
        :key="`m-${idx}`"
        class="text-[10px] text-muted-foreground leading-none whitespace-nowrap"
        :style="{ gridRow: 1, gridColumn: idx + 2 }"
      >
        <span v-if="monthLabels.find((l) => l.index === idx)">
          {{ monthLabels.find((l) => l.index === idx)?.label }}
        </span>
      </div>
    </template>

    <div
      v-for="(key, idx) in ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun']"
      :key="key"
      class="text-[10px] text-muted-foreground leading-none flex items-center justify-end pr-1"
      :style="{ gridRow: (showMonthLabels ? 2 : 1) + idx, gridColumn: 1 }"
    >{{ t(`year.weekdays.${key}`) }}</div>

    <button
      v-for="(day, cellIdx) in flatCells"
      :key="day.date"
      type="button"
      :title="cellTitle(day.date, day.iso, day.inRange)"
      :class="[
        'aspect-square rounded-[2px] transition-all',
        cellClass(cellState(day.date, day.iso, day.inRange), day.date),
        highlightedKey && legendKeyForCell(cellState(day.date, day.iso, day.inRange), day.date) !== highlightedKey
          ? 'opacity-15'
          : '',
        day.date === today ? 'ring-1 ring-foreground/60' : '',
        day.inRange && day.date <= today ? 'cursor-pointer hover:ring-1 hover:ring-foreground/40' : '',
      ]"
      :style="{
        gridRow: (showMonthLabels ? 2 : 1) + (cellIdx % 7),
        gridColumn: Math.floor(cellIdx / 7) + 2,
      }"
      :disabled="!day.inRange || day.date > today"
      @click="handleCellClick(day.date, day.inRange)"
    ></button>
  </div>
</template>
