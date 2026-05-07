import { format, formatRelative } from 'date-fns'
import { hr as hrLocale, enGB } from 'date-fns/locale'
import { i18n } from '@/main'

const dateFnsLocale = () => (i18n.global.locale.value === 'hr' ? hrLocale : enGB)

export const formatMonthYear = (year: number, month: number): string =>
  format(new Date(year, month - 1, 1), 'LLLL yyyy', { locale: dateFnsLocale() })

export const formatWeekdayShort = (d: Date): string =>
  format(d, 'EEE', { locale: dateFnsLocale() })

export const formatDayLong = (dateStr: string): string => {
  if (!dateStr) return ''
  return format(new Date(dateStr + 'T00:00:00'), 'EEEE, d. MMMM', { locale: dateFnsLocale() })
}

export const formatDateShort = (dateStr: string): string =>
  format(new Date(dateStr), 'd. MMM yyyy.', { locale: dateFnsLocale() })

export const formatDateTime = (date: string) =>
  format(new Date(date), 'd. MMM yyyy. HH:mm', { locale: dateFnsLocale() })

export const formatDate = (date: string) =>
  format(new Date(date), 'd. MMMM yyyy.', { locale: dateFnsLocale() })

export const formatRelativeTime = (date: string): string => {
  const diffSeconds = Math.round((new Date(date).getTime() - Date.now()) / 1000)

  const units: [Intl.RelativeTimeFormatUnit, number][] = [
    ['year', 60 * 60 * 24 * 365],
    ['month', 60 * 60 * 24 * 30],
    ['week', 60 * 60 * 24 * 7],
    ['day', 60 * 60 * 24],
    ['hour', 60 * 60],
    ['minute', 60],
  ]

  const locale = i18n.global.locale.value
  const rtf = new Intl.RelativeTimeFormat(locale, { numeric: 'auto' })

  for (const [unit, seconds] of units) {
    if (Math.abs(diffSeconds) >= seconds) {
      return rtf.format(Math.round(diffSeconds / seconds), unit)
    }
  }
  return rtf.format(diffSeconds, 'second')
}

export const stripHtml = (html: string) => html.replace(/<[^>]*>/g, '').trim()

export const minutesToHm = (minutes: number): string => {
  const h = Math.floor(minutes / 60)
  const m = minutes % 60
  return m === 0 ? `${h}h` : `${h}h ${m}m`
}

export const hmToMinutes = (hm: string): number => {
  const [h, m] = hm.split(':').map(Number)
  return h * 60 + m
}

export const entryMinutes = (startedAt: string, endedAt: string): number =>
  hmToMinutes(endedAt) - hmToMinutes(startedAt)

export const decimalHoursToHm = (decimal: number): string => {
  const h = Math.floor(decimal)
  const m = Math.round((decimal - h) * 60)
  return `${h}:${String(m).padStart(2, '0')}`
}

export const hmToDecimalHours = (hm: string): number => {
  const [h, m] = hm.split(':').map(Number)
  return h + (m ?? 0) / 60
}

export const toIsoDate = (d: Date): string =>
  `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`

const DAY_START_MINUTES = 5 * 60   // 05:00
const DAY_END_MINUTES   = 18 * 60  // 18:00
const DAY_SPAN_MINUTES  = DAY_END_MINUTES - DAY_START_MINUTES

export const timeToOffset = (hm: string): number => {
  const mins = hmToMinutes(hm)
  return Math.max(0, Math.min(1, (mins - DAY_START_MINUTES) / DAY_SPAN_MINUTES))
}

export const addMinutesToTime = (time: string, mins: number): string => {
  const [h, m] = time.split(':').map(Number)
  const total = h * 60 + m + mins
  return `${String(Math.floor(total / 60) % 24).padStart(2, '0')}:${String(total % 60).padStart(2, '0')}`
}
