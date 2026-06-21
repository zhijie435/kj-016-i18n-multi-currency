import Vue from 'vue'
import App from './App.vue'
import router from './router'
import store from './store'
import i18n, { loadElementUILocale, resolveInitialLocale, setLocale } from './locales'
import ElementUI from 'element-ui'
import ElementUILocale from 'element-ui/lib/locale'
import 'element-ui/lib/theme-chalk/index.css'
import './styles/index.scss'
import request from '@/utils/request'

Vue.use(ElementUI, { size: 'small' })

Vue.prototype.$http = request

Vue.config.productionTip = false

const initialLocale = resolveInitialLocale()
setLocale(initialLocale)

loadElementUILocale(initialLocale)
  .then(mod => {
    if (mod && mod.default) {
      ElementUILocale.use(mod.default)
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

new Vue({
  router,
  store,
  i18n,
  render: h => h(App)
}).$mount('#app')
