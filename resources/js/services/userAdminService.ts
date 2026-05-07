import api from '@/httpClient'
import type { AdminUser, StoreUserPayload, UpdateUserPayload } from '@/types'

export const userAdminService = {
  getAll: async (organisationId?: number): Promise<AdminUser[]> => {
    const { data } = await api.get<{ data: AdminUser[] }>('/users', {
      params: organisationId ? { organisation_id: organisationId } : undefined,
    })
    return data.data
  },

  store: async (payload: StoreUserPayload): Promise<AdminUser> => {
    const { data } = await api.post<{ data: AdminUser }>('/users', payload)
    return data.data
  },

  update: async (id: number, payload: UpdateUserPayload): Promise<AdminUser> => {
    const { data } = await api.put<{ data: AdminUser }>(`/users/${id}`, payload)
    return data.data
  },

  destroy: async (id: number): Promise<void> => {
    await api.delete(`/users/${id}`)
  },
}
