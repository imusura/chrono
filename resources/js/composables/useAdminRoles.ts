import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { roleService } from '@/services/roleService'
import type { MaybeRef } from 'vue'
import type { StoreRolePayload, UpdateRolePayload } from '@/types'

export const useAdminRoles = (organisationId?: MaybeRef<number | undefined>) => {
  const queryClient = useQueryClient()
  const queryKey = ['admin-roles', organisationId]
  const invalidate = () => queryClient.invalidateQueries({ queryKey: ['admin-roles'] })

  const query = useQuery({
    queryKey,
    queryFn: () => {
      const id = typeof organisationId === 'object' ? organisationId.value : organisationId
      return roleService.getAll(id)
    },
  })

  const storeMutation = useMutation({
    mutationFn: (payload: StoreRolePayload) => roleService.store(payload),
    onSuccess: invalidate,
  })

  const updateMutation = useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: UpdateRolePayload }) =>
      roleService.update(id, payload),
    onSuccess: invalidate,
  })

  const destroyMutation = useMutation({
    mutationFn: (id: number) => roleService.destroy(id),
    onSuccess: invalidate,
  })

  return { query, storeMutation, updateMutation, destroyMutation }
}
