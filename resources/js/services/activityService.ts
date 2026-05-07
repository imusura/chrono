import api from '@/httpClient'
import type { Activity, StoreActivityPayload, UpdateActivityPayload } from '@/types'

export const activityService = {
  getAll: async (organisationId?: number): Promise<Activity[]> => {
    const { data } = await api.get<{ data: Activity[] }>('/activities', {
      params: organisationId ? { organisation_id: organisationId } : undefined,
    })
    return data.data
  },

  store: async (payload: StoreActivityPayload): Promise<Activity> => {
    const { data } = await api.post<{ data: Activity }>('/activities', payload)
    return data.data
  },

  update: async (id: number, payload: UpdateActivityPayload): Promise<Activity> => {
    const { data } = await api.put<{ data: Activity }>(`/activities/${id}`, payload)
    return data.data
  },

  destroy: async (id: number): Promise<void> => {
    await api.delete(`/activities/${id}`)
  },
}
