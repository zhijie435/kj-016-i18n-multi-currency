import Vue from 'vue'
import VueRouter from 'vue-router'
import store from '@/store'
import i18n from '@/locales'

Vue.use(VueRouter)

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/Login/index.vue'),
    meta: { titleKey: 'common.login', requiresAuth: false }
  },
  {
    path: '/',
    redirect: '/dashboard'
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: () => import('@/views/Dashboard/index.vue'),
    meta: { titleKey: 'menu.dashboard', requiresAuth: true }
  },
  {
    path: '/403',
    name: 'Forbidden',
    component: () => import('@/views/NotFound/index.vue'),
    meta: { titleKey: 'common.no_permission' }
  },
  {
    path: '*',
    name: 'NotFound',
    component: () => import('@/views/NotFound/index.vue'),
    meta: { titleKey: 'common.no_data' }
  }
]

const router = new VueRouter({
  mode: 'history',
  base: import.meta.env.VITE_APP_BASE_URL || '/',
  routes
})

router.beforeEach((to, from, next) => {
  if (to.meta.titleKey) {
    const appName = i18n.t('common.app_name')
    const pageTitle = i18n.t(to.meta.titleKey)
    document.title = `${pageTitle} - ${appName}`
  }
  if (to.meta.requiresAuth && !store.getters.isLogin) {
    next({ path: '/login', query: { redirect: to.fullPath } })
    return
  }
  next()
})

export default router
