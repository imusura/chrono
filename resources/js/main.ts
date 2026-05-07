import './httpClient'
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { VueQueryPlugin } from '@tanstack/vue-query'
import { createI18n } from 'vue-i18n'
import App from './App.vue'
import router from './router'
import { registerServiceWorker } from './registerSW'
import hr from './locales/hr.json'
import en from './locales/en.json'

const savedLocale = (localStorage.getItem('app-locale') ?? 'hr') as 'hr' | 'en'

export const i18n = createI18n({
  legacy: false,
  locale: savedLocale,
  fallbackLocale: 'en',
  messages: { hr, en },
})

const container = document.getElementById('app')!

if (!container.__vue_app__) {
  const app = createApp(App)

  app.use(createPinia())
  app.use(router)
  app.use(VueQueryPlugin)
  app.use(i18n)

  app.mount(container)

  registerServiceWorker()
}
