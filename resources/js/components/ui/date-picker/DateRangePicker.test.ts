import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import DateRangePicker from './DateRangePicker.vue'

const sleep = (ms: number) => new Promise((r) => setTimeout(r, ms))

const clickDay = async (dayNumber: number) => {
  const triggers = document.querySelectorAll('[data-slot="range-calendar-trigger"]')
  const btn = Array.from(triggers).find(
    (el) => el.textContent?.trim() === String(dayNumber) && !el.hasAttribute('data-outside-view'),
  ) as HTMLElement | undefined
  if (!btn) {
    const available = Array.from(triggers).map((el) => el.textContent?.trim()).join(', ')
    throw new Error(`Day ${dayNumber} not found in calendar. Available: ${available}`)
  }
  btn.click()
  await nextTick()
  await sleep(50)
}

const openPopover = async (wrapper: ReturnType<typeof mount>) => {
  await wrapper.find('button').trigger('click')
  await nextTick()
  await sleep(100)
}

const getLastRange = (wrapper: ReturnType<typeof mount>) => {
  const emitted = wrapper.emitted('update:range')!
  return emitted[emitted.length - 1][0] as { from: string; to: string }
}

describe('DateRangePicker', () => {
  it('selects a forward range (10 → 20) correctly', async () => {
    const wrapper = mount(DateRangePicker, {
      props: { placeholder: 'Pick a date range' },
      attachTo: document.body,
    })

    await openPopover(wrapper)
    await clickDay(10)
    await clickDay(20)

    const range = getLastRange(wrapper)
    expect(range.from).toMatch(/-10$/)
    expect(range.to).toMatch(/-20$/)

    wrapper.unmount()
  })

  it('selects a backward range (20 → 5) and normalizes it', async () => {
    const wrapper = mount(DateRangePicker, {
      props: { placeholder: 'Pick a date range' },
      attachTo: document.body,
    })

    await openPopover(wrapper)
    await clickDay(20)
    await clickDay(5)

    const range = getLastRange(wrapper)
    expect(new Date(range.from).getTime()).toBeLessThan(new Date(range.to).getTime())

    wrapper.unmount()
  })

  it('re-selects a new forward range after a complete range', async () => {
    const wrapper = mount(DateRangePicker, {
      props: { placeholder: 'Pick a date range' },
      attachTo: document.body,
    })

    await openPopover(wrapper)
    await clickDay(1)
    await clickDay(20)

    await clickDay(25)
    await clickDay(28)

    const range = getLastRange(wrapper)
    expect(range.from).toMatch(/-25$/)
    expect(range.to).toMatch(/-28$/)

    wrapper.unmount()
  })

  it('works when parent feeds props back after selection', async () => {
    const wrapper = mount(DateRangePicker, {
      props: { placeholder: 'Pick a date range' },
      attachTo: document.body,
    })

    await openPopover(wrapper)
    await clickDay(10)
    await clickDay(20)

    const firstRange = getLastRange(wrapper)

    // Simulate parent syncing both props back atomically (single router.replace)
    await wrapper.setProps({ from: firstRange.from, to: firstRange.to })
    await nextTick()
    await sleep(50)

    // Now select a new range: 25 → 28
    await clickDay(25)
    await clickDay(28)

    const secondRange = getLastRange(wrapper)
    expect(secondRange.from).toMatch(/-25$/)
    expect(secondRange.to).toMatch(/-28$/)
    expect(new Date(secondRange.from).getTime()).toBeLessThan(new Date(secondRange.to).getTime())

    wrapper.unmount()
  })
})
