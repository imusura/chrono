import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { computed, type Ref } from 'vue'
import { timeEntryService } from '@/services/timeEntryService'
import type { StoreTimeEntryPayload, TimeEntry, UpdateTimeEntryPayload } from '@/types'

export const useTimeEntries = (year: Ref<number>, month: Ref<number>) => {
  const queryClient = useQueryClient()

  const query = useQuery({
    queryKey: ['time-entries', year, month],
    queryFn: () => timeEntryService.getMonth(year.value, month.value),
  })

  const invalidate = () =>
    queryClient.invalidateQueries({ queryKey: ['time-entries', year.value, month.value] })

  const storeMutation = useMutation({
    mutationFn: (payload: StoreTimeEntryPayload) => timeEntryService.store(payload),
    onSuccess: invalidate,
  })

  const updateMutation = useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: UpdateTimeEntryPayload }) =>
      timeEntryService.update(id, payload),
    onSuccess: invalidate,
  })

  const destroyMutation = useMutation({
    mutationFn: (id: number) => timeEntryService.destroy(id),
    onSuccess: invalidate,
  })

  const entriesByDate = computed(() => {
    const map = new Map<string, TimeEntry[]>()
    for (const entry of query.data.value ?? []) {
      const list = map.get(entry.date) ?? []
      list.push(entry)
      map.set(entry.date, list)
    }
    return map
  })

  return { query, entriesByDate, storeMutation, updateMutation, destroyMutation }
}
