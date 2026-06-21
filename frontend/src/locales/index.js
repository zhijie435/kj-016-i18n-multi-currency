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

const packageMessages = {}

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

function mergeMessages(locale, key, messages) {
  if (!i18n.messages[locale]) {
    i18n.messages[locale] = {}
  }
  if (!i18n.messages[locale][key]) {
    i18n.messages[locale][key] = {}
  }
  i18n.messages[locale][key] = {
    ...i18n.messages[locale][key],
    ...messages
  }
}

const BASE_MESSAGE_KEYS = ['common', 'menu', 'auth', 'validation', 'pagination', 'passwords']

export function mergeLocaleMessages(locale, messages) {
  if (!messages) return
  BASE_MESSAGE_KEYS.forEach(key => {
    if (messages[key] && typeof messages[key] === 'object') {
      mergeMessages(locale, key, messages[key])
    }
  })
  if (messages.packages && typeof messages.packages === 'object') {
    mergePackageMessages(locale, messages.packages)
  }
}

export function mergePackageMessages(locale, packages) {
  if (!packageMessages[locale]) {
    packageMessages[locale] = {}
  }
  Object.keys(packages).forEach(packageKey => {
    packageMessages[locale][packageKey] = packages[packageKey]
    mergeMessages(locale, packageKey, packages[packageKey])
  })
}

export function getPackageMessage(locale, packageKey, key) {
  return packageMessages[locale]?.[packageKey]?.[key] || `[${packageKey}.${key}]`
}

const baseMessages = {
  zh_CN: zhCN,
  en: en,
  pt_BR: ptBR,
  ru: ru
}

const messages = {}
Object.keys(baseMessages).forEach(locale => {
  messages[locale] = { ...baseMessages[locale] }
})

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
  if (i18n.locale === locale) {
    return locale
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
