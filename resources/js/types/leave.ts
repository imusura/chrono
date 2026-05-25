export type LeaveRequestStatus = 'draft' | 'pending' | 'approved' | 'rejected' | 'cancelled'

export interface LeaveType {
  id: number
  name: string
  has_allocation: boolean
  requires_approval: boolean
  allow_carryover: boolean
}

export interface LeaveBalance {
  leave_type_id: number
  leave_type_name: string
  balance: number
  unexpired_carryover: number
}

export interface LeaveBalanceResponse {
  data: LeaveBalance[]
  year: number
}

export interface LeaveRequest {
  id: number
  user_id: number
  user_name?: string
  leave_type_id: number
  leave_type_name?: string
  approved_by: number | null
  approver_name?: string | null
  start_date: string
  end_date: string
  days_count: number
  status: LeaveRequestStatus
  rejection_reason: string | null
  created_at: string
  updated_at: string
}

export interface StoreLeaveRequestPayload {
  leave_type_id: number
  start_date: string
  end_date: string
}

export interface UpdateLeaveRequestStatusPayload {
  status: LeaveRequestStatus
  rejection_reason?: string | null
}
