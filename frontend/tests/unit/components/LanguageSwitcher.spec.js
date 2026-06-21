import { createLocalVue, shallowMount } from '@vue/test-utils'
import Vuex from 'vuex'
import VueI18n from 'vue-i18n'
import ElementUI from 'element-ui'
import LanguageSwitcher from '@/components/LanguageSwitcher.vue'

const localVue = createLocalVue()
localVue.use(Vuex)
localVue.use(VueI18n)
localVue.use(ElementUI)

const i18n = new VueI18n({
  locale: 'zh_CN',
  messages: {
    zh_CN: {
      common: {
        switch_language: '切换语言',
        language_changed: '语言已切换'
      }
    }
  },
  silentTranslationWarn: true,
  silentFallbackWarn: true
})

const SUPPORTED_LOCALES = {
  zh_CN: { name: '简体中文', native: '简体中文', flag: '🇨🇳', elementLocale: 'zh-CN' },
  en: { name: 'English', native: 'English', flag: '🇺🇸', elementLocale: 'en' },
  pt_BR: { name: 'Português', native: 'Português', flag: '🇧🇷', elementLocale: 'pt-br' },
  ru: { name: 'Русский', native: 'Русский', flag: '🇷🇺', elementLocale: 'ru' }
}

const availableLocaleList = Object.entries(SUPPORTED_LOCALES).map(([code, info]) => ({
  code,
  ...info
}))

function createStoreConfig(overrides = {}) {
  const changeLocale = jest.fn().mockResolvedValue('zh_CN')
  const loadServerLocales = jest.fn().mockResolvedValue(null)

  const state = {
    locale: 'zh_CN',
    availableLocales: { ...SUPPORTED_LOCALES },
    ...overrides.state
  }

  const getters = {
    currentLocale: state => state.locale,
    currentLocaleInfo: state => state.availableLocales[state.locale] || SUPPORTED_LOCALES['zh_CN'],
    availableLocaleList: state => Object.entries(state.availableLocales).map(([code, info]) => ({
      code,
      ...info
    })),
    ...overrides.getters
  }

  const actions = {
    changeLocale,
    loadServerLocales,
    ...overrides.actions
  }

  return {
    state,
    getters,
    actions,
    mockChangeLocale: changeLocale,
    mockLoadServerLocales: loadServerLocales
  }
}

function createStore(config) {
  const { state, getters, actions } = config
  return new Vuex.Store({ state, getters, actions })
}

const mockMessageSuccess = jest.fn()

function createWrapper(store, options = {}) {
  return shallowMount(LanguageSwitcher, {
    localVue,
    store,
    i18n,
    mocks: {
      $message: {
        success: mockMessageSuccess
      },
      ...options.mocks
    },
    ...options
  })
}

describe('LanguageSwitcher.vue', () => {
  let storeConfig
  let store
  let wrapper

  beforeEach(() => {
    jest.clearAllMocks()
    storeConfig = createStoreConfig()
    store = createStore(storeConfig)
    wrapper = createWrapper(store)
  })

  afterEach(() => {
    wrapper.destroy()
  })

  it('组件正确渲染', () => {
    expect(wrapper.exists()).toBe(true)
    expect(wrapper.find('.locale-switcher').exists()).toBe(true)
    expect(wrapper.find('.locale-switcher-trigger').exists()).toBe(true)
  })

  it('显示当前选中 locale 的 flag 和 native name', () => {
    const flagEl = wrapper.find('.locale-flag')
    const labelEl = wrapper.find('.locale-label')
    expect(flagEl.exists()).toBe(true)
    expect(flagEl.text()).toBe('🇨🇳')
    expect(labelEl.exists()).toBe(true)
    expect(labelEl.text()).toBe('简体中文')
  })

  it('trigger 的 title 属性绑定了 $t("common.switch_language")', () => {
    const trigger = wrapper.find('.locale-switcher-trigger')
    expect(trigger.attributes('title')).toBe('切换语言')
  })

  it('下拉列表渲染正确项数', () => {
    const items = wrapper.findAllComponents({ name: 'ElDropdownItem' })
    expect(items.length).toBe(availableLocaleList.length)
  })

  it('每个下拉项包含 flag、native 和 name', () => {
    const items = wrapper.findAllComponents({ name: 'ElDropdownItem' })
    items.wrappers.forEach((item, index) => {
      const expected = availableLocaleList[index]
      const html = item.html()
      expect(html).toContain(expected.flag)
      expect(html).toContain(expected.native)
      expect(html).toContain(`(${expected.name})`)
    })
  })

  it('当前选中的 locale 对应的下拉项有 is-active class', () => {
    const items = wrapper.findAllComponents({ name: 'ElDropdownItem' })
    const activeItems = items.wrappers.filter(item => item.classes('is-active'))
    expect(activeItems.length).toBe(1)
    expect(activeItems[0].props('command')).toBe('zh_CN')
  })

  it('handleChange 调用 changeLocale action 并显示成功消息', async () => {
    wrapper.vm.handleChange('en')
    await wrapper.vm.$nextTick()
    expect(storeConfig.mockChangeLocale).toHaveBeenCalledWith(expect.any(Object), 'en')
    await Promise.resolve()
    expect(mockMessageSuccess).toHaveBeenCalledWith('语言已切换')
  })

  it('handleChange 相同值时不重复调用 action', () => {
    wrapper.vm.handleChange('zh_CN')
    expect(storeConfig.mockChangeLocale).not.toHaveBeenCalled()
    expect(mockMessageSuccess).not.toHaveBeenCalled()
  })

  it('handleVisibleChange visible=true 时调用 loadServerLocales', () => {
    wrapper.vm.handleVisibleChange(true)
    expect(storeConfig.mockLoadServerLocales).toHaveBeenCalled()
  })

  it('handleVisibleChange visible=false 时不调用 loadServerLocales', () => {
    wrapper.vm.handleVisibleChange(false)
    expect(storeConfig.mockLoadServerLocales).not.toHaveBeenCalled()
  })

  it('空可用语言列表时不崩溃', () => {
    const emptyConfig = createStoreConfig({
      state: { locale: 'zh_CN', availableLocales: {} },
      getters: {
        currentLocaleInfo: () => SUPPORTED_LOCALES['zh_CN'],
        availableLocaleList: () => []
      }
    })
    const emptyStore = createStore(emptyConfig)
    const w = createWrapper(emptyStore)
    expect(w.exists()).toBe(true)
    const items = w.findAllComponents({ name: 'ElDropdownItem' })
    expect(items.length).toBe(0)
    w.destroy()
  })

  it('当前 locale 不在可用列表中时回退到默认值', () => {
    const invalidConfig = createStoreConfig({
      state: { locale: 'invalid_locale', availableLocales: { ...SUPPORTED_LOCALES } }
    })
    const invalidStore = createStore(invalidConfig)
    const w = createWrapper(invalidStore)
    const labelEl = w.find('.locale-label')
    expect(labelEl.text()).toBe('简体中文')
    w.destroy()
  })

  it('切换到不同语言后下拉项的 is-active 正确更新', () => {
    const newConfig = createStoreConfig({
      state: { locale: 'en', availableLocales: { ...SUPPORTED_LOCALES } }
    })
    const newStore = createStore(newConfig)
    const w = createWrapper(newStore)
    const items = w.findAllComponents({ name: 'ElDropdownItem' })
    const activeItems = items.wrappers.filter(item => item.classes('is-active'))
    expect(activeItems.length).toBe(1)
    expect(activeItems[0].props('command')).toBe('en')
    w.destroy()
  })

  it('loadServerLocales 失败时不抛出异常', async () => {
    const failingConfig = createStoreConfig({
      actions: {
        loadServerLocales: jest.fn().mockRejectedValue(new Error('Network error'))
      }
    })
    const failingStore = createStore(failingConfig)
    const w = createWrapper(failingStore)
    expect(() => w.vm.handleVisibleChange(true)).not.toThrow()
    w.destroy()
  })

  it('el-dropdown 绑定了正确的事件', () => {
    const dropdown = wrapper.findComponent({ name: 'ElDropdown' })
    expect(dropdown.exists()).toBe(true)
    expect(dropdown.props('trigger')).toBe('click')
  })
})
