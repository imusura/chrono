import api from '@/httpClient'
import type { Organisation, StoreOrganisationPayload, UpdateOrganisationPayload } from '@/types'

export const organisationService = {
  getAll: async (): Promise<Organisation[]> => {
    const { data } = await api.get<{ data: Organisation[] }>('/organisations')
    return data.data
  },

  store: async (payload: StoreOrganisationPayload): Promise<Organisation> => {
    const { data } = await api.post<{ data: Organisation }>('/organisations', payload)
    return data.data
  },

  update: async (id: number, payload: UpdateOrganisationPayload): Promise<Organisation> => {
    const { data } = await api.put<{ data: Organisation }>(`/organisations/${id}`, payload)
    return data.data
  },

  destroy: async (id: number): Promise<void> => {
    await api.delete(`/organisations/${id}`)
  },
}
