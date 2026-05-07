import api from '@/httpClient'
import type { Activity } from '@/types'

export const userActivityService = {
  getAll: async (): Promise<Activity[]> => {
    const { data } = await api.get<{ data: Activity[] }>('/user-activities')
    return data.data
  },
}
