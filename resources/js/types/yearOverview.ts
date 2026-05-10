export interface YearDay {
  minutes: number
  leave: string | null
  non_working: string | null
}

export interface YearOverview {
  year: number
  contracted_minutes: number
  first_activity_date: string | null
  days: Record<string, YearDay>
}
