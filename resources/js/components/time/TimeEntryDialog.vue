<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { useUserActivities } from '@/composables/useUserActivities'
import { addMinutesToTime, entryMinutes, minutesToHm } from '@/lib/format'
import type { TimeEntry, StoreTimeEntryPayload, UpdateTimeEntryPayload, TimeEntryMode } from '@/types'
import type { AxiosError } from 'axios'
import { useI18n } from 'vue-i18n'

const props = defineProps<{
  open: boolean
  date: string
  timeEntryMode: TimeEntryMode
  entry?: TimeEntry | null
  dayEntries?: TimeEntry[]
  contractedMinutes?: number
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
  save: [payload: StoreTimeEntryPayload | UpdateTimeEntryPayload]
}>()

const { t } = useI18n()
const { data: activities } = useUserActivities()

const activityId = ref<number | null>(null)
const startedAt = ref('')
const endedAt = ref('')
const durationInput = ref('')
const notes = ref('')
const errors = ref<Record<string, string[]>>({})

const isRangeMode = computed(() => props.timeEntryMode === 'range')

const lastEndTime = computed(() => {
  const entries = props.dayEntries ?? []
  if (!entries.length) return null
  return entries.reduce(
    (latest, e) => (e.ended_at ?? '') > latest ? (e.ended_at ?? '') : latest,
    entries[0].ended_at ?? '',
  )
})

const loggedMinutes = computed(() => {
  const entries = props.dayEntries ?? []
  return entries.reduce((sum, e) => {
    if (props.entry && e.id === props.entry.id) return sum
    return sum + e.duration_minutes
  }, 0)
})

const parsedDurationInput = computed((): number => {
  const raw = durationInput.value.trim()
  if (!raw) return 0
  return parseInt(raw, 10) || 0
})

const currentEntryMinutes = computed(() => {
  if (isRangeMode.value) {
    return startedAt.value && endedAt.value && endedAt.value > startedAt.value
      ? entryMinutes(startedAt.value, endedAt.value)
      : 0
  }
  return parsedDurationInput.value
})

const remainingMinutes = computed(() => {
  if (props.contractedMinutes == null) return null
  return Math.max(0, props.contractedMinutes - loggedMinutes.value - currentEntryMinutes.value)
})

const selectedActivityColor = computed(() =>
  activities.value?.find(a => a.id === activityId.value)?.color ?? 'hsl(var(--primary))',
)

const barSegments = computed(() => {
  const total = props.contractedMinutes
  if (!total) return null
  const logged = Math.min(loggedMinutes.value, total)
  const current = Math.min(currentEntryMinutes.value, total - logged)
  return {
    logged: (logged / total) * 100,
    current: (current / total) * 100,
    remaining: ((total - logged - current) / total) * 100,
  }
})

watch(
  () => props.open,
  (open) => {
    if (!open) {
      activityId.value = null
      startedAt.value = ''
      endedAt.value = ''
      durationInput.value = ''
      notes.value = ''
      errors.value = {}
      return
    }
    errors.value = {}
    if (props.entry) {
      activityId.value = props.entry.activity_id
      startedAt.value = props.entry.started_at ?? ''
      endedAt.value = props.entry.ended_at ?? ''
      durationInput.value = props.entry.duration_minutes ? String(props.entry.duration_minutes) : ''
      notes.value = props.entry.notes ?? ''
    } else {
      activityId.value = null
      startedAt.value = isRangeMode.value ? (lastEndTime.value ?? '') : ''
      endedAt.value = ''
      durationInput.value = ''
      notes.value = ''
    }
  },
)

const applyDuration = (mins: number) => {
  if (isRangeMode.value) {
    if (!startedAt.value) return
    endedAt.value = addMinutesToTime(startedAt.value, mins)
  } else {
    durationInput.value = String((parsedDurationInput.value || 0) + mins)
  }
}

const close = () => emit('update:open', false)

const canSubmit = computed(() => {
  if (!activityId.value) return false
  if (isRangeMode.value) return !!startedAt.value && !!endedAt.value
  return parsedDurationInput.value > 0
})

const submit = () => {
  if (!canSubmit.value) return

  if (isRangeMode.value) {
    const base = {
      activity_id: activityId.value!,
      started_at: startedAt.value,
      ended_at: endedAt.value,
      notes: notes.value || null,
    }
    emit('save', props.entry ? base : { ...base, date: props.date })
  } else {
    const base = {
      activity_id: activityId.value!,
      duration_minutes: parsedDurationInput.value,
      notes: notes.value || null,
    }
    emit('save', props.entry ? base : { ...base, date: props.date })
  }
}

const setErrors = (err: AxiosError<{ errors: Record<string, string[]> }>) => {
  errors.value = err.response?.data?.errors ?? {}
}

defineExpose({ setErrors })
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <div class="flex items-baseline justify-between pr-6">
          <DialogTitle>{{ entry ? t('entryDialog.editEntry') : t('entryDialog.addEntry') }}</DialogTitle>
          <span v-if="remainingMinutes != null" class="text-xs text-muted-foreground tabular-nums">
            {{ minutesToHm(remainingMinutes) }} {{ t('entryDialog.left') }}
          </span>
        </div>
        <div v-if="barSegments" class="relative h-2 rounded-full mt-2 bg-border overflow-hidden">
          <div
            class="absolute inset-y-0 left-0 bg-foreground/20"
            :style="{ width: `calc(${barSegments.logged}% + 1px)` }"
          />
          <div
            class="absolute inset-y-0 transition-all duration-500 ease-out"
            :style="{
              left: `calc(${barSegments.logged}% - 1px)`,
              width: `calc(${barSegments.current}% + 1px)`,
              backgroundColor: selectedActivityColor,
            }"
          />
        </div>
      </DialogHeader>

      <div class="grid gap-5 py-2">
        <!-- Activity pill grid -->
        <div class="grid gap-2">
          <Label>{{ t('entryDialog.activity') }}</Label>
          <div v-if="activities?.length" class="flex flex-wrap gap-2">
            <button
              v-for="activity in activities"
              :key="activity.id"
              type="button"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border text-sm font-medium transition-colors"
              :class="activityId === activity.id
                ? 'text-white border-transparent'
                : 'border-border text-muted-foreground hover:text-foreground hover:border-foreground/30'"
              :style="activityId === activity.id ? { backgroundColor: activity.color, borderColor: activity.color } : {}"
              @click="activityId = activity.id"
            >
              <span
                class="h-2 w-2 rounded-full shrink-0"
                :style="{ backgroundColor: activityId === activity.id ? 'rgba(255,255,255,0.7)' : activity.color }"
              />
              {{ activity.name }}
            </button>
          </div>
          <p v-else class="text-xs text-muted-foreground">{{ t('entryDialog.noActivities') }}</p>
          <p v-if="errors.activity_id" class="text-sm text-destructive">{{ errors.activity_id[0] }}</p>
        </div>

        <!-- Range mode: start + end time inputs -->
        <div v-if="isRangeMode" class="grid gap-3">
          <div class="grid grid-cols-2 gap-3">
            <div class="grid gap-1.5">
              <Label for="started_at">{{ t('entryDialog.start') }}</Label>
              <Input id="started_at" v-model="startedAt" type="time" />
              <p v-if="errors.started_at" class="text-sm text-destructive">{{ errors.started_at[0] }}</p>
            </div>
            <div class="grid gap-1.5">
              <Label for="ended_at">{{ t('entryDialog.end') }}</Label>
              <Input id="ended_at" v-model="endedAt" type="time" />
              <p v-if="errors.ended_at" class="text-sm text-destructive">{{ errors.ended_at[0] }}</p>
            </div>
          </div>

          <div class="flex gap-1.5">
            <span class="text-xs text-muted-foreground self-center mr-0.5">+</span>
            <Button
              v-for="mins in [30, 60, 90, 120]"
              :key="mins"
              type="button"
              variant="outline"
              size="sm"
              class="h-7 px-2.5 text-xs"
              :disabled="!startedAt"
              @click="applyDuration(mins)"
            >
              {{ mins < 60 ? `${mins}m` : `${mins / 60}h` }}
            </Button>
          </div>
        </div>

        <!-- Duration mode: total minutes input -->
        <div v-else class="grid gap-3">
          <div class="grid gap-1.5">
            <Label for="duration_input">{{ t('entryDialog.duration') }}</Label>
            <Input
              id="duration_input"
              v-model="durationInput"
              type="number"
              min="1"
              max="1440"
              placeholder="e.g. 90"
            />
            <p v-if="errors.duration_minutes" class="text-sm text-destructive">{{ errors.duration_minutes[0] }}</p>
          </div>

          <div class="flex gap-1.5">
            <span class="text-xs text-muted-foreground self-center mr-0.5">+</span>
            <Button
              v-for="mins in [30, 60, 90, 120]"
              :key="mins"
              type="button"
              variant="outline"
              size="sm"
              class="h-7 px-2.5 text-xs"
              @click="applyDuration(mins)"
            >
              {{ mins < 60 ? `${mins}m` : `${mins / 60}h` }}
            </Button>
          </div>
        </div>

        <!-- Notes -->
        <div class="grid gap-1.5">
          <Label for="notes">{{ t('entryDialog.notes') }} <span class="text-muted-foreground">{{ t('entryDialog.notesOptional') }}</span></Label>
          <Textarea id="notes" v-model="notes" rows="2" />
        </div>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="close">{{ t('entryDialog.cancel') }}</Button>
        <Button :disabled="!canSubmit" @click="submit">{{ t('entryDialog.save') }}</Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
