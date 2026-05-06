import { ref, watch } from 'vue'
import { defineStore } from 'pinia'

type Theme = 'light' | 'dark' | 'system'

const STORAGE_KEY = 'appearance-theme'

const applyTheme = (theme: Theme) => {
  const isDark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)
  document.documentElement.classList.toggle('dark', isDark)
}

export const useAppearanceStore = defineStore('appearance', () => {
  const theme = ref<Theme>((localStorage.getItem(STORAGE_KEY) as Theme) ?? 'system')

  applyTheme(theme.value)

  watch(theme, (value) => {
    localStorage.setItem(STORAGE_KEY, value)
    applyTheme(value)
  })

  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    if (theme.value === 'system') applyTheme('system')
  })

  return { theme }
})
