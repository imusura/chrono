export interface NonWorkingDay {
  id: number
  organisation_id: number | null
  date: string
  name: string
  is_public: boolean
}

export interface StoreNonWorkingDayPayload {
  organisation_id?: number
  date: string
  name: string
}

export interface UpdateNonWorkingDayPayload {
  name: string
}
