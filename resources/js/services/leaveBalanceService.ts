import api from '@/httpClient'
import type { LeaveBalanceResponse } from '@/types'

export const leaveBalanceService = {
  get: async (year?: number): Promise<LeaveBalanceResponse> => {
    const { data } = await api.get<LeaveBalanceResponse>('/leave/balance', {
      params: year ? { year } : undefined,
    })
    return data
  },
}
