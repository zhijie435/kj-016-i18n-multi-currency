import request from '@/utils/request'
import {
  fetchAvailableLocales,
  fetchAllLocales,
  fetchLocaleMessages,
  updateServerLocale,
  createLocale,
  updateLocale,
  deleteLocale,
  setChannel,
  getStoredChannel,
  clearChannel,
  setCurrency,
  getStoredCurrency,
  clearCurrency
} from '@/api/locale'

jest.mock('@/utils/request')

describe('locale API', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    localStorage.clear()
  })

  it('fetchAvailableLocales should GET /locale', async () => {
    const mockResponse = { data: { available: {} } }
    request.get.mockResolvedValue(mockResponse)

    const result = await fetchAvailableLocales()

    expect(request.get).toHaveBeenCalledTimes(1)
    expect(request.get).toHaveBeenCalledWith('/locale')
    expect(result).toEqual(mockResponse)
  })

  it('fetchAllLocales should GET /locales/all', async () => {
    const mockResponse = { data: [] }
    request.get.mockResolvedValue(mockResponse)

    const result = await fetchAllLocales()

    expect(request.get).toHaveBeenCalledTimes(1)
    expect(request.get).toHaveBeenCalledWith('/locales/all')
    expect(result).toEqual(mockResponse)
  })

  it('fetchLocaleMessages should GET /locale/:locale', async () => {
    const locale = 'zh_CN'
    const mockResponse = { data: { messages: {} } }
    request.get.mockResolvedValue(mockResponse)

    const result = await fetchLocaleMessages(locale)

    expect(request.get).toHaveBeenCalledTimes(1)
    expect(request.get).toHaveBeenCalledWith(`/locale/${locale}`)
    expect(result).toEqual(mockResponse)
  })

  it('updateServerLocale should POST /locale with locale data', async () => {
    const locale = 'en'
    const mockResponse = { data: { success: true } }
    request.post.mockResolvedValue(mockResponse)

    const result = await updateServerLocale(locale)

    expect(request.post).toHaveBeenCalledTimes(1)
    expect(request.post).toHaveBeenCalledWith('/locale', { locale })
    expect(result).toEqual(mockResponse)
  })

  it('createLocale should POST /locales with data', async () => {
    const data = { code: 'fr', name: 'French' }
    const mockResponse = { data: { id: 1, ...data } }
    request.post.mockResolvedValue(mockResponse)

    const result = await createLocale(data)

    expect(request.post).toHaveBeenCalledTimes(1)
    expect(request.post).toHaveBeenCalledWith('/locales', data)
    expect(result).toEqual(mockResponse)
  })

  it('updateLocale should PUT /locales/:id with data', async () => {
    const id = 1
    const data = { name: 'Updated French' }
    const mockResponse = { data: { id, ...data } }
    request.put.mockResolvedValue(mockResponse)

    const result = await updateLocale(id, data)

    expect(request.put).toHaveBeenCalledTimes(1)
    expect(request.put).toHaveBeenCalledWith(`/locales/${id}`, data)
    expect(result).toEqual(mockResponse)
  })

  it('deleteLocale should DELETE /locales/:id', async () => {
    const id = 1
    const mockResponse = { data: { success: true } }
    request.delete.mockResolvedValue(mockResponse)

    const result = await deleteLocale(id)

    expect(request.delete).toHaveBeenCalledTimes(1)
    expect(request.delete).toHaveBeenCalledWith(`/locales/${id}`)
    expect(result).toEqual(mockResponse)
  })

  it('setChannel should store channel code in localStorage', () => {
    const channelCode = 'default_channel'
    setChannel(channelCode)
    expect(localStorage.getItem('app_channel')).toBe(channelCode)
  })

  it('getStoredChannel should return stored channel code', () => {
    const channelCode = 'test_channel'
    localStorage.setItem('app_channel', channelCode)
    expect(getStoredChannel()).toBe(channelCode)
  })

  it('getStoredChannel should return null when not stored', () => {
    expect(getStoredChannel()).toBeNull()
  })

  it('clearChannel should remove channel code from localStorage', () => {
    localStorage.setItem('app_channel', 'channel_to_clear')
    clearChannel()
    expect(localStorage.getItem('app_channel')).toBeNull()
  })

  it('setCurrency should store currency object in localStorage', () => {
    const currency = { code: 'USD', symbol: '$', name: 'US Dollar', decimals: 2 }
    setCurrency(currency)
    expect(JSON.parse(localStorage.getItem('app_currency'))).toEqual(currency)
  })

  it('getStoredCurrency should return stored currency object', () => {
    const currency = { code: 'EUR', symbol: '€', name: 'Euro', decimals: 2 }
    localStorage.setItem('app_currency', JSON.stringify(currency))
    expect(getStoredCurrency()).toEqual(currency)
  })

  it('getStoredCurrency should return null when not stored', () => {
    expect(getStoredCurrency()).toBeNull()
  })

  it('clearCurrency should remove currency from localStorage', () => {
    localStorage.setItem('app_currency', JSON.stringify({ code: 'CNY' }))
    clearCurrency()
    expect(localStorage.getItem('app_currency')).toBeNull()
  })
})
