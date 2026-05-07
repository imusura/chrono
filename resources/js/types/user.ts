import type { TimeEntryMode } from './organisation'

export interface User {
  id: number
  name: string
  email: string
  organisation_id: number
  contracted_hours: number
  time_entry_mode: TimeEntryMode
  is_admin: boolean
  is_super_admin: boolean
  created_at: string
  updated_at: string
}
