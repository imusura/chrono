import api from '@/httpClient'
import type { User } from '@/types'

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
