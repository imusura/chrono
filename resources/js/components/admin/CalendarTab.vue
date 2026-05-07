<script setup lang="ts">
import { computed, ref } from 'vue'
import { ChevronLeft, ChevronRight, RefreshCw, X } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover'
import {
  AlertDialog, AlertDialogAction, AlertDialogCancel,
  AlertDialogContent, AlertDialogDescription, AlertDialogFooter,
  AlertDialogHeader, AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import { useNonWorkingDays } from '@/composables/useNonWorkingDays'
import { toIsoDate, formatMonthYear } from '@/lib/format'
import type { NonWorkingDay } from '@/types'
import type { MaybeRef } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps<{
  organisationId?: number
}>()

const { t } = useI18n()
const orgIdRef = computed(() => props.organisationId)

const now = new Date()
const year = ref(now.getFullYear())
const month = ref(now.getMonth()) // 0-indexed for Date

const { query, storeMutation, destroyMutation, syncMutation } = useNonWorkingDays(year, orgIdRef as MaybeRef<number | undefined>)

const syncHolidays = () => syncMutation.mutate(year.value)

const monthLabel = computed(() => formatMonthYear(year.value, month.value + 1))

const prevMonth = () => {
  if (month.value === 0) { year.value--; month.value = 11 } else { month.value-- }
}
const nextMonth = () => {
  if (month.value === 11) { year.value++; month.value = 0 } else { month.value++ }
}

const calendarDays = computed(() => {
  const firstDay = new Date(year.value, month.value, 1)
  const lastDay = new Date(year.value, month.value + 1, 0)

  const startOffset = (firstDay.getDay() + 6) % 7
  const days: (Date | null)[] = []

  for (let i = 0; i < startOffset; i++) days.push(null)
  for (let d = 1; d <= lastDay.getDate(); d++) days.push(new Date(year.value, month.value, d))

  while (days.length % 7 !== 0) days.push(null)
  return days
})

const dayMap = computed(() => {
  const map = new Map<string, NonWorkingDay>()
  for (const d of query.data.value ?? []) map.set(d.date, d)
  return map
})

const isWeekend = (d: Date) => d.getDay() === 0 || d.getDay() === 6
const isCurrentMonth = (d: Date) => d.getMonth() === month.value

const openDate = ref<string | null>(null)
const popoverName = ref('')
const editingId = ref<number | null>(null)
const deleteDialogOpen = ref(false)
const pendingDeleteId = ref<number | null>(null)

const openPopover = (d: Date) => {
  const iso = toIsoDate(d)
  const existing = dayMap.value.get(iso)
  if (existing?.is_public) {
    openDate.value = iso
    popoverName.value = existing.name
    editingId.value = null
    return
  }
  openDate.value = iso
  popoverName.value = existing?.name ?? ''
  editingId.value = existing?.id ?? null
}

const closePopover = () => {
  openDate.value = null
  popoverName.value = ''
  editingId.value = null
}

const saveCustomDay = async () => {
  if (!openDate.value || !popoverName.value.trim()) return
  await storeMutation.mutateAsync({
    date: openDate.value,
    name: popoverName.value.trim(),
    ...(props.organisationId ? { organisation_id: props.organisationId } : {}),
  })
  closePopover()
}

const openDeleteDialog = (id: number) => {
  pendingDeleteId.value = id
  deleteDialogOpen.value = true
}

const confirmDeleteCustomDay = async () => {
  if (pendingDeleteId.value === null) return
  await destroyMutation.mutateAsync(pendingDeleteId.value)
  pendingDeleteId.value = null
  closePopover()
}

const weekdayKeys = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as const
</script>

<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <h2 class="text-sm font-semibold capitalize">{{ monthLabel }}</h2>
      <div class="flex items-center gap-2">
        <Button
          variant="outline"
          size="sm"
          class="h-7 gap-1.5 text-xs"
          :disabled="syncMutation.isPending.value"
          @click="syncHolidays"
        >
          <RefreshCw class="h-3 w-3" :class="{ 'animate-spin': syncMutation.isPending.value }" />
          {{ t('calendar.syncHolidays') }}
        </Button>
        <div class="flex items-center gap-1">
          <Button variant="outline" size="icon" class="h-7 w-7" @click="prevMonth">
            <ChevronLeft class="h-3.5 w-3.5" />
          </Button>
          <Button variant="outline" size="icon" class="h-7 w-7" @click="nextMonth">
            <ChevronRight class="h-3.5 w-3.5" />
          </Button>
        </div>
      </div>
    </div>

    <!-- Legend -->
    <div class="flex items-center gap-4 text-xs text-muted-foreground">
      <span class="flex items-center gap-1.5">
        <span class="h-3 w-3 rounded-sm bg-amber-100 dark:bg-amber-900/40 border border-amber-300 dark:border-amber-700" />
        {{ t('calendar.publicHoliday') }}
      </span>
      <span class="flex items-center gap-1.5">
        <span class="h-3 w-3 rounded-sm bg-blue-100 dark:bg-blue-900/40 border border-blue-300 dark:border-blue-700" />
        {{ t('calendar.customDay') }}
      </span>
    </div>

    <AlertDialog :open="deleteDialogOpen" @update:open="deleteDialogOpen = $event">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>{{ t('calendar.delete.title') }}</AlertDialogTitle>
          <AlertDialogDescription>{{ t('calendar.delete.description') }}</AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>{{ t('calendar.delete.cancel') }}</AlertDialogCancel>
          <AlertDialogAction
            class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
            :disabled="destroyMutation.isPending.value"
            @click="confirmDeleteCustomDay"
          >
            {{ t('calendar.delete.confirm') }}
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>

    <!-- Calendar grid -->
    <div class="rounded-lg border overflow-hidden">
      <!-- Weekday headers -->
      <div class="grid grid-cols-7 border-b bg-muted/40">
        <div
          v-for="key in weekdayKeys"
          :key="key"
          class="py-2 text-center text-xs font-medium text-muted-foreground"
          :class="key === 'Sat' || key === 'Sun' ? 'text-muted-foreground/50' : ''"
        >
          {{ t(`calendar.weekdays.${key}`) }}
        </div>
      </div>

      <!-- Day cells -->
      <div class="grid grid-cols-7">
        <template v-for="(day, idx) in calendarDays" :key="idx">
          <div v-if="!day" class="border-b border-r last:border-r-0 bg-muted/20 min-h-[56px]" />

          <Popover
            v-else
            :open="openDate === toIsoDate(day)"
            @update:open="(v) => { if (!v) closePopover() }"
          >
            <PopoverTrigger as-child>
              <button
                class="w-full min-h-[56px] border-b border-r last:border-r-0 p-1.5 text-left transition-colors relative group"
                :class="[
                  isWeekend(day) ? 'opacity-50' : '',
                  !isCurrentMonth(day) ? 'opacity-30' : '',
                  dayMap.get(toIsoDate(day))?.is_public
                    ? 'bg-amber-50 dark:bg-amber-900/20 hover:bg-amber-100 dark:hover:bg-amber-900/30'
                    : dayMap.get(toIsoDate(day))
                      ? 'bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30'
                      : 'hover:bg-muted/50',
                ]"
                @click="openPopover(day)"
              >
                <span
                  class="text-xs font-medium"
                  :class="toIsoDate(day) === toIsoDate(new Date()) ? 'text-primary font-bold' : ''"
                >
                  {{ day.getDate() }}
                </span>
                <span
                  v-if="dayMap.get(toIsoDate(day))"
                  class="block text-[10px] leading-tight mt-0.5 truncate"
                  :class="dayMap.get(toIsoDate(day))?.is_public
                    ? 'text-amber-700 dark:text-amber-400'
                    : 'text-blue-700 dark:text-blue-400'"
                >
                  {{ dayMap.get(toIsoDate(day))?.name }}
                </span>
              </button>
            </PopoverTrigger>

            <PopoverContent class="w-64 p-3" align="start">
              <template v-if="dayMap.get(toIsoDate(day))?.is_public">
                <p class="text-xs font-medium text-muted-foreground mb-1">{{ t('calendar.publicHoliday') }}</p>
                <p class="text-sm font-semibold">{{ dayMap.get(toIsoDate(day))?.name }}</p>
              </template>

              <template v-else>
                <p class="text-xs font-medium text-muted-foreground mb-2">
                  {{ dayMap.get(toIsoDate(day)) ? t('calendar.customDay') : t('calendar.addNonWorkingDay') }}
                </p>
                <div class="space-y-3">
                  <div class="grid gap-1.5">
                    <Label for="nwd-name" class="text-xs">{{ t('calendar.label') }}</Label>
                    <Input
                      id="nwd-name"
                      v-model="popoverName"
                      class="h-8 text-sm"
                      :placeholder="t('calendar.labelPlaceholder')"
                      @keydown.enter="saveCustomDay"
                    />
                  </div>
                  <div class="flex gap-2">
                    <Button
                      size="sm"
                      class="flex-1 h-8"
                      :disabled="!popoverName.trim() || storeMutation.isPending.value"
                      @click="saveCustomDay"
                    >
                      {{ t('calendar.save') }}
                    </Button>
                    <Button
                      v-if="editingId"
                      size="sm"
                      variant="ghost"
                      class="h-8 px-2 text-destructive hover:text-destructive"
                      :disabled="destroyMutation.isPending.value"
                      @click="openDeleteDialog(editingId!)"
                    >
                      <X class="h-3.5 w-3.5" />
                    </Button>
                  </div>
                </div>
              </template>
            </PopoverContent>
          </Popover>
        </template>
      </div>
    </div>
  </div>
</template>
