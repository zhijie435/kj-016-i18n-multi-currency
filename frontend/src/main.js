import Vue from 'vue'
import App from './App.vue'
import router from './router'
import store from './store'
import i18n, { loadElementUILocale, resolveInitialLocale, setLocale, mergeLocaleMessages } from './locales'
import ElementUI from 'element-ui'
import ElementUILocale from 'element-ui/lib/locale'
import 'element-ui/lib/theme-chalk/index.css'
import './styles/index.scss'
import request from '@/utils/request'
import { fetchLocaleMessages } from '@/api/locale'

Vue.use(ElementUI, { size: 'small' })

Vue.prototype.$http = request

Vue.config.productionTip = false

const initialLocale = resolveInitialLocale()
setLocale(initialLocale)
store.commit('SET_LOCALE', initialLocale)

loadElementUILocale(initialLocale)
  .then(mod => {
    if (mod && mod.default) {
      ElementUILocale.use(mod.default)
    }
  })
  .catch(() => {})

fetchLocaleMessages(initialLocale)
  .then(res => {
    if (res.data?.messages) {
      mergeLocaleMessages(initialLocale, res.data.messages)
    }
  })
  .catch(() => {})

store.watch(
  state => state.locale,
  newLocale => {
    loadElementUILocale(newLocale).then(mod => {
      if (mod && mod.default) {
        ElementUILocale.use(mod.default)
      }
    })
  }
)

store.dispatch('loadServerLocales').catch(() => {})
store.dispatch('loadChannels').catch(() => {})

new Vue({
  router,
  store,
  i18n,
  render: h => h(App)
}).$mount('#app')
