<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import type { LeaveRequestStatus } from '@/types'

const props = defineProps<{ status: LeaveRequestStatus }>()
const { t } = useI18n()

const config = computed(() => {
  switch (props.status) {
    case 'pending':
      return { label: t('leave.status.pending'), classes: 'bg-amber-100 text-amber-900 dark:bg-amber-900/40 dark:text-amber-200' }
    case 'approved':
      return { label: t('leave.status.approved'), classes: 'bg-emerald-100 text-emerald-900 dark:bg-emerald-900/40 dark:text-emerald-200' }
    case 'rejected':
      return { label: t('leave.status.rejected'), classes: 'bg-rose-100 text-rose-900 dark:bg-rose-900/40 dark:text-rose-200' }
    case 'cancelled':
      return { label: t('leave.status.cancelled'), classes: 'bg-muted text-muted-foreground' }
    case 'draft':
    default:
      return { label: t('leave.status.draft'), classes: 'bg-muted text-muted-foreground' }
  }
})
</script>

<template>
  <span
    class="inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium"
    :class="config.classes"
  >
    {{ config.label }}
  </span>
</template>
