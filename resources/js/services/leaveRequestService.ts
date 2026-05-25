import api from '@/httpClient'
import type {
  LeaveRequest,
  StoreLeaveRequestPayload,
  UpdateLeaveRequestStatusPayload,
} from '@/types'

export const leaveRequestService = {
  listMine: async (): Promise<LeaveRequest[]> => {
    const { data } = await api.get<{ data: LeaveRequest[] }>('/leave/requests')
    return data.data
  },

  listAll: async (): Promise<LeaveRequest[]> => {
    const { data } = await api.get<{ data: LeaveRequest[] }>('/leave/requests/all')
    return data.data
  },

  store: async (payload: StoreLeaveRequestPayload): Promise<LeaveRequest> => {
    const { data } = await api.post<{ data: LeaveRequest }>('/leave/requests', payload)
    return data.data
  },

  updateStatus: async (
    id: number,
    payload: UpdateLeaveRequestStatusPayload,
  ): Promise<LeaveRequest> => {
    const { data } = await api.patch<{ data: LeaveRequest }>(`/leave/requests/${id}`, payload)
    return data.data
  },

  destroy: async (id: number): Promise<void> => {
    await api.delete(`/leave/requests/${id}`)
  },
}
