import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import { authService } from '@/services/authService'
import type { User } from '@/types'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const isReady = ref(false)

  const isAuthenticated = computed(() => user.value !== null)
  const isSuperAdmin = computed(() => user.value?.is_super_admin ?? false)
  const isAdmin = computed(() => user.value?.is_admin ?? false)

  const login = async (email: string, password: string) => {
    user.value = await authService.login(email, password)
  }

  const logout = async () => {
    await authService.logout()
    user.value = null
  }

  const fetchUser = async () => {
    try {
      user.value = await authService.getUser()
    } catch {
      user.value = null
    } finally {
      isReady.value = true
    }
  }

  const setUser = (newUser: User) => {
    user.value = newUser
    isReady.value = true
  }

  return { user, isReady, isAuthenticated, isSuperAdmin, isAdmin, login, logout, fetchUser, setUser }
})
