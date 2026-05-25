import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { leaveRequestService } from '@/services/leaveRequestService'
import type {
  LeaveRequest,
  StoreLeaveRequestPayload,
  UpdateLeaveRequestStatusPayload,
} from '@/types'

const MINE_KEY = ['leave-requests', 'mine'] as const
const ALL_KEY = ['leave-requests', 'all'] as const

const invalidateAll = (queryClient: ReturnType<typeof useQueryClient>) => {
  queryClient.invalidateQueries({ queryKey: ['leave-requests'] })
  queryClient.invalidateQueries({ queryKey: ['leave-balance'] })
  queryClient.invalidateQueries({ queryKey: ['leave-days'] })
  queryClient.invalidateQueries({ queryKey: ['year-overview'] })
}

export const useMyLeaveRequests = () => {
  const queryClient = useQueryClient()

  const query = useQuery<LeaveRequest[]>({
    queryKey: MINE_KEY,
    queryFn: () => leaveRequestService.listMine(),
  })

  const createMutation = useMutation({
    mutationFn: (payload: StoreLeaveRequestPayload) => leaveRequestService.store(payload),
    onSuccess: () => invalidateAll(queryClient),
  })

  const cancelMutation = useMutation({
    mutationFn: (id: number) =>
      leaveRequestService.updateStatus(id, { status: 'cancelled' }),
    onSuccess: () => invalidateAll(queryClient),
  })

  const destroyMutation = useMutation({
    mutationFn: (id: number) => leaveRequestService.destroy(id),
    onSuccess: () => invalidateAll(queryClient),
  })

  return { query, createMutation, cancelMutation, destroyMutation }
}

export const useOrganisationLeaveRequests = () => {
  const queryClient = useQueryClient()

  const query = useQuery<LeaveRequest[]>({
    queryKey: ALL_KEY,
    queryFn: () => leaveRequestService.listAll(),
  })

  const updateStatusMutation = useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: UpdateLeaveRequestStatusPayload }) =>
      leaveRequestService.updateStatus(id, payload),
    onSuccess: () => invalidateAll(queryClient),
  })

  return { query, updateStatusMutation }
}
