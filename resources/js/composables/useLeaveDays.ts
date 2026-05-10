import { useQuery } from '@tanstack/vue-query'
import { computed, toValue, type MaybeRef } from 'vue'
import { leaveDayService } from '@/services/leaveDayService'

export const useLeaveDays = (year: MaybeRef<number>, month: MaybeRef<number>) => {
  const queryKey = computed(() => ['leave-days', toValue(year), toValue(month)])

  const query = useQuery({
    queryKey,
    queryFn: () => leaveDayService.getMonth(toValue(year), toValue(month)),
  })

  const leaveDayMap = computed(() => {
    const map = new Map<string, string>()
    const data = query.data.value
    if (data) {
      for (const [date, name] of Object.entries(data)) map.set(date, name)
    }
    return map
  })

  return { query, leaveDayMap }
}
