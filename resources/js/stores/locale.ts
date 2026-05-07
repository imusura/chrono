import { ref, watch } from 'vue'
import { defineStore } from 'pinia'
import { useI18n } from 'vue-i18n'

export type Locale = 'hr' | 'en'

const STORAGE_KEY = 'app-locale'

export const useLocaleStore = defineStore('locale', () => {
  const { locale } = useI18n()
  const current = ref<Locale>((localStorage.getItem(STORAGE_KEY) as Locale) ?? 'hr')

  locale.value = current.value

  watch(current, (value) => {
    locale.value = value
    localStorage.setItem(STORAGE_KEY, value)
  })

  return { current }
})
