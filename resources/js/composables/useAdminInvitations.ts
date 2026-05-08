import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { invitationService } from '@/services/invitationService'
import type { MaybeRef } from 'vue'

export const useAdminInvitations = (organisationId?: MaybeRef<number | undefined>) => {
  const queryClient = useQueryClient()
  const queryKey = ['admin-invitations', organisationId]
  const invalidate = () => queryClient.invalidateQueries({ queryKey: ['admin-invitations'] })

  const query = useQuery({
    queryKey,
    queryFn: () => {
      const id = typeof organisationId === 'object' ? organisationId.value : organisationId
      return invitationService.getAll(id)
    },
  })

  const destroyMutation = useMutation({
    mutationFn: (id: number) => invitationService.destroy(id),
    onSuccess: invalidate,
  })

  return { query, destroyMutation }
}
