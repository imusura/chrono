import api from '@/httpClient'

export const leaveDayService = {
  getMonth: async (year: number, month: number): Promise<Record<string, string>> => {
    const { data } = await api.get<{ data: Record<string, string> }>('/leave/days', {
      params: { year, month },
    })
    return data.data
  },
}
