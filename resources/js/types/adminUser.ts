export interface AdminUser {
  id: number
  name: string
  email: string
  organisation_id: number | null
  contracted_hours: number
  is_admin: boolean
  is_super_admin: boolean
  role_ids: number[]
  created_at: string
  updated_at: string
}

export interface StoreUserPayload {
  organisation_id?: number
  name: string
  email: string
  password: string
  contracted_hours: number
  is_admin: boolean
  role_ids: number[]
}

export interface UpdateUserPayload {
  name: string
  email: string
  password?: string | null
  contracted_hours: number
  is_admin: boolean
  role_ids: number[]
}
