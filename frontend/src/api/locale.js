import request from '@/utils/request'

export function fetchAvailableLocales() {
  return request.get('/locale')
}

export function fetchAllLocales() {
  return request.get('/locales/all')
}

export function fetchLocaleMessages(locale) {
  return request.get(`/locale/${locale}`)
}

export function updateServerLocale(locale) {
  return request.post('/locale', { locale })
}

export function createLocale(data) {
  return request.post('/locales', data)
}

export function updateLocale(id, data) {
  return request.put(`/locales/${id}`, data)
}

export function deleteLocale(id) {
  return request.delete(`/locales/${id}`)
}

export function setChannel(channelCode) {
  try {
    localStorage.setItem('app_channel', channelCode)
  } catch (e) {}
}

export function getStoredChannel() {
  try {
    return localStorage.getItem('app_channel')
  } catch (e) {
    return null
  }
}

export function clearChannel() {
  try {
    localStorage.removeItem('app_channel')
  } catch (e) {}
}

export function setCurrency(currency) {
  try {
    localStorage.setItem('app_currency', JSON.stringify(currency))
  } catch (e) {}
}

export function getStoredCurrency() {
  try {
    const stored = localStorage.getItem('app_currency')
    return stored ? JSON.parse(stored) : null
  } catch (e) {
    return null
  }
}

export function clearCurrency() {
  try {
    localStorage.removeItem('app_currency')
  } catch (e) {}
}
