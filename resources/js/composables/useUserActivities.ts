import { useQuery } from '@tanstack/vue-query'
import { userActivityService } from '@/services/userActivityService'

export const useUserActivities = () =>
  useQuery({
    queryKey: ['user-activities'],
    queryFn: userActivityService.getAll,
  })
