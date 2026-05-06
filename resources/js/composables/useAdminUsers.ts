import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { userAdminService } from '@/services/userAdminService'
import type { StoreUserPayload, UpdateUserPayload } from '@/types'

const QUERY_KEY = ['admin-users']

export const useAdminUsers = () => {
  const queryClient = useQueryClient()
  const invalidate = () => queryClient.invalidateQueries({ queryKey: QUERY_KEY })

  const query = useQuery({
    queryKey: QUERY_KEY,
    queryFn: userAdminService.getAll,
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
