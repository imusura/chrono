import { useQuery } from '@tanstack/vue-query'
import { computed, toValue, type MaybeRef } from 'vue'
import { leaveBalanceService } from '@/services/leaveBalanceService'

export const useLeaveBalance = (year?: MaybeRef<number>) => {
  const queryKey = computed(() => ['leave-balance', year ? toValue(year) : 'current'])

  return useQuery({
    queryKey,
    queryFn: () => leaveBalanceService.get(year ? toValue(year) : undefined),
  })
}
