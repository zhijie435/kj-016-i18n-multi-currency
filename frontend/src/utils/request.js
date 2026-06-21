import axios from 'axios'
import store from '@/store'
import i18n from '@/locales'

const request = axios.create({
  baseURL: '/api',
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

request.interceptors.request.use(
  config => {
    const token = store.state.token
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    const locale = store.state.locale
    if (locale) {
      config.headers['X-App-Locale'] = locale
      config.headers['Accept-Language'] = locale.replace('_', '-')
    }
    return config
  },
  error => Promise.reject(error)
)

request.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      store.dispatch('logout')
    }
    return Promise.reject(error)
  }
)

export default request
