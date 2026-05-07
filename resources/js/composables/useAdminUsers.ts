import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { userAdminService } from '@/services/userAdminService'
import type { MaybeRef } from 'vue'
import type { StoreUserPayload, UpdateUserPayload } from '@/types'

export const useAdminUsers = (organisationId?: MaybeRef<number | undefined>) => {
  const queryClient = useQueryClient()
  const queryKey = ['admin-users', organisationId]
  const invalidate = () => queryClient.invalidateQueries({ queryKey: ['admin-users'] })

  const query = useQuery({
    queryKey,
    queryFn: () => {
      const id = typeof organisationId === 'object' ? organisationId.value : organisationId
      return userAdminService.getAll(id)
    },
  })

  const storeMutation = useMutation({
    mutationFn: (payload: StoreUserPayload) => userAdminService.store(payload),
    onSuccess: invalidate,
  })

  const updateMutation = useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: UpdateUserPayload }) =>
      userAdminService.update(id, payload),
    onSuccess: invalidate,
  })

  const destroyMutation = useMutation({
    mutationFn: (id: number) => userAdminService.destroy(id),
    onSuccess: invalidate,
  })

  return { query, storeMutation, updateMutation, destroyMutation }
}
