<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { useI18n } from 'vue-i18n'
import { useLeaveTypes } from '@/composables/useLeaveTypes'
import { useLeaveBalance } from '@/composables/useLeaveBalance'
import { leaveColorFor } from '@/lib/leaveColors'
import { leaveIconFor } from '@/lib/dayIcons'
import type { LeaveType, StoreLeaveRequestPayload, ValidationError } from '@/types'

const props = defineProps<{
  open: boolean
  vacationMode: 'simple' | 'workflow'
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
  save: [payload: StoreLeaveRequestPayload]
}>()

const { t } = useI18n()
const { data: leaveTypes } = useLeaveTypes()
const { data: balanceData } = useLeaveBalance()

const leaveTypeId = ref<number | null>(null)
const startDate = ref('')
const endDate = ref('')
const errors = ref<Record<string, string[]>>({})
const submitting = ref(false)

const selectedType = computed<LeaveType | null>(() =>
  leaveTypes.value?.find((t) => t.id === leaveTypeId.value) ?? null,
)

const balanceForType = computed(() => {
  if (!selectedType.value) return null
  return balanceData.value?.data.find((b) => b.leave_type_id === selectedType.value!.id) ?? null
})

const workingDays = computed(() => {
  if (!startDate.value || !endDate.value) return 0
  const start = new Date(startDate.value + 'T00:00:00')
  const end = new Date(endDate.value + 'T00:00:00')
  if (end < start) return 0
  let count = 0
  const cursor = new Date(start)
  while (cursor <= end) {
    const dow = cursor.getDay()
    if (dow !== 0 && dow !== 6) count++
    cursor.setDate(cursor.getDate() + 1)
  }
  return count
})

const balanceAfter = computed(() => {
  if (!balanceForType.value) return null
  return balanceForType.value.balance - workingDays.value
})

const isWorkflow = computed(() => props.vacationMode === 'workflow')
const willRequireApproval = computed(() => {
  if (!selectedType.value) return false
  return isWorkflow.value || selectedType.value.requires_approval
})

const canSubmit = computed(() => {
  if (!leaveTypeId.value || !startDate.value || !endDate.value) return false
  if (endDate.value < startDate.value) return false
  if (workingDays.value <= 0) return false
  return true
})

watch(
  () => props.open,
  (open) => {
    if (open) {
      leaveTypeId.value = leaveTypes.value?.[0]?.id ?? null
      const today = new Date()
      const iso = today.toISOString().slice(0, 10)
      startDate.value = iso
      endDate.value = iso
      errors.value = {}
      submitting.value = false
    }
  },
)

watch(startDate, (val) => {
  if (val && endDate.value && endDate.value < val) endDate.value = val
})

const close = () => emit('update:open', false)

const submit = async () => {
  if (!canSubmit.value || !leaveTypeId.value) return
  submitting.value = true
  errors.value = {}
  emit('save', {
    leave_type_id: leaveTypeId.value,
    start_date: startDate.value,
    end_date: endDate.value,
  })
}

const setErrors = (err: ValidationError) => {
  errors.value = err.errors ?? {}
  submitting.value = false
}

const finishSubmit = () => {
  submitting.value = false
  emit('update:open', false)
}

defineExpose({ setErrors, finishSubmit })
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle>{{ t('leave.requestDialog.title') }}</DialogTitle>
      </DialogHeader>

      <div class="grid gap-5 py-2">
        <div class="grid gap-2">
          <Label>{{ t('leave.requestDialog.type') }}</Label>
          <div v-if="leaveTypes?.length" class="flex flex-wrap gap-2">
            <button
              v-for="type in leaveTypes"
              :key="type.id"
              type="button"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border text-sm font-medium transition-colors"
              :class="leaveTypeId === type.id
                ? 'border-foreground/40 bg-muted'
                : 'border-border text-muted-foreground hover:text-foreground hover:border-foreground/30'"
              @click="leaveTypeId = type.id"
            >
              <span class="size-3 rounded-[2px]" :class="leaveColorFor(type.name).swatch"></span>
              <component :is="leaveIconFor(type.name)" class="size-3.5" />
              {{ type.name }}
            </button>
          </div>
          <p v-if="errors.leave_type_id" class="text-sm text-destructive">{{ errors.leave_type_id[0] }}</p>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div class="grid gap-1.5">
            <Label for="start_date">{{ t('leave.requestDialog.from') }}</Label>
            <Input id="start_date" v-model="startDate" type="date" />
            <p v-if="errors.start_date" class="text-sm text-destructive">{{ errors.start_date[0] }}</p>
          </div>
          <div class="grid gap-1.5">
            <Label for="end_date">{{ t('leave.requestDialog.to') }}</Label>
            <Input id="end_date" v-model="endDate" type="date" :min="startDate" />
            <p v-if="errors.end_date" class="text-sm text-destructive">{{ errors.end_date[0] }}</p>
          </div>
        </div>

        <div class="rounded border bg-muted/40 px-3 py-2 text-sm space-y-1">
          <div class="flex justify-between">
            <span class="text-muted-foreground">{{ t('leave.requestDialog.workingDays') }}</span>
            <span class="font-medium tabular-nums">{{ workingDays }}</span>
          </div>
          <div v-if="selectedType?.has_allocation && balanceForType" class="flex justify-between">
            <span class="text-muted-foreground">{{ t('leave.requestDialog.balanceAfter') }}</span>
            <span
              class="font-medium tabular-nums"
              :class="balanceAfter !== null && balanceAfter < 0 ? 'text-destructive' : ''"
            >
              {{ balanceAfter?.toFixed(1) }}
              <span class="text-muted-foreground font-normal">/ {{ balanceForType.balance.toFixed(1) }}</span>
            </span>
          </div>
          <div class="text-xs text-muted-foreground pt-1">
            {{ willRequireApproval ? t('leave.requestDialog.willPend') : t('leave.requestDialog.willApprove') }}
          </div>
        </div>
      </div>

      <DialogFooter>
        <Button variant="outline" :disabled="submitting" @click="close">
          {{ t('leave.cancel') }}
        </Button>
        <Button :disabled="!canSubmit || submitting" @click="submit">
          {{ submitting ? t('leave.requestDialog.submitting') : t('leave.requestDialog.submit') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
