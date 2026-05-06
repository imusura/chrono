import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { organisationService } from '@/services/organisationService'
import type { StoreOrganisationPayload, UpdateOrganisationPayload } from '@/types'

const QUERY_KEY = ['organisations']

export const useOrganisations = () => {
  const queryClient = useQueryClient()
  const invalidate = () => queryClient.invalidateQueries({ queryKey: QUERY_KEY })

  const query = useQuery({
    queryKey: QUERY_KEY,
    queryFn: organisationService.getAll,
  })

  const storeMutation = useMutation({
    mutationFn: (payload: StoreOrganisationPayload) => organisationService.store(payload),
    onSuccess: invalidate,
  })

  const updateMutation = useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: UpdateOrganisationPayload }) =>
      organisationService.update(id, payload),
    onSuccess: invalidate,
  })

  const destroyMutation = useMutation({
    mutationFn: (id: number) => organisationService.destroy(id),
    onSuccess: invalidate,
  })

  return { query, storeMutation, updateMutation, destroyMutation }
}
