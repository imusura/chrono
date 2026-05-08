export interface PendingInvitation {
  id: number
  email: string
  role_ids: number[]
  contracted_hours: string
  is_admin: boolean
  expires_at: string
}
