import api from '@/httpClient'
import type { Role, StoreRolePayload, UpdateRolePayload } from '@/types'

export const roleService = {
  getAll: async (organisationId?: number): Promise<Role[]> => {
    const { data } = await api.get<{ data: Role[] }>('/roles', {
      params: organisationId ? { organisation_id: organisationId } : undefined,
    })
    return data.data
  },

  store: async (payload: StoreRolePayload): Promise<Role> => {
    const { data } = await api.post<{ data: Role }>('/roles', payload)
    return data.data
  },

  update: async (id: number, payload: UpdateRolePayload): Promise<Role> => {
    const { data } = await api.put<{ data: Role }>(`/roles/${id}`, payload)
    return data.data
  },

  destroy: async (id: number): Promise<void> => {
    await api.delete(`/roles/${id}`)
  },
}
