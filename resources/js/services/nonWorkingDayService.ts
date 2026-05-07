import api from '@/httpClient'
import type { NonWorkingDay, StoreNonWorkingDayPayload, UpdateNonWorkingDayPayload } from '@/types'

export const nonWorkingDayService = {
  getYear: async (year: number, organisationId?: number): Promise<NonWorkingDay[]> => {
    const { data } = await api.get<{ data: NonWorkingDay[] }>('/non-working-days', {
      params: { year, ...(organisationId ? { organisation_id: organisationId } : {}) },
    })
    return data.data
  },

  store: async (payload: StoreNonWorkingDayPayload): Promise<NonWorkingDay> => {
    const { data } = await api.post<{ data: NonWorkingDay }>('/non-working-days', payload)
    return data.data
  },

  update: async (id: number, payload: UpdateNonWorkingDayPayload): Promise<NonWorkingDay> => {
    const { data } = await api.put<{ data: NonWorkingDay }>(`/non-working-days/${id}`, payload)
    return data.data
  },

  destroy: async (id: number): Promise<void> => {
    await api.delete(`/non-working-days/${id}`)
  },

  syncYear: async (year: number, organisationId?: number): Promise<{ synced: number }> => {
    const { data } = await api.post<{ synced: number }>('/non-working-days/sync', {
      year,
      ...(organisationId ? { organisation_id: organisationId } : {}),
    })
    return data
  },
}
