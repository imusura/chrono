import { useQuery } from '@tanstack/vue-query'
import type { Ref } from 'vue'
import { yearOverviewService } from '@/services/yearOverviewService'

export const useYearOverview = (year: Ref<number>) => {
  const query = useQuery({
    queryKey: ['year-overview', year],
    queryFn: () => yearOverviewService.get(year.value),
  })

  return { query }
}
