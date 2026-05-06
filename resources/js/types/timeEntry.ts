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
  started_at: string
  ended_at: string
  notes: string | null
}

export interface StoreTimeEntryPayload {
  activity_id: number
  date: string
  started_at: string
  ended_at: string
  notes?: string | null
}

export interface UpdateTimeEntryPayload {
  activity_id: number
  started_at: string
  ended_at: string
  notes?: string | null
}
