<script setup lang="ts">
import { ref, watch } from 'vue'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import type { TimeEntry, StoreTimeEntryPayload, UpdateTimeEntryPayload } from '@/types'

const props = defineProps<{
  open: boolean
  date: string
  entry?: TimeEntry | null
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
  save: [payload: StoreTimeEntryPayload | UpdateTimeEntryPayload]
}>()

const activityId = ref<number | ''>('')
const startedAt = ref('')
const endedAt = ref('')
const notes = ref('')
const errors = ref<Record<string, string[]>>({})
const saving = ref(false)

watch(
  () => props.open,
  (open) => {
    if (!open) return
    errors.value = {}
    if (props.entry) {
      activityId.value = props.entry.activity_id
      startedAt.value = props.entry.started_at
      endedAt.value = props.entry.ended_at
      notes.value = props.entry.notes ?? ''
    } else {
      activityId.value = ''
      startedAt.value = ''
      endedAt.value = ''
      notes.value = ''
    }
  },
)

const close = () => emit('update:open', false)

const submit = () => {
  if (!activityId.value || !startedAt.value || !endedAt.value) return

  const base = {
    activity_id: activityId.value as number,
    started_at: startedAt.value,
    ended_at: endedAt.value,
    notes: notes.value || null,
  }

  emit('save', props.entry ? base : { ...base, date: props.date })
}
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle>{{ entry ? 'Edit Entry' : 'Add Entry' }}</DialogTitle>
      </DialogHeader>

      <div class="grid gap-4 py-2">
        <div class="grid gap-1.5">
          <Label for="activity">Activity</Label>
          <Input
            id="activity"
            v-model.number="activityId"
            type="number"
            placeholder="Activity ID (temp)"
          />
          <p v-if="errors.activity_id" class="text-sm text-destructive">{{ errors.activity_id[0] }}</p>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div class="grid gap-1.5">
            <Label for="started_at">Start</Label>
            <Input id="started_at" v-model="startedAt" type="time" />
            <p v-if="errors.started_at" class="text-sm text-destructive">{{ errors.started_at[0] }}</p>
          </div>
          <div class="grid gap-1.5">
            <Label for="ended_at">End</Label>
            <Input id="ended_at" v-model="endedAt" type="time" />
            <p v-if="errors.ended_at" class="text-sm text-destructive">{{ errors.ended_at[0] }}</p>
          </div>
        </div>

        <div class="grid gap-1.5">
          <Label for="notes">Notes <span class="text-muted-foreground">(optional)</span></Label>
          <Textarea id="notes" v-model="notes" rows="3" />
        </div>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="close">Cancel</Button>
        <Button @click="submit">Save</Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
