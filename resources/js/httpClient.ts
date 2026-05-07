import axios from 'axios'
import { toast } from 'vue-sonner'
import { ValidationError } from '@/types'

axios.defaults.baseURL = '/api'
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
axios.defaults.withCredentials = true

axios.interceptors.request.use((config) => {
  const locale = localStorage.getItem('app-locale') ?? 'hr'
  config.headers['Accept-Language'] = locale
  return config
})

axios.interceptors.response.use(
  (response) => response,
  (error) => {
    if (!axios.isAxiosError(error) || !error.response) {
      toast.error('Network error. Please check your connection.')
      return Promise.reject(error)
    }

    const { status, data } = error.response

    if (status === 422 && data.errors) {
      return Promise.reject(new ValidationError(data.errors))
    }

    if (status === 422) {
      toast.error(data.message || 'The given data was invalid.')
      return Promise.reject(error)
    }

    if (status === 401) {
      return Promise.reject(error)
    }

    if (status === 419) {
      toast.error('Session expired. Please refresh the page.')
      return Promise.reject(error)
    }

    if (status === 429) {
      toast.error('Too many requests. Please wait a moment.')
      return Promise.reject(error)
    }

    if (status >= 500) {
      toast.error('Something went wrong. Please try again later.')
      return Promise.reject(error)
    }

    if (status === 403) {
      toast.error('You do not have permission to perform this action.')
      return Promise.reject(error)
    }

    if (status === 404) {
      toast.error('The requested resource was not found.')
      return Promise.reject(error)
    }

    toast.error(data?.message || 'An unexpected error occurred.')
    return Promise.reject(error)
  },
)

export default axios
