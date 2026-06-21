import Vue from 'vue'
import Vuex from 'vuex'

jest.mock('@/api/locale', () => ({
  fetchAvailableLocales: jest.fn(),
  updateServerLocale: jest.fn(),
  fetchLocaleMessages: jest.fn(),
  getStoredChannel: jest.fn(() => ''),
  setChannel: jest.fn(),
  clearChannel: jest.fn(),
  getStoredCurrency: jest.fn(() => null),
  setCurrency: jest.fn(),
  clearCurrency: jest.fn()
}))

jest.mock('@/api/channel', () => ({
  fetchEnabledChannels: jest.fn(),
  fetchChannelCurrency: jest.fn()
}))

jest.mock('@/api/currency', () => ({
  fetchEnabledCurrencies: jest.fn()
}))

jest.mock('@/api/exchangeRate', () => ({
  fetchActiveExchangeRates: jest.fn(),
  convertCurrency: jest.fn(),
  getExchangeRate: jest.fn()
}))

jest.mock('element-ui/lib/locale', () => ({
  use: jest.fn()
}))

jest.mock('@/locales', () => {
  const mockSetLocale = jest.fn(locale => locale)
  const mockMergeLocaleMessages = jest.fn()
  const mockLoadElementUILocale = jest.fn(() => Promise.resolve({ default: {} }))
  const mockResolveInitialLocale = jest.fn(() => 'zh_CN')

  const SUPPORTED_LOCALES = {
    zh_CN: { name: '简体中文', native: '简体中文', flag: '🇨🇳', elementLocale: 'zh-CN' },
    en: { name: 'English', native: 'English', flag: '🇺🇸', elementLocale: 'en' },
    pt_BR: { name: 'Português', native: 'Português', flag: '🇧🇷', elementLocale: 'pt-br' },
    ru: { name: 'Русский', native: 'Русский', flag: '🇷🇺', elementLocale: 'ru' }
  }
  const DEFAULT_LOCALE = 'zh_CN'

  const VueI18n = require('vue-i18n')
  const Vue = require('vue')
  Vue.use(VueI18n)

  const i18n = new VueI18n({
    locale: 'zh_CN',
    fallbackLocale: 'zh_CN',
    messages: {
      zh_CN: { common: {} },
      en: { common: {} },
      pt_BR: { common: {} },
      ru: { common: {} }
    },
    silentTranslationWarn: true,
    silentFallbackWarn: true
  })

  return {
    __esModule: true,
    default: i18n,
    setLocale: mockSetLocale,
    SUPPORTED_LOCALES,
    DEFAULT_LOCALE,
    resolveInitialLocale: mockResolveInitialLocale,
    loadElementUILocale: mockLoadElementUILocale,
    mergeLocaleMessages: mockMergeLocaleMessages
  }
})

Vue.use(Vuex)

describe('Vuex Store', () => {
  let store
  let apiLocale
  let apiChannel
  let apiExchangeRate
  let localesModule
  let DEFAULT_CURRENCY
  let AVAILABLE_CURRENCIES

  beforeEach(() => {
    jest.resetModules()
    localStorage.clear()

    apiLocale = require('@/api/locale')
    apiChannel = require('@/api/channel')
    apiExchangeRate = require('@/api/exchangeRate')
    localesModule = require('@/locales')

    jest.clearAllMocks()

    apiLocale.getStoredChannel.mockReturnValue('')
    apiLocale.getStoredCurrency.mockReturnValue(null)
    localesModule.resolveInitialLocale.mockReturnValue('zh_CN')
    localesModule.setLocale.mockImplementation(locale => locale)

    const storeModule = require('@/store/index')
    store = storeModule.default
    DEFAULT_CURRENCY = storeModule.DEFAULT_CURRENCY
    AVAILABLE_CURRENCIES = storeModule.AVAILABLE_CURRENCIES
  })

  describe('State', () => {
    it('should have default locale state', () => {
      expect(store.state.locale).toBe('zh_CN')
    })

    it('should have default channels state', () => {
      expect(store.state.channels).toEqual([])
    })

    it('should have default currentChannel state', () => {
      expect(store.state.currentChannel).toBe('')
    })

    it('should have default currencies from AVAILABLE_CURRENCIES', () => {
      expect(store.state.currencies).toEqual(Object.values(AVAILABLE_CURRENCIES))
    })

    it('should have default currency as DEFAULT_CURRENCY', () => {
      expect(store.state.currency).toEqual({ ...DEFAULT_CURRENCY })
    })

    it('should have default exchangeRates state', () => {
      expect(store.state.exchangeRates).toEqual([])
    })

    it('should have default rateCache state', () => {
      expect(store.state.rateCache).toEqual({})
    })
  })

  describe('Mutations', () => {
    it('SET_LOCALE should update locale state', () => {
      store.commit('SET_LOCALE', 'en')
      expect(store.state.locale).toBe('en')
    })

    it('SET_AVAILABLE_LOCALES should merge with base locales', () => {
      const newLocales = {
        zh_CN: { name: 'Updated Chinese' },
        fr: { name: 'French', native: 'Français' }
      }
      store.commit('SET_AVAILABLE_LOCALES', newLocales)
      expect(store.state.availableLocales.zh_CN.name).toBe('Updated Chinese')
      expect(store.state.availableLocales.en).toBeDefined()
      expect(store.state.availableLocales.fr).toBeDefined()
      expect(store.state.availableLocales.fr.name).toBe('French')
    })

    it('SET_CHANNELS should update channels state', () => {
      const channels = [{ id: 1, code: 'default', name: 'Default' }]
      store.commit('SET_CHANNELS', channels)
      expect(store.state.channels).toEqual(channels)
    })

    it('SET_CURRENT_CHANNEL should update currentChannel state', () => {
      store.commit('SET_CURRENT_CHANNEL', 'test_channel')
      expect(store.state.currentChannel).toBe('test_channel')
    })

    it('SET_CURRENCIES should update currencies state', () => {
      const currencies = [{ code: 'USD', symbol: '$', name: 'US Dollar' }]
      store.commit('SET_CURRENCIES', currencies)
      expect(store.state.currencies).toEqual(currencies)
    })

    it('SET_CURRENCY should update currency state', () => {
      const currency = { code: 'USD', symbol: '$', name: 'US Dollar', decimals: 2 }
      store.commit('SET_CURRENCY', currency)
      expect(store.state.currency).toEqual(currency)
    })

    it('SET_EXCHANGE_RATES should update exchangeRates state', () => {
      const rates = [{ id: 1, from_currency_code: 'USD', to_currency_code: 'CNY', rate: 7.2 }]
      store.commit('SET_EXCHANGE_RATES', rates)
      expect(store.state.exchangeRates).toEqual(rates)
    })

    it('SET_RATE_CACHE should add rate to cache', () => {
      store.commit('SET_RATE_CACHE', { key: 'USD-CNY', value: 7.2 })
      expect(store.state.rateCache['USD-CNY']).toBe(7.2)
    })

    it('CLEAR_RATE_CACHE should reset rateCache to empty object', () => {
      store.commit('SET_RATE_CACHE', { key: 'USD-CNY', value: 7.2 })
      store.commit('CLEAR_RATE_CACHE')
      expect(store.state.rateCache).toEqual({})
    })
  })

  describe('Getters', () => {
    it('currentLocale should return state.locale', () => {
      store.commit('SET_LOCALE', 'en')
      expect(store.getters.currentLocale).toBe('en')
    })

    it('currentLocaleInfo should return locale info for current locale', () => {
      store.commit('SET_LOCALE', 'en')
      const info = store.getters.currentLocaleInfo
      expect(info).toBeDefined()
      expect(info.name).toBe('English')
    })

    it('currentLocaleInfo should fallback to DEFAULT_LOCALE for unsupported locale', () => {
      store.commit('SET_LOCALE', 'unsupported')
      const info = store.getters.currentLocaleInfo
      expect(info).toBeDefined()
      expect(info.name).toBe('简体中文')
    })

    it('availableLocaleList should return array of locale objects with code', () => {
      const list = store.getters.availableLocaleList
      expect(Array.isArray(list)).toBe(true)
      expect(list.length).toBeGreaterThan(0)
      expect(list[0]).toHaveProperty('code')
      expect(list[0]).toHaveProperty('name')
    })

    it('currentChannel should return state.currentChannel', () => {
      store.commit('SET_CURRENT_CHANNEL', 'channel1')
      expect(store.getters.currentChannel).toBe('channel1')
    })

    it('availableChannels should return state.channels', () => {
      const channels = [{ id: 1, code: 'ch1' }]
      store.commit('SET_CHANNELS', channels)
      expect(store.getters.availableChannels).toEqual(channels)
    })

    it('currentCurrency should return state.currency', () => {
      const currency = { code: 'EUR', symbol: '€', name: 'Euro', decimals: 2 }
      store.commit('SET_CURRENCY', currency)
      expect(store.getters.currentCurrency).toEqual(currency)
    })

    it('availableCurrencies should return state.currencies when not empty', () => {
      const currencies = [{ code: 'USD', symbol: '$' }]
      store.commit('SET_CURRENCIES', currencies)
      expect(store.getters.availableCurrencies).toEqual(currencies)
    })

    it('availableCurrencies should fallback to AVAILABLE_CURRENCIES when empty', () => {
      store.commit('SET_CURRENCIES', [])
      const result = store.getters.availableCurrencies
      expect(result.length).toBeGreaterThan(0)
      expect(result.find(c => c.code === 'CNY')).toBeDefined()
    })
  })

  describe('Actions', () => {
    it('changeLocale should commit SET_LOCALE for supported locale', async () => {
      localesModule.setLocale.mockReturnValue('en')
      apiLocale.fetchLocaleMessages.mockResolvedValue({ data: { messages: {} } })
      apiLocale.updateServerLocale.mockResolvedValue({})

      const result = await store.dispatch('changeLocale', 'en')

      expect(result).toBe('en')
      expect(store.state.locale).toBe('en')
      expect(localesModule.setLocale).toHaveBeenCalledWith('en')
      expect(apiLocale.updateServerLocale).toHaveBeenCalled()
    })

    it('changeLocale should not change when locale is same as current', async () => {
      store.commit('SET_LOCALE', 'zh_CN')

      const result = await store.dispatch('changeLocale', 'zh_CN')

      expect(result).toBe('zh_CN')
      expect(localesModule.setLocale).not.toHaveBeenCalled()
    })

    it('changeLocale should return current locale for unsupported locale', async () => {
      store.commit('SET_LOCALE', 'zh_CN')

      const result = await store.dispatch('changeLocale', 'unsupported')

      expect(result).toBe('zh_CN')
      expect(localesModule.setLocale).not.toHaveBeenCalled()
    })

    it('changeChannel should commit SET_CURRENT_CHANNEL and call setChannel', async () => {
      apiLocale.fetchAvailableLocales.mockResolvedValue({ data: {} })
      apiLocale.fetchLocaleMessages.mockResolvedValue({ data: { messages: {} } })
      apiChannel.fetchChannelCurrency.mockResolvedValue({ data: { success: true, data: { code: 'USD', symbol: '$', name: 'Dollar', decimals: 2 } } })

      const result = await store.dispatch('changeChannel', 'test_channel')

      expect(result).toBe('test_channel')
      expect(store.state.currentChannel).toBe('test_channel')
      expect(apiLocale.setChannel).toHaveBeenCalledWith('test_channel')
    })

    it('changeChannel should not change when channel is same as current', async () => {
      store.commit('SET_CURRENT_CHANNEL', 'same_channel')

      const result = await store.dispatch('changeChannel', 'same_channel')

      expect(result).toBe('same_channel')
      expect(apiLocale.setChannel).not.toHaveBeenCalled()
    })

    it('changeCurrency should commit SET_CURRENCY and call setCurrency', async () => {
      const currency = AVAILABLE_CURRENCIES.USD

      const result = await store.dispatch('changeCurrency', currency)

      expect(result).toEqual(currency)
      expect(store.state.currency).toEqual(currency)
      expect(apiLocale.setCurrency).toHaveBeenCalled()
    })

    it('changeCurrency should return input if no code', async () => {
      const result = await store.dispatch('changeCurrency', null)
      expect(result).toBeNull()
    })

    it('convertAmount should return same amount when fromCode equals toCode', async () => {
      const result = await store.dispatch('convertAmount', { amount: 100, fromCode: 'CNY', toCode: 'CNY' })
      expect(result.success).toBe(true)
      expect(result.amount).toBe(100)
      expect(result.converted_amount).toBe(100)
      expect(result.rate).toBe(1)
    })

    it('convertAmount should use cached rate if available', async () => {
      store.commit('SET_RATE_CACHE', { key: 'USD-CNY', value: 7.2 })
      const currencies = [
        { code: 'USD', symbol: '$', name: 'US Dollar', decimals: 2 },
        { code: 'CNY', symbol: '¥', name: '人民币', decimals: 2 }
      ]
      store.commit('SET_CURRENCIES', currencies)

      const result = await store.dispatch('convertAmount', { amount: 100, fromCode: 'USD', toCode: 'CNY' })

      expect(result.success).toBe(true)
      expect(result.rate).toBe(7.2)
      expect(result.converted_amount).toBe(720)
      expect(apiExchangeRate.convertCurrency).not.toHaveBeenCalled()
    })

    it('convertAmount should call API and cache result when no cache', async () => {
      apiExchangeRate.convertCurrency.mockResolvedValue({
        data: {
          success: true,
          amount: 100,
          from_currency: 'USD',
          to_currency: 'CNY',
          rate: 7.2,
          converted_amount: 720,
          formatted_from: '$100.00',
          formatted_to: '¥720.00'
        }
      })

      const result = await store.dispatch('convertAmount', { amount: 100, fromCode: 'USD', toCode: 'CNY' })

      expect(result.success).toBe(true)
      expect(result.rate).toBe(7.2)
      expect(apiExchangeRate.convertCurrency).toHaveBeenCalledWith({
        amount: 100,
        from_currency_code: 'USD',
        to_currency_code: 'CNY'
      })
      expect(store.state.rateCache['USD-CNY']).toBe(7.2)
    })

    it('getRate should return 1 when fromCode equals toCode', async () => {
      const result = await store.dispatch('getRate', { fromCode: 'USD', toCode: 'USD' })
      expect(result).toBe(1)
    })

    it('getRate should return cached rate if available', async () => {
      store.commit('SET_RATE_CACHE', { key: 'USD-CNY', value: 7.2 })
      const result = await store.dispatch('getRate', { fromCode: 'USD', toCode: 'CNY' })
      expect(result).toBe(7.2)
      expect(apiExchangeRate.getExchangeRate).not.toHaveBeenCalled()
    })

    it('getRate should call API and cache result when no cache', async () => {
      apiExchangeRate.getExchangeRate.mockResolvedValue({
        data: { success: true, data: { rate: 7.2 } }
      })

      const result = await store.dispatch('getRate', { fromCode: 'USD', toCode: 'CNY' })

      expect(result).toBe(7.2)
      expect(apiExchangeRate.getExchangeRate).toHaveBeenCalledWith({
        from_currency_code: 'USD',
        to_currency_code: 'CNY'
      })
      expect(store.state.rateCache['USD-CNY']).toBe(7.2)
    })

    it('getRate should fallback to local exchange rates', async () => {
      apiExchangeRate.getExchangeRate.mockRejectedValue(new Error('API Error'))
      store.commit('SET_EXCHANGE_RATES', [
        { from_currency_code: 'USD', to_currency_code: 'CNY', rate: 7.2 }
      ])

      const result = await store.dispatch('getRate', { fromCode: 'USD', toCode: 'CNY' })
      expect(result).toBe(7.2)
    })
  })
})
