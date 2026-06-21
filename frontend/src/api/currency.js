import request from '@/utils/request'

export function fetchCurrencies() {
  return request.get('/currencies')
}

export function fetchEnabledCurrencies() {
  return request.get('/currencies/enabled')
}

export function fetchCurrency(code) {
  return request.get(`/currencies/${code}`)
}

export function createCurrency(data) {
  return request.post('/currencies', data)
}

export function updateCurrency(id, data) {
  return request.put(`/currencies/${id}`, data)
}

export function deleteCurrency(id) {
  return request.delete(`/currencies/${id}`)
}
