import { useQuery } from '@tanstack/vue-query'
import { leaveTypeService } from '@/services/leaveTypeService'

export const useLeaveTypes = () =>
  useQuery({
    queryKey: ['leave-types'],
    queryFn: () => leaveTypeService.list(),
    staleTime: 5 * 60 * 1000,
  })
