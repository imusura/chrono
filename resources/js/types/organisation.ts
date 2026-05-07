export type TimeEntryMode = 'range' | 'duration'

export interface Organisation {
  id: number
  name: string
  time_entry_mode: TimeEntryMode
  country_code: string
  created_at: string
}

export interface StoreOrganisationPayload {
  name: string
  time_entry_mode: TimeEntryMode
  country_code: string
}

export interface UpdateOrganisationPayload {
  name: string
  time_entry_mode?: TimeEntryMode
  country_code?: string
}
