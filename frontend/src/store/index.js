import Vue from 'vue'
import Vuex from 'vuex'
import i18n, { setLocale, SUPPORTED_LOCALES, DEFAULT_LOCALE, resolveInitialLocale } from '@/locales'
import { fetchAvailableLocales, updateServerLocale } from '@/api/locale'

Vue.use(Vuex)

export default new Vuex.Store({
  state: {
    locale: resolveInitialLocale(),
    availableLocales: { ...SUPPORTED_LOCALES },
    token: localStorage.getItem('token') || '',
    userInfo: JSON.parse(localStorage.getItem('userInfo') || 'null')
  },
  getters: {
    isLogin: state => !!state.token,
    userInfo: state => state.userInfo,
    currentLocale: state => state.locale,
    currentLocaleInfo: state => state.availableLocales[state.locale] || state.availableLocales[DEFAULT_LOCALE],
    availableLocaleList: state => Object.entries(state.availableLocales).map(([code, info]) => ({
      code,
      ...info
    }))
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
    }
  },
  actions: {
    async changeLocale({ commit, state }, locale) {
      if (!state.availableLocales[locale]) {
        console.warn(`[store] Unsupported locale: ${locale}`)
        return state.locale
      }
      const applied = setLocale(locale)
      commit('SET_LOCALE', applied)
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
      } catch (e) {}
    },
    async logout({ commit }) {
      commit('CLEAR_AUTH')
    }
  }
})
