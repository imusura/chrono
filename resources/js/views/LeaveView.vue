<script setup lang="ts">
import { ref, computed } from 'vue'
import { Plus } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import {
  AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent,
  AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import AppLayout from '@/components/layout/AppLayout.vue'
import LeaveBalanceCard from '@/components/leave/LeaveBalanceCard.vue'
import LeaveRequestRow from '@/components/leave/LeaveRequestRow.vue'
import LeaveRequestDialog from '@/components/leave/LeaveRequestDialog.vue'
import { useAuthStore } from '@/stores/auth'
import { useMyLeaveRequests } from '@/composables/useLeaveRequests'
import { ValidationError } from '@/types'
import type { LeaveRequest, StoreLeaveRequestPayload } from '@/types'
import { useI18n } from 'vue-i18n'

const auth = useAuthStore()
const { t } = useI18n()

const { query, createMutation, cancelMutation } = useMyLeaveRequests()

const dialogOpen = ref(false)
const dialogRef = ref<InstanceType<typeof LeaveRequestDialog> | null>(null)

const cancelTarget = ref<LeaveRequest | null>(null)

const vacationMode = computed(() => auth.user?.vacation_mode ?? 'simple')

const handleSave = async (payload: StoreLeaveRequestPayload) => {
  try {
    await createMutation.mutateAsync(payload)
    dialogRef.value?.finishSubmit()
  } catch (err) {
    if (err instanceof ValidationError) dialogRef.value?.setErrors(err)
  }
}

const handleCancel = (request: LeaveRequest) => {
  cancelTarget.value = request
}

const confirmCancel = async () => {
  if (!cancelTarget.value) return
  await cancelMutation.mutateAsync(cancelTarget.value.id)
  cancelTarget.value = null
}

const sortedRequests = computed(() => {
  const list = query.data.value ?? []
  return [...list].sort((a, b) => b.start_date.localeCompare(a.start_date))
})

const canCancel = (request: LeaveRequest) =>
  request.status === 'pending' || request.status === 'approved'
</script>

<template>
  <AppLayout>
    <div class="max-w-4xl mx-auto px-3 sm:px-6 py-6 space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">{{ t('leave.title') }}</h1>
        <Button @click="dialogOpen = true">
          <Plus class="size-4 mr-1" />
          {{ t('leave.requestLeave') }}
        </Button>
      </div>

      <LeaveBalanceCard />

      <div class="bg-card border rounded-lg overflow-hidden">
        <div class="px-3 sm:px-4 py-3 border-b text-sm font-medium text-muted-foreground">
          {{ t('leave.history') }}
        </div>

        <div v-if="query.isPending.value" class="p-4 space-y-2">
          <Skeleton class="h-12" />
          <Skeleton class="h-12" />
        </div>

        <div v-else-if="sortedRequests.length === 0" class="px-3 sm:px-4 py-8 text-center text-sm text-muted-foreground">
          {{ t('leave.noHistory') }}
        </div>

        <div v-else>
          <LeaveRequestRow
            v-for="request in sortedRequests"
            :key="request.id"
            :request="request"
            :can-cancel="canCancel(request)"
            :cancelling="cancelMutation.isPending.value && cancelMutation.variables.value === request.id"
            @cancel="handleCancel"
          />
        </div>
      </div>
    </div>

    <LeaveRequestDialog
      ref="dialogRef"
      v-model:open="dialogOpen"
      :vacation-mode="vacationMode"
      @save="handleSave"
    />

    <AlertDialog :open="cancelTarget !== null" @update:open="(o) => !o && (cancelTarget = null)">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>{{ t('leave.cancelDialog.title') }}</AlertDialogTitle>
          <AlertDialogDescription>{{ t('leave.cancelDialog.description') }}</AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>{{ t('leave.cancelDialog.keep') }}</AlertDialogCancel>
          <AlertDialogAction @click="confirmCancel">{{ t('leave.cancelDialog.confirm') }}</AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </AppLayout>
</template>
