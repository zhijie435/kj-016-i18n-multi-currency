import Vue from 'vue'
import App from './App.vue'
import router from './router'
import store from './store'
import i18n, { setLocale, loadElementUILocale, resolveInitialLocale } from './locales'
import ElementUI from 'element-ui'
import ElementUILocale from 'element-ui/lib/locale'
import 'element-ui/lib/theme-chalk/index.css'
import './styles/index.scss'
import request from '@/utils/request'

Vue.use(ElementUI, { size: 'small' })

Vue.prototype.$http = request
Vue.prototype.$t = i18n.t.bind(i18n)
Vue.prototype.$tc = i18n.tc.bind(i18n)
Vue.prototype.$te = i18n.te.bind(i18n)

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
