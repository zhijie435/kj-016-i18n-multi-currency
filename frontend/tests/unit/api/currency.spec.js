import request from '@/utils/request'
import {
  fetchCurrencies,
  fetchEnabledCurrencies,
  fetchCurrency,
  createCurrency,
  updateCurrency,
  deleteCurrency
} from '@/api/currency'

jest.mock('@/utils/request')

describe('currency API', () => {
  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('fetchCurrencies should GET /currencies', async () => {
    const mockResponse = { data: [] }
    request.get.mockResolvedValue(mockResponse)

    const result = await fetchCurrencies()

    expect(request.get).toHaveBeenCalledTimes(1)
    expect(request.get).toHaveBeenCalledWith('/currencies')
    expect(result).toEqual(mockResponse)
  })

  it('fetchEnabledCurrencies should GET /currencies/enabled', async () => {
    const mockResponse = { data: [] }
    request.get.mockResolvedValue(mockResponse)

    const result = await fetchEnabledCurrencies()

    expect(request.get).toHaveBeenCalledTimes(1)
    expect(request.get).toHaveBeenCalledWith('/currencies/enabled')
    expect(result).toEqual(mockResponse)
  })

  it('fetchCurrency should GET /currencies/:code', async () => {
    const code = 'USD'
    const mockResponse = { data: { code, name: 'US Dollar' } }
    request.get.mockResolvedValue(mockResponse)

    const result = await fetchCurrency(code)

    expect(request.get).toHaveBeenCalledTimes(1)
    expect(request.get).toHaveBeenCalledWith(`/currencies/${code}`)
    expect(result).toEqual(mockResponse)
  })

  it('createCurrency should POST /currencies with data', async () => {
    const data = { code: 'JPY', name: 'Japanese Yen', symbol: '¥', decimals: 0 }
    const mockResponse = { data: { id: 1, ...data } }
    request.post.mockResolvedValue(mockResponse)

    const result = await createCurrency(data)

    expect(request.post).toHaveBeenCalledTimes(1)
    expect(request.post).toHaveBeenCalledWith('/currencies', data)
    expect(result).toEqual(mockResponse)
  })

  it('updateCurrency should PUT /currencies/:id with data', async () => {
    const id = 1
    const data = { name: 'Updated Japanese Yen', decimals: 2 }
    const mockResponse = { data: { id, ...data } }
    request.put.mockResolvedValue(mockResponse)

    const result = await updateCurrency(id, data)

    expect(request.put).toHaveBeenCalledTimes(1)
    expect(request.put).toHaveBeenCalledWith(`/currencies/${id}`, data)
    expect(result).toEqual(mockResponse)
  })

  it('deleteCurrency should DELETE /currencies/:id', async () => {
    const id = 1
    const mockResponse = { data: { success: true } }
    request.delete.mockResolvedValue(mockResponse)

    const result = await deleteCurrency(id)

    expect(request.delete).toHaveBeenCalledTimes(1)
    expect(request.delete).toHaveBeenCalledWith(`/currencies/${id}`)
    expect(result).toEqual(mockResponse)
  })
})
