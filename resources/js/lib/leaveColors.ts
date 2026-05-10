export type LeaveColorKey = 'vacation' | 'sick' | 'paid' | 'default'

export interface LeaveColor {
  key: LeaveColorKey
  swatch: string
  rowBg: string
  legendKey: string
}

const colors: Record<LeaveColorKey, LeaveColor> = {
  vacation: {
    key: 'vacation',
    swatch: 'heat-vacation',
    rowBg: 'bg-blue-100 dark:bg-blue-950/40',
    legendKey: 'year.legendVacation',
  },
  sick: {
    key: 'sick',
    swatch: 'heat-sick',
    rowBg: 'bg-violet-100 dark:bg-violet-950/40',
    legendKey: 'year.legendSick',
  },
  paid: {
    key: 'paid',
    swatch: 'heat-paid',
    rowBg: 'bg-teal-100 dark:bg-teal-950/40',
    legendKey: 'year.legendPaidLeave',
  },
  default: {
    key: 'default',
    swatch: 'heat-vacation',
    rowBg: 'bg-blue-100 dark:bg-blue-950/40',
    legendKey: 'year.legendLeave',
  },
}

export const LEAVE_COLOR_ORDER: LeaveColorKey[] = ['vacation', 'sick', 'paid']

export const leaveColorByKey = (key: LeaveColorKey): LeaveColor => colors[key]

export const leaveColorFor = (name: string | null | undefined): LeaveColor => {
  if (!name) return colors.default
  const n = name.toLowerCase()
  if (n.includes('vacation') || n.includes('godišnj') || n.includes('odmor')) return colors.vacation
  if (n.includes('sick') || n.includes('bolovanj')) return colors.sick
  if (n.includes('paid') || n.includes('plaćen')) return colors.paid
  return colors.default
}

export const HOLIDAY_SWATCH = 'heat-holiday'
export const HOLIDAY_ROW_BG = 'bg-rose-100 dark:bg-rose-950/40'
