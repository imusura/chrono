export interface Role {
  id: number
  name: string
  color: string
  activity_ids: number[]
}

export interface StoreRolePayload {
  organisation_id?: number
  name: string
  color: string
}

export interface UpdateRolePayload {
  name: string
  color: string
}
