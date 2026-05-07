import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { computed, toValue, type MaybeRef } from 'vue'
import { nonWorkingDayService } from '@/services/nonWorkingDayService'
import type { StoreNonWorkingDayPayload, UpdateNonWorkingDayPayload } from '@/types'

export const useNonWorkingDays = (
  year: MaybeRef<number>,
  organisationId?: MaybeRef<number | undefined>,
) => {
  const queryClient = useQueryClient()
  const queryKey = computed(() => ['non-working-days', toValue(year), toValue(organisationId)])
  const invalidate = () => queryClient.invalidateQueries({ queryKey: ['non-working-days'] })

  const query = useQuery({
    queryKey,
    queryFn: () => nonWorkingDayService.getYear(toValue(year), toValue(organisationId)),
  })

  const nonWorkingDaySet = computed(() => new Set(query.data.value?.map((d) => d.date) ?? []))

  const storeMutation = useMutation({
    mutationFn: (payload: StoreNonWorkingDayPayload) => nonWorkingDayService.store(payload),
    onSuccess: invalidate,
  })

  const updateMutation = useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: UpdateNonWorkingDayPayload }) =>
      nonWorkingDayService.update(id, payload),
    onSuccess: invalidate,
  })

  const destroyMutation = useMutation({
    mutationFn: (id: number) => nonWorkingDayService.destroy(id),
    onSuccess: invalidate,
  })

  const syncMutation = useMutation({
    mutationFn: (y: number) => nonWorkingDayService.syncYear(y, toValue(organisationId)),
    onSuccess: invalidate,
  })

  return { query, nonWorkingDaySet, storeMutation, updateMutation, destroyMutation, syncMutation }
}
