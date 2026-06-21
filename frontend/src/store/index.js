import Vue from 'vue'
import Vuex from 'vuex'
import i18n, { setLocale, SUPPORTED_LOCALES, DEFAULT_LOCALE, resolveInitialLocale, loadElementUILocale, mergeLocaleMessages } from '@/locales'
import { fetchAvailableLocales, updateServerLocale, fetchLocaleMessages, getStoredChannel, setChannel, clearChannel, getStoredCurrency, setCurrency, clearCurrency } from '@/api/locale'
import { fetchEnabledChannels, fetchChannelCurrency } from '@/api/channel'
import { fetchEnabledCurrencies } from '@/api/currency'
import { fetchActiveExchangeRates, convertCurrency, getExchangeRate } from '@/api/exchangeRate'
import ElementUILocale from 'element-ui/lib/locale'

Vue.use(Vuex)

export const DEFAULT_CURRENCY = { code: 'CNY', symbol: '¥', name: '人民币', decimals: 2 }

export const AVAILABLE_CURRENCIES = {
  CNY: { code: 'CNY', symbol: '¥', name: '人民币', decimals: 2 },
  USD: { code: 'USD', symbol: '$', name: '美元', decimals: 2 },
  EUR: { code: 'EUR', symbol: '€', name: '欧元', decimals: 2 },
  BRL: { code: 'BRL', symbol: 'R$', name: '巴西雷亚尔', decimals: 2 },
  RUB: { code: 'RUB', symbol: '₽', name: '俄罗斯卢布', decimals: 2 }
}

export default new Vuex.Store({
  state: {
    locale: resolveInitialLocale(),
    availableLocales: { ...SUPPORTED_LOCALES },
    token: localStorage.getItem('token') || '',
    userInfo: JSON.parse(localStorage.getItem('userInfo') || 'null'),
    channels: [],
    currentChannel: getStoredChannel() || '',
    currencies: Object.values(AVAILABLE_CURRENCIES),
    currency: getStoredCurrency() || { ...DEFAULT_CURRENCY },
    exchangeRates: [],
    rateCache: {}
  },
  getters: {
    isLogin: state => !!state.token,
    userInfo: state => state.userInfo,
    currentLocale: state => state.locale,
    currentLocaleInfo: state => state.availableLocales[state.locale] || state.availableLocales[DEFAULT_LOCALE],
    availableLocaleList: state => Object.entries(state.availableLocales).map(([code, info]) => ({
      code,
      ...info
    })),
    currentChannel: state => state.currentChannel,
    availableChannels: state => state.channels,
    currentChannelInfo: state => state.channels.find(c => c.code === state.currentChannel) || null,
    currentCurrency: state => state.currency,
    availableCurrencies: state => state.currencies.length > 0 ? state.currencies : Object.values(AVAILABLE_CURRENCIES),
    availableCurrencyMap: (state, getters) => {
      const map = {}
      getters.availableCurrencies.forEach(c => {
        map[c.code] = c
      })
      return map
    },
    activeExchangeRates: state => state.exchangeRates
  },
  mutations: {
    SET_LOCALE(state, locale) {
      state.locale = locale
    },
    SET_AVAILABLE_LOCALES(state, locales) {
      const merged = {}
      const baseLocales = { ...SUPPORTED_LOCALES }
      Object.keys(baseLocales).forEach(code => {
        merged[code] = { ...baseLocales[code], ...(locales[code] || {}) }
      })
      Object.keys(locales).forEach(code => {
        if (!merged[code]) {
          merged[code] = { ...locales[code] }
        }
      })
      state.availableLocales = merged
    },
    SET_TOKEN(state, token) {
      state.token = token
      localStorage.setItem('token', token)
    },
    SET_USER_INFO(state, userInfo) {
      state.userInfo = userInfo
      localStorage.setItem('userInfo', JSON.stringify(userInfo))
    },
    CLEAR_AUTH(state) {
      state.token = ''
      state.userInfo = null
      localStorage.removeItem('token')
      localStorage.removeItem('userInfo')
    },
    SET_CHANNELS(state, channels) {
      state.channels = channels
    },
    SET_CURRENT_CHANNEL(state, channelCode) {
      state.currentChannel = channelCode
    },
    SET_CURRENCIES(state, currencies) {
      state.currencies = currencies
    },
    SET_CURRENCY(state, currency) {
      state.currency = currency
    },
    SET_EXCHANGE_RATES(state, rates) {
      state.exchangeRates = rates
    },
    SET_RATE_CACHE(state, { key, value }) {
      state.rateCache[key] = value
    },
    CLEAR_RATE_CACHE(state) {
      state.rateCache = {}
    }
  },
  actions: {
    async initializeApp({ dispatch, commit }) {
      const initialLocale = resolveInitialLocale()
      commit('SET_LOCALE', initialLocale)
      setLocale(initialLocale)

      try {
        const mod = await loadElementUILocale(initialLocale)
        if (mod && mod.default) {
          ElementUILocale.use(mod.default)
        }
      } catch (e) {}

      try {
        const res = await fetchLocaleMessages(initialLocale)
        if (res.data?.messages) {
          mergeLocaleMessages(initialLocale, res.data.messages)
        }
      } catch (e) {}

      try {
        await updateServerLocale(initialLocale)
      } catch (e) {}

      await Promise.all([
        dispatch('loadServerLocales'),
        dispatch('loadChannels'),
        dispatch('loadCurrencies'),
        dispatch('loadExchangeRates')
      ]).catch(() => {})

      return initialLocale
    },

    async changeLocale({ commit, state }, locale) {
      if (!state.availableLocales[locale]) {
        console.warn(`[store] Unsupported locale: ${locale}`)
        return state.locale
      }
      if (locale === state.locale) {
        return locale
      }

      const applied = setLocale(locale)
      commit('SET_LOCALE', applied)

      try {
        const mod = await loadElementUILocale(applied)
        if (mod && mod.default) {
          ElementUILocale.use(mod.default)
        }
      } catch (e) {}

      try {
        const res = await fetchLocaleMessages(applied)
        if (res.data?.messages) {
          mergeLocaleMessages(applied, res.data.messages)
        }
      } catch (e) {}

      try {
        await updateServerLocale(applied)
      } catch (e) {}

      return applied
    },

    async loadServerLocales({ commit }) {
      try {
        const res = await fetchAvailableLocales()
        if (res.data?.available) {
          commit('SET_AVAILABLE_LOCALES', res.data.available)
        }
        if (res.data?.currency?.available) {
          const serverCurrencies = Object.values(res.data.currency.available)
          if (serverCurrencies.length > 0) {
            commit('SET_CURRENCIES', serverCurrencies)
          }
        }
        if (res.data?.currency?.current) {
          const currency = res.data.currency.current
          setCurrency(currency)
          commit('SET_CURRENCY', currency)
        }
      } catch (e) {}
    },

    async loadChannels({ commit }) {
      try {
        const res = await fetchEnabledChannels()
        if (res.data?.data) {
          commit('SET_CHANNELS', res.data.data)
        }
      } catch (e) {}
    },

    async loadCurrencies({ commit }) {
      try {
        const res = await fetchEnabledCurrencies()
        if (res.data?.data && Array.isArray(res.data.data)) {
          const currencies = res.data.data.map(c => ({
            code: c.code,
            name: c.name,
            symbol: c.symbol,
            decimals: c.decimals ?? 2
          }))
          if (currencies.length > 0) {
            commit('SET_CURRENCIES', currencies)
          }
        }
      } catch (e) {}
    },

    async loadExchangeRates({ commit }) {
      try {
        const res = await fetchActiveExchangeRates()
        if (res.data?.data) {
          commit('SET_EXCHANGE_RATES', res.data.data)
        }
      } catch (e) {}
    },

    async changeChannel({ commit, dispatch, state }, channelCode) {
      if (channelCode === state.currentChannel) {
        return channelCode
      }

      setChannel(channelCode)
      commit('SET_CURRENT_CHANNEL', channelCode)

      let newLocale = state.locale

      try {
        const res = await fetchAvailableLocales()
        if (res.data?.current && res.data.current !== state.locale) {
          newLocale = await dispatch('changeLocale', res.data.current)
        }
      } catch (e) {}

      try {
        const localeRes = await fetchLocaleMessages(newLocale)
        if (localeRes.data?.messages) {
          mergeLocaleMessages(newLocale, localeRes.data.messages)
        }
      } catch (e) {}

      if (channelCode) {
        try {
          const currencyRes = await fetchChannelCurrency(channelCode)
          if (currencyRes.data?.success && currencyRes.data?.data) {
            const currency = currencyRes.data.data
            setCurrency(currency)
            commit('SET_CURRENCY', currency)
          }
        } catch (e) {}
      } else {
        const defaultCurrency = { ...DEFAULT_CURRENCY }
        setCurrency(defaultCurrency)
        commit('SET_CURRENCY', defaultCurrency)
      }

      return channelCode
    },

    async clearChannel({ commit }) {
      clearChannel()
      commit('SET_CURRENT_CHANNEL', '')
      const defaultCurrency = { ...DEFAULT_CURRENCY }
      clearCurrency()
      commit('SET_CURRENCY', defaultCurrency)
    },

    async changeCurrency({ commit, state }, currency) {
      if (currency && currency.code) {
        const targetCurrency = state.currencies.find(c => c.code === currency.code) || currency
        setCurrency(targetCurrency)
        commit('SET_CURRENCY', targetCurrency)
        return targetCurrency
      }
      return currency
    },

    async convertAmount({ commit, state, getters }, { amount, fromCode, toCode }) {
      if (!fromCode) fromCode = state.currency.code
      if (!toCode) return { success: false, message: 'Target currency required' }
      if (fromCode === toCode) {
        const currency = getters.availableCurrencyMap[toCode] || DEFAULT_CURRENCY
        return {
          success: true,
          amount,
          from_currency: fromCode,
          to_currency: toCode,
          rate: 1,
          converted_amount: amount,
          formatted_from: formatCurrency(amount, fromCode, getters),
          formatted_to: formatCurrency(amount, toCode, getters)
        }
      }

      const cacheKey = `${fromCode}-${toCode}`
      if (state.rateCache[cacheKey]) {
        const rate = state.rateCache[cacheKey]
        const converted = roundTo(amount * rate, getters.availableCurrencyMap[toCode]?.decimals ?? 2)
        return {
          success: true,
          amount,
          from_currency: fromCode,
          to_currency: toCode,
          rate,
          converted_amount: converted,
          formatted_from: formatCurrency(amount, fromCode, getters),
          formatted_to: formatCurrency(converted, toCode, getters)
        }
      }

      try {
        const res = await convertCurrency({ amount, from_currency_code: fromCode, to_currency_code: toCode })
        if (res.data?.success) {
          commit('SET_RATE_CACHE', { key: cacheKey, value: res.data.rate })
          return res.data
        }
        return { success: false, message: res.data?.message || 'Conversion failed' }
      } catch (e) {
        const localRate = findLocalRate(state.exchangeRates, fromCode, toCode)
        if (localRate) {
          commit('SET_RATE_CACHE', { key: cacheKey, value: localRate })
          const converted = roundTo(amount * localRate, getters.availableCurrencyMap[toCode]?.decimals ?? 2)
          return {
            success: true,
            amount,
            from_currency: fromCode,
            to_currency: toCode,
            rate: localRate,
            converted_amount: converted,
            formatted_from: formatCurrency(amount, fromCode, getters),
            formatted_to: formatCurrency(converted, toCode, getters)
          }
        }
        return { success: false, message: 'Exchange rate not available' }
      }
    },

    async getRate({ commit, state }, { fromCode, toCode }) {
      if (fromCode === toCode) return 1
      const cacheKey = `${fromCode}-${toCode}`
      if (state.rateCache[cacheKey]) return state.rateCache[cacheKey]

      try {
        const res = await getExchangeRate({ from_currency_code: fromCode, to_currency_code: toCode })
        if (res.data?.success && res.data?.data?.rate) {
          commit('SET_RATE_CACHE', { key: cacheKey, value: res.data.data.rate })
          return res.data.data.rate
        }
      } catch (e) {}

      const localRate = findLocalRate(state.exchangeRates, fromCode, toCode)
      if (localRate) {
        commit('SET_RATE_CACHE', { key: cacheKey, value: localRate })
        return localRate
      }
      return null
    },

    async logout({ commit }) {
      commit('CLEAR_AUTH')
    }
  }
})

function findLocalRate(rates, fromCode, toCode) {
  const direct = rates.find(r => r.from_currency_code === fromCode && r.to_currency_code === toCode)
  if (direct) return direct.rate

  const reverse = rates.find(r => r.from_currency_code === toCode && r.to_currency_code === fromCode)
  if (reverse && reverse.rate) return 1 / reverse.rate

  const fromUSD = rates.find(r => r.from_currency_code === 'USD' && r.to_currency_code === fromCode)
  const toUSD = rates.find(r => r.from_currency_code === 'USD' && r.to_currency_code === toCode)
  if (fromUSD && toUSD && fromUSD.rate && toUSD.rate) {
    return toUSD.rate / fromUSD.rate
  }

  return null
}

function formatCurrency(amount, code, getters) {
  const currency = getters.availableCurrencyMap[code] || DEFAULT_CURRENCY
  const decimals = currency.decimals ?? 2
  const symbol = currency.symbol || ''
  const formatted = numberFormat(amount, decimals)
  return `${symbol}${formatted}`
}

function numberFormat(num, decimals) {
  return Number(num).toLocaleString('en-US', {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals
  })
}

function roundTo(num, decimals) {
  const factor = Math.pow(10, decimals)
  return Math.round(num * factor) / factor
}

