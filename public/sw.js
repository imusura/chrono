const VERSION = 'v2'
const CACHE_STATIC = `tickit-static-${VERSION}`
const CACHE_ASSETS = `tickit-assets-${VERSION}`
const CACHE_PAGES = `tickit-pages-${VERSION}`

const PRECACHE_URLS = [
  '/manifest.webmanifest',
  '/logo.png',
  '/favicon.ico',
  '/apple-touch-icon-180x180.png',
  '/pwa-192x192.png',
  '/pwa-512x512.png',
]

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_STATIC).then((cache) => cache.addAll(PRECACHE_URLS)).then(() => self.skipWaiting())
  )
})

self.addEventListener('activate', (event) => {
  const allowed = new Set([CACHE_STATIC, CACHE_ASSETS, CACHE_PAGES])
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((k) => !allowed.has(k)).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  )
})

const isApi = (url) => url.pathname.startsWith('/api/') || url.pathname.startsWith('/sanctum/') || url.pathname.startsWith('/auth/')
const isHashedAsset = (url) => url.pathname.startsWith('/build/')
const isStaticIcon = (url) => PRECACHE_URLS.includes(url.pathname)

self.addEventListener('fetch', (event) => {
  const { request } = event
  if (request.method !== 'GET') return

  const url = new URL(request.url)
  if (url.origin !== self.location.origin) return
  if (isApi(url)) return

  if (isHashedAsset(url)) {
    event.respondWith(cacheFirst(request, CACHE_ASSETS))
    return
  }

  if (isStaticIcon(url)) {
    event.respondWith(cacheFirst(request, CACHE_STATIC))
    return
  }

  if (request.mode === 'navigate') {
    event.respondWith(networkFirstNavigation(request))
  }
})

const cacheFirst = async (request, cacheName) => {
  const cache = await caches.open(cacheName)
  const cached = await cache.match(request)
  if (cached) return cached
  const response = await fetch(request)
  if (response.ok) cache.put(request, response.clone())
  return response
}

const networkFirstNavigation = async (request) => {
  const cache = await caches.open(CACHE_PAGES)
  try {
    const response = await fetch(request)
    if (response.ok) cache.put('/', response.clone())
    return response
  } catch {
    const cached = await cache.match('/')
    if (cached) return cached
    throw new Error('Offline and no cached shell available')
  }
}
