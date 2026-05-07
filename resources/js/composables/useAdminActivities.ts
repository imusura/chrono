import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { activityService } from '@/services/activityService'
import type { MaybeRef } from 'vue'
import type { StoreActivityPayload, UpdateActivityPayload } from '@/types'

export const useAdminActivities = (organisationId?: MaybeRef<number | undefined>) => {
  const queryClient = useQueryClient()
  const queryKey = ['admin-activities', organisationId]
  const invalidate = () => queryClient.invalidateQueries({ queryKey: ['admin-activities'] })

  const query = useQuery({
    queryKey,
    queryFn: () => {
      const id = typeof organisationId === 'object' ? organisationId.value : organisationId
      return activityService.getAll(id)
    },
  })

  const storeMutation = useMutation({
    mutationFn: (payload: StoreActivityPayload) => activityService.store(payload),
    onSuccess: invalidate,
  })

  const updateMutation = useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: UpdateActivityPayload }) =>
      activityService.update(id, payload),
    onSuccess: invalidate,
  })

  const destroyMutation = useMutation({
    mutationFn: (id: number) => activityService.destroy(id),
    onSuccess: invalidate,
  })

  return { query, storeMutation, updateMutation, destroyMutation }
}
