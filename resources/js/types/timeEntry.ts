export interface ActivitySummary {
  id: number
  name: string
  color: string | null
}

export interface TimeEntry {
  id: number
  activity_id: number
  activity: ActivitySummary
  date: string
  started_at: string | null
  ended_at: string | null
  duration_minutes: number
  notes: string | null
}

export interface StoreTimeEntryPayload {
  activity_id: number
  date: string
  // range mode
  started_at?: string
  ended_at?: string
  // duration mode
  duration_minutes?: number
  notes?: string | null
}

export interface BatchStoreTimeEntryPayload {
  entries: StoreTimeEntryPayload[]
}

export interface UpdateTimeEntryPayload {
  activity_id: number
  // range mode
  started_at?: string
  ended_at?: string
  // duration mode
  duration_minutes?: number
  notes?: string | null
}
