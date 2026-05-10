import api from '@/httpClient'
import type { YearOverview } from '@/types'

export const yearOverviewService = {
  get: async (year: number): Promise<YearOverview> => {
    const { data } = await api.get<YearOverview>('/year-overview', { params: { year } })
    return data
  },
}
