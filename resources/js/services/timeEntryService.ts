import api from '@/httpClient'
import type { TimeEntry, StoreTimeEntryPayload, UpdateTimeEntryPayload, BatchStoreTimeEntryPayload } from '@/types'

export const timeEntryService = {
  getMonth: async (year: number, month: number): Promise<TimeEntry[]> => {
    const { data } = await api.get<{ data: TimeEntry[] }>('/time-entries', {
      params: { year, month },
    })
    return data.data
  },

  store: async (payload: StoreTimeEntryPayload): Promise<TimeEntry> => {
    const { data } = await api.post<{ data: TimeEntry }>('/time-entries', payload)
    return data.data
  },

  update: async (id: number, payload: UpdateTimeEntryPayload): Promise<TimeEntry> => {
    const { data } = await api.put<{ data: TimeEntry }>(`/time-entries/${id}`, payload)
    return data.data
  },

  destroy: async (id: number): Promise<void> => {
    await api.delete(`/time-entries/${id}`)
  },

  batchStore: async (payload: BatchStoreTimeEntryPayload): Promise<TimeEntry[]> => {
    const { data } = await api.post<{ data: TimeEntry[] }>('/time-entries/batch', payload)
    return data.data
  },
}
