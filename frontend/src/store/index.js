import Vue from 'vue'
import Vuex from 'vuex'
import i18n, { setLocale, SUPPORTED_LOCALES, DEFAULT_LOCALE, resolveInitialLocale, loadElementUILocale, mergeLocaleMessages } from '@/locales'
import { fetchAvailableLocales, updateServerLocale, fetchLocaleMessages, getStoredChannel, setChannel, clearChannel, getStoredCurrency, setCurrency, clearCurrency } from '@/api/locale'
import { fetchEnabledChannels, fetchChannelCurrency } from '@/api/channel'
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
    currency: getStoredCurrency() || { ...DEFAULT_CURRENCY }
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
    availableCurrencies: state => Object.values(AVAILABLE_CURRENCIES)
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
    SET_CURRENCY(state, currency) {
      state.currency = currency
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
        dispatch('loadChannels')
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

    async changeCurrency({ commit }, currency) {
      setCurrency(currency)
      commit('SET_CURRENCY', currency)
      return currency
    },

    async logout({ commit }) {
      commit('CLEAR_AUTH')
    }
  }
})
