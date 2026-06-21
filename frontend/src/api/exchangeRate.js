import request from '@/utils/request'

export function fetchExchangeRates(params) {
  return request.get('/exchange-rates', { params })
}

export function fetchActiveExchangeRates(params) {
  return request.get('/exchange-rates/active', { params })
}

export function fetchExchangeRate(id) {
  return request.get(`/exchange-rates/${id}`)
}

export function getExchangeRate(params) {
  return request.get('/exchange-rates/rate', { params })
}

export function convertCurrency(params) {
  return request.get('/exchange-rates/convert', { params })
}

export function getExchangeRateMatrix(data) {
  return request.post('/exchange-rates/matrix', data)
}

export function createExchangeRate(data) {
  return request.post('/exchange-rates', data)
}

export function updateExchangeRate(id, data) {
  return request.put(`/exchange-rates/${id}`, data)
}

export function deleteExchangeRate(id) {
  return request.delete(`/exchange-rates/${id}`)
}

export function activateExchangeRate(id) {
  return request.post(`/exchange-rates/${id}/activate`)
}

export function deactivateExchangeRate(id) {
  return request.post(`/exchange-rates/${id}/deactivate`)
}
