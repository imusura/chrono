<script setup lang="ts">
import { computed } from 'vue'
import { Check, X } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { useI18n } from 'vue-i18n'
import { formatDateShort } from '@/lib/format'
import { leaveColorFor } from '@/lib/leaveColors'
import { leaveIconFor } from '@/lib/dayIcons'
import LeaveStatusBadge from './LeaveStatusBadge.vue'
import type { LeaveRequest } from '@/types'

const props = defineProps<{
  request: LeaveRequest
  showUser?: boolean
  canCancel?: boolean
  canApprove?: boolean
  cancelling?: boolean
  approving?: boolean
  rejecting?: boolean
}>()

defineEmits<{
  cancel: [request: LeaveRequest]
  approve: [request: LeaveRequest]
  reject: [request: LeaveRequest]
}>()

const { t } = useI18n()

const typeName = computed(() => props.request.leave_type_name ?? '')
const color = computed(() => leaveColorFor(typeName.value))
const Icon = computed(() => leaveIconFor(typeName.value))

const sameDay = computed(() => props.request.start_date === props.request.end_date)
const dateLabel = computed(() =>
  sameDay.value
    ? formatDateShort(props.request.start_date)
    : `${formatDateShort(props.request.start_date)} → ${formatDateShort(props.request.end_date)}`,
)
</script>

<template>
  <div class="flex flex-wrap items-center gap-3 px-3 sm:px-4 py-3 border-b last:border-b-0">
    <span class="size-7 rounded flex items-center justify-center" :class="color.swatch">
      <component :is="Icon" class="size-4 text-foreground/70" />
    </span>

    <div class="flex-1 min-w-0">
      <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
        <span v-if="showUser" class="font-medium text-sm">{{ request.user_name }}</span>
        <span class="text-sm" :class="showUser ? 'text-muted-foreground' : 'font-medium'">
          {{ typeName }}
        </span>
        <LeaveStatusBadge :status="request.status" />
      </div>
      <div class="text-xs text-muted-foreground tabular-nums">
        {{ dateLabel }} · {{ t('leave.daysCount', { count: request.days_count }) }}
      </div>
      <div v-if="request.status === 'rejected' && request.rejection_reason" class="text-xs text-rose-600 dark:text-rose-400 mt-0.5">
        {{ request.rejection_reason }}
      </div>
    </div>

    <div class="flex items-center gap-1.5 shrink-0">
      <template v-if="canApprove && request.status === 'pending'">
        <Button
          variant="outline"
          size="sm"
          class="h-8"
          :disabled="rejecting || approving"
          @click="$emit('reject', request)"
        >
          <X class="size-3.5 mr-1" />
          {{ t('leave.reject') }}
        </Button>
        <Button
          size="sm"
          class="h-8"
          :disabled="approving || rejecting"
          @click="$emit('approve', request)"
        >
          <Check class="size-3.5 mr-1" />
          {{ t('leave.approve') }}
        </Button>
      </template>
      <Button
        v-else-if="canCancel"
        variant="ghost"
        size="sm"
        class="h-8 text-muted-foreground hover:text-foreground"
        :disabled="cancelling"
        @click="$emit('cancel', request)"
      >
        <X class="size-3.5 mr-1" />
        {{ t('leave.cancel') }}
      </Button>
    </div>
  </div>
</template>
