import api from '@/httpClient'
import type { User } from '@/types'

export const authService = {
  getCsrfCookie: () => api.get('/sanctum/csrf-cookie', { baseURL: '/' }),

  login: async (email: string, password: string): Promise<User> => {
    await authService.getCsrfCookie()
    const { data } = await api.post<User>('/login', { email, password })
    return data
  },

  register: async (payload: {
    name: string
    email: string
    password: string
    password_confirmation: string
  }): Promise<User> => {
    await authService.getCsrfCookie()
    const { data } = await api.post<User>('/register', payload)
    return data
  },

  logout: () => api.post('/logout'),

  getUser: async (): Promise<User> => {
    const { data } = await api.get<User>('/user')
    return data
  },

  forgotPassword: async (email: string): Promise<string> => {
    const { data } = await api.post<{ message: string }>('/forgot-password', { email })
    return data.message
  },

  resetPassword: async (payload: {
    token: string
    email: string
    password: string
    password_confirmation: string
  }): Promise<string> => {
    const { data } = await api.post<{ message: string }>('/reset-password', payload)
    return data.message
  },
}
