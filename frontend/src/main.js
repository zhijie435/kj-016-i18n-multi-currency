import Vue from 'vue'
import App from './App.vue'
import router from './router'
import store from './store'
import i18n from './locales'
import ElementUI from 'element-ui'
import 'element-ui/lib/theme-chalk/index.css'
import './styles/index.scss'
import request from '@/utils/request'

Vue.use(ElementUI, { size: 'small' })

Vue.prototype.$http = request

Vue.config.productionTip = false

store.dispatch('initializeApp').catch(() => {})

new Vue({
  router,
  store,
  i18n,
  render: h => h(App)
}).$mount('#app')
