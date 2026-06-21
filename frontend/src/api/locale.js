import request from '@/utils/request'

export function fetchAvailableLocales() {
  return request.get('/locale')
}

export function fetchLocaleMessages(locale) {
  return request.get(`/locale/${locale}`)
}

export function updateServerLocale(locale) {
  return request.post('/locale', { locale })
}
