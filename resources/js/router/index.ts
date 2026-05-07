import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    redirect: '/time',
  },
  {
    path: '/time',
    name: 'time',
    component: () => import('@/views/TimeView.vue'),
    meta: { auth: true },
  },
  {
    path: '/admin/organisations',
    name: 'admin.organisations',
    component: () => import('@/views/admin/OrganisationsView.vue'),
    meta: { auth: true, superAdmin: true },
  },
  {
    path: '/admin/organisation',
    name: 'admin.organisation',
    component: () => import('@/views/admin/OrganisationView.vue'),
    meta: { auth: true, admin: true },
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/auth/LoginView.vue'),
    meta: { guest: true },
  },
  {
    path: '/forgot-password',
    name: 'forgot-password',
    component: () => import('@/views/auth/ForgotPasswordView.vue'),
    meta: { guest: true },
  },
  {
    path: '/reset-password/:token',
    name: 'reset-password',
    component: () => import('@/views/auth/ResetPasswordView.vue'),
    meta: { guest: true },
  },
  {
    path: '/accept-invite/:token',
    name: 'accept-invite',
    component: () => import('@/views/auth/AcceptInviteView.vue'),
    meta: { guest: true },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to) => {
  const authStore = useAuthStore()

  if (!authStore.isReady) {
    await authStore.fetchUser()
  }

  if (to.meta.auth && !authStore.isAuthenticated) {
    return { name: 'login' }
  }

  if (to.meta.guest && authStore.isAuthenticated) {
    return { name: 'time' }
  }

  if (to.meta.superAdmin && !authStore.isSuperAdmin) {
    return { name: 'time' }
  }

  if (to.meta.admin && !authStore.isAdmin && !authStore.isSuperAdmin) {
    return { name: 'time' }
  }
})

export default router
