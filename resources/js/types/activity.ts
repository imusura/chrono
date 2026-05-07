export interface Activity {
  id: number
  name: string
  color: string
  is_active: boolean
  role_ids: number[]
}

export interface StoreActivityPayload {
  organisation_id?: number
  name: string
  color: string
  is_active: boolean
  role_ids: number[]
}

export interface UpdateActivityPayload {
  name: string
  color: string
  is_active: boolean
  role_ids: number[]
}
