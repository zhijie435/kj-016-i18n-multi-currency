import Vue from 'vue'
import VueRouter from 'vue-router'
import store from '@/store'

Vue.use(VueRouter)

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/Login/index.vue'),
    meta: { title: 'login', requiresAuth: false }
  },
  {
    path: '/',
    redirect: '/dashboard'
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: () => import('@/views/Dashboard/index.vue'),
    meta: { title: 'dashboard', requiresAuth: true }
  },
  {
    path: '/403',
    name: 'Forbidden',
    component: () => import('@/views/NotFound/index.vue'),
    meta: { title: 'no_permission' }
  },
  {
    path: '*',
    name: 'NotFound',
    component: () => import('@/views/NotFound/index.vue'),
    meta: { title: 'no_data' }
  }
]

const router = new VueRouter({
  mode: 'history',
  base: import.meta.env.VITE_APP_BASE_URL || '/',
  routes
})

router.beforeEach((to, from, next) => {
  if (to.meta.title) {
    const appName = store.getters.currentLocaleInfo ? '' : ''
    document.title = `${to.meta.title}${appName ? ' - ' + appName : ''}`
  }
  if (to.meta.requiresAuth && !store.getters.isLogin) {
    next({ path: '/login', query: { redirect: to.fullPath } })
    return
  }
  next()
})

export default router
