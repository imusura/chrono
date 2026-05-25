<script setup lang="ts">
import { ref, computed } from 'vue'
import { Skeleton } from '@/components/ui/skeleton'
import AppLayout from '@/components/layout/AppLayout.vue'
import LeaveRequestRow from '@/components/leave/LeaveRequestRow.vue'
import RejectLeaveDialog from '@/components/leave/RejectLeaveDialog.vue'
import { useOrganisationLeaveRequests } from '@/composables/useLeaveRequests'
import type { LeaveRequest } from '@/types'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const { query, updateStatusMutation } = useOrganisationLeaveRequests()

const rejectTarget = ref<LeaveRequest | null>(null)

const pending = computed(() =>
  (query.data.value ?? [])
    .filter((r) => r.status === 'pending')
    .sort((a, b) => a.start_date.localeCompare(b.start_date)),
)

const recent = computed(() =>
  (query.data.value ?? [])
    .filter((r) => r.status !== 'pending')
    .sort((a, b) => b.updated_at.localeCompare(a.updated_at))
    .slice(0, 20),
)

const approve = (request: LeaveRequest) => {
  updateStatusMutation.mutate({ id: request.id, payload: { status: 'approved' } })
}

const openReject = (request: LeaveRequest) => {
  rejectTarget.value = request
}

const confirmReject = async (reason: string) => {
  if (!rejectTarget.value) return
  await updateStatusMutation.mutateAsync({
    id: rejectTarget.value.id,
    payload: { status: 'rejected', rejection_reason: reason },
  })
  rejectTarget.value = null
}

const isMutatingId = (id: number) =>
  updateStatusMutation.isPending.value && updateStatusMutation.variables.value?.id === id
</script>

<template>
  <AppLayout>
    <div class="max-w-4xl mx-auto px-3 sm:px-6 py-6 space-y-6">
      <h1 class="text-2xl font-semibold">{{ t('leave.approvals.title') }}</h1>

      <div class="bg-card border rounded-lg overflow-hidden">
        <div class="px-3 sm:px-4 py-3 border-b text-sm font-medium text-muted-foreground flex items-center justify-between">
          <span>{{ t('leave.approvals.pending') }}</span>
          <span class="tabular-nums text-xs">{{ pending.length }}</span>
        </div>

        <div v-if="query.isPending.value" class="p-4 space-y-2">
          <Skeleton class="h-12" />
          <Skeleton class="h-12" />
        </div>

        <div v-else-if="pending.length === 0" class="px-3 sm:px-4 py-8 text-center text-sm text-muted-foreground">
          {{ t('leave.approvals.empty') }}
        </div>

        <div v-else>
          <LeaveRequestRow
            v-for="request in pending"
            :key="request.id"
            :request="request"
            :show-user="true"
            :can-approve="true"
            :approving="isMutatingId(request.id) && updateStatusMutation.variables.value?.payload.status === 'approved'"
            :rejecting="isMutatingId(request.id) && updateStatusMutation.variables.value?.payload.status === 'rejected'"
            @approve="approve"
            @reject="openReject"
          />
        </div>
      </div>

      <div v-if="recent.length > 0" class="bg-card border rounded-lg overflow-hidden">
        <div class="px-3 sm:px-4 py-3 border-b text-sm font-medium text-muted-foreground">
          {{ t('leave.approvals.recent') }}
        </div>
        <div>
          <LeaveRequestRow
            v-for="request in recent"
            :key="request.id"
            :request="request"
            :show-user="true"
          />
        </div>
      </div>
    </div>

    <RejectLeaveDialog
      :open="rejectTarget !== null"
      :submitting="updateStatusMutation.isPending.value"
      @update:open="(o) => !o && (rejectTarget = null)"
      @confirm="confirmReject"
    />
  </AppLayout>
</template>
