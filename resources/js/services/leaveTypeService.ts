import api from '@/httpClient'
import type { LeaveType } from '@/types'

export const leaveTypeService = {
  list: async (): Promise<LeaveType[]> => {
    const { data } = await api.get<{ data: LeaveType[] }>('/leave/types')
    return data.data
  },
}
