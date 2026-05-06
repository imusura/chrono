export interface Organisation {
  id: number
  name: string
  created_at: string
}

export interface StoreOrganisationPayload {
  name: string
}

export interface UpdateOrganisationPayload {
  name: string
}
