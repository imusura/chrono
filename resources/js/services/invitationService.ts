import api from '@/httpClient'
import type { PendingInvitation, User } from '@/types'

export interface StoreInvitationPayload {
  emails: string[]
  role_ids: number[]
  contracted_hours: number
  is_admin: boolean
  organisation_id?: number
}

export interface InvitationPreview {
  email: string
}

export interface InvitationResult {
  sent: string[]
  skipped: string[]
}

export const invitationService = {
  getAll: async (organisationId?: number): Promise<PendingInvitation[]> => {
    const { data } = await api.get<{ data: PendingInvitation[] }>('/invitations', {
      params: organisationId ? { organisation_id: organisationId } : undefined,
    })
    return data.data
  },

  destroy: async (id: number): Promise<void> => {
    await api.delete(`/invitations/${id}`)
  },

  store: async (payload: StoreInvitationPayload): Promise<InvitationResult> => {
    const { data } = await api.post<InvitationResult>('/invitations', payload)
    return data
  },

  show: async (token: string): Promise<InvitationPreview> => {
    const { data } = await api.get<InvitationPreview>(`/invitations/${token}`)
    return data
  },

  accept: async (token: string, payload: { name: string; password: string; password_confirmation: string }): Promise<User> => {
    const { data } = await api.post<User>(`/invitations/${token}/accept`, payload)
    return data
  },
}
