import request from '@/utils/request'
import {
  fetchExchangeRates,
  fetchActiveExchangeRates,
  fetchExchangeRate,
  getExchangeRate,
  convertCurrency,
  getExchangeRateMatrix,
  createExchangeRate,
  updateExchangeRate,
  deleteExchangeRate,
  activateExchangeRate,
  deactivateExchangeRate
} from '@/api/exchangeRate'

jest.mock('@/utils/request')

describe('exchangeRate API', () => {
  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('fetchExchangeRates should GET /exchange-rates with params', async () => {
    const params = { page: 1, limit: 10 }
    const mockResponse = { data: [] }
    request.get.mockResolvedValue(mockResponse)

    const result = await fetchExchangeRates(params)

    expect(request.get).toHaveBeenCalledTimes(1)
    expect(request.get).toHaveBeenCalledWith('/exchange-rates', { params })
    expect(result).toEqual(mockResponse)
  })

  it('fetchExchangeRates should GET /exchange-rates without params', async () => {
    const mockResponse = { data: [] }
    request.get.mockResolvedValue(mockResponse)

    const result = await fetchExchangeRates()

    expect(request.get).toHaveBeenCalledTimes(1)
    expect(request.get).toHaveBeenCalledWith('/exchange-rates', { params: undefined })
    expect(result).toEqual(mockResponse)
  })

  it('fetchActiveExchangeRates should GET /exchange-rates/active with params', async () => {
    const params = { from: 'USD', to: 'CNY' }
    const mockResponse = { data: [] }
    request.get.mockResolvedValue(mockResponse)

    const result = await fetchActiveExchangeRates(params)

    expect(request.get).toHaveBeenCalledTimes(1)
    expect(request.get).toHaveBeenCalledWith('/exchange-rates/active', { params })
    expect(result).toEqual(mockResponse)
  })

  it('fetchExchangeRate should GET /exchange-rates/:id', async () => {
    const id = 1
    const mockResponse = { data: { id, rate: 7.2 } }
    request.get.mockResolvedValue(mockResponse)

    const result = await fetchExchangeRate(id)

    expect(request.get).toHaveBeenCalledTimes(1)
    expect(request.get).toHaveBeenCalledWith(`/exchange-rates/${id}`)
    expect(result).toEqual(mockResponse)
  })

  it('getExchangeRate should GET /exchange-rates/rate with params', async () => {
    const params = { from_currency_code: 'USD', to_currency_code: 'CNY' }
    const mockResponse = { data: { rate: 7.2 } }
    request.get.mockResolvedValue(mockResponse)

    const result = await getExchangeRate(params)

    expect(request.get).toHaveBeenCalledTimes(1)
    expect(request.get).toHaveBeenCalledWith('/exchange-rates/rate', { params })
    expect(result).toEqual(mockResponse)
  })

  it('convertCurrency should GET /exchange-rates/convert with params', async () => {
    const params = { amount: 100, from_currency_code: 'USD', to_currency_code: 'CNY' }
    const mockResponse = { data: { converted_amount: 720 } }
    request.get.mockResolvedValue(mockResponse)

    const result = await convertCurrency(params)

    expect(request.get).toHaveBeenCalledTimes(1)
    expect(request.get).toHaveBeenCalledWith('/exchange-rates/convert', { params })
    expect(result).toEqual(mockResponse)
  })

  it('getExchangeRateMatrix should POST /exchange-rates/matrix with data', async () => {
    const data = { currencies: ['USD', 'CNY', 'EUR'] }
    const mockResponse = { data: { matrix: {} } }
    request.post.mockResolvedValue(mockResponse)

    const result = await getExchangeRateMatrix(data)

    expect(request.post).toHaveBeenCalledTimes(1)
    expect(request.post).toHaveBeenCalledWith('/exchange-rates/matrix', data)
    expect(result).toEqual(mockResponse)
  })

  it('createExchangeRate should POST /exchange-rates with data', async () => {
    const data = { from_currency_code: 'USD', to_currency_code: 'CNY', rate: 7.2 }
    const mockResponse = { data: { id: 1, ...data } }
    request.post.mockResolvedValue(mockResponse)

    const result = await createExchangeRate(data)

    expect(request.post).toHaveBeenCalledTimes(1)
    expect(request.post).toHaveBeenCalledWith('/exchange-rates', data)
    expect(result).toEqual(mockResponse)
  })

  it('updateExchangeRate should PUT /exchange-rates/:id with data', async () => {
    const id = 1
    const data = { rate: 7.3 }
    const mockResponse = { data: { id, ...data } }
    request.put.mockResolvedValue(mockResponse)

    const result = await updateExchangeRate(id, data)

    expect(request.put).toHaveBeenCalledTimes(1)
    expect(request.put).toHaveBeenCalledWith(`/exchange-rates/${id}`, data)
    expect(result).toEqual(mockResponse)
  })

  it('deleteExchangeRate should DELETE /exchange-rates/:id', async () => {
    const id = 1
    const mockResponse = { data: { success: true } }
    request.delete.mockResolvedValue(mockResponse)

    const result = await deleteExchangeRate(id)

    expect(request.delete).toHaveBeenCalledTimes(1)
    expect(request.delete).toHaveBeenCalledWith(`/exchange-rates/${id}`)
    expect(result).toEqual(mockResponse)
  })

  it('activateExchangeRate should POST /exchange-rates/:id/activate', async () => {
    const id = 1
    const mockResponse = { data: { success: true } }
    request.post.mockResolvedValue(mockResponse)

    const result = await activateExchangeRate(id)

    expect(request.post).toHaveBeenCalledTimes(1)
    expect(request.post).toHaveBeenCalledWith(`/exchange-rates/${id}/activate`)
    expect(result).toEqual(mockResponse)
  })

  it('deactivateExchangeRate should POST /exchange-rates/:id/deactivate', async () => {
    const id = 1
    const mockResponse = { data: { success: true } }
    request.post.mockResolvedValue(mockResponse)

    const result = await deactivateExchangeRate(id)

    expect(request.post).toHaveBeenCalledTimes(1)
    expect(request.post).toHaveBeenCalledWith(`/exchange-rates/${id}/deactivate`)
    expect(result).toEqual(mockResponse)
  })
})
