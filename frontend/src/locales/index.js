import Vue from 'vue'
import VueI18n from 'vue-i18n'
import zhCN from './zh_CN'
import en from './en'
import ptBR from './pt_BR'
import ru from './ru'

Vue.use(VueI18n)

export const SUPPORTED_LOCALES = {
  zh_CN: { name: '简体中文', native: '简体中文', flag: '🇨🇳', elementLocale: 'zh-CN' },
  en:    { name: 'English',   native: 'English',   flag: '🇺🇸', elementLocale: 'en' },
  pt_BR: { name: 'Português', native: 'Português', flag: '🇧🇷', elementLocale: 'pt-br' },
  ru:    { name: 'Русский',   native: 'Русский',   flag: '🇷🇺', elementLocale: 'ru' }
}

export const DEFAULT_LOCALE = 'zh_CN'

export function getBrowserLocale() {
  const browserLang = (navigator.language || navigator.userLanguage || DEFAULT_LOCALE).replace('-', '_')
  return Object.keys(SUPPORTED_LOCALES).includes(browserLang)
    ? browserLang
    : Object.keys(SUPPORTED_LOCALES).find(l => browserLang.startsWith(l.split('_')[0])) || DEFAULT_LOCALE
}

export function getStoredLocale() {
  try {
    const stored = localStorage.getItem('app_locale')
    return stored && Object.keys(SUPPORTED_LOCALES).includes(stored) ? stored : null
  } catch (e) {
    return null
  }
}

export function resolveInitialLocale() {
  return getStoredLocale() || getBrowserLocale() || DEFAULT_LOCALE
}

const messages = {
  zh_CN: zhCN,
  en: en,
  pt_BR: ptBR,
  ru: ru
}

const i18n = new VueI18n({
  locale: resolveInitialLocale(),
  fallbackLocale: DEFAULT_LOCALE,
  messages,
  silentTranslationWarn: true,
  silentFallbackWarn: true
})

export function setLocale(locale) {
  if (!Object.keys(SUPPORTED_LOCALES).includes(locale)) {
    console.warn(`[i18n] Unsupported locale: ${locale}, fallback to ${DEFAULT_LOCALE}`)
    locale = DEFAULT_LOCALE
  }
  i18n.locale = locale
  try {
    localStorage.setItem('app_locale', locale)
  } catch (e) {}
  document.documentElement.setAttribute('lang', locale.replace('_', '-'))
  return locale
}

export function loadElementUILocale(locale) {
  const mapping = {
    zh_CN: () => import('element-ui/lib/locale/lang/zh-CN'),
    en:    () => import('element-ui/lib/locale/lang/en'),
    pt_BR: () => import('element-ui/lib/locale/lang/pt-br'),
    ru:    () => import('element-ui/lib/locale/lang/ru-RU')
  }
  return mapping[locale] ? mapping[locale]() : Promise.resolve(null)
}

export default i18n
