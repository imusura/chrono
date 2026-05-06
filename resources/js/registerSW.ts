export const registerServiceWorker = (): void => {
  if (!('serviceWorker' in navigator)) return
  if (!import.meta.env.PROD) return

  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch((error) => {
      console.error('Service worker registration failed:', error)
    })
  })
}
