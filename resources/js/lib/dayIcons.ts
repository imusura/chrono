import type { Component } from 'vue'
import { CalendarOff, Palmtree, PartyPopper, Stethoscope, Wallet } from 'lucide-vue-next'

export const leaveIconFor = (name: string): Component => {
  const n = name.toLowerCase()
  if (n.includes('vacation') || n.includes('godišnj') || n.includes('odmor')) return Palmtree
  if (n.includes('sick') || n.includes('bolovanj')) return Stethoscope
  if (n.includes('paid') || n.includes('plaćen')) return Wallet
  return CalendarOff
}

export const holidayIcon: Component = PartyPopper
