import type { TimeEntryMode } from './organisation'

export type VacationMode = 'simple' | 'workflow'

export interface User {
  id: number
  name: string
  email: string
  organisation_id: number
  contracted_hours: number
  time_entry_mode: TimeEntryMode
  vacation_mode: VacationMode
  is_admin: boolean
  is_super_admin: boolean
  created_at: string
  updated_at: string
}
