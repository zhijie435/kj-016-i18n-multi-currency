import { createLocalVue, shallowMount } from '@vue/test-utils'
import Vuex from 'vuex'
import VueI18n from 'vue-i18n'
import ElementUI from 'element-ui'
import CurrencySwitcher from '@/components/CurrencySwitcher.vue'

const localVue = createLocalVue()
localVue.use(Vuex)
localVue.use(VueI18n)
localVue.use(ElementUI)

const i18n = new VueI18n({
  locale: 'zh_CN',
  messages: {
    zh_CN: {
      common: {
        switch_currency: '切换货币',
        currency_changed: '货币已切换'
      }
    }
  },
  silentTranslationWarn: true,
  silentFallbackWarn: true
})

const AVAILABLE_CURRENCIES = [
  { code: 'CNY', symbol: '¥', name: '人民币', decimals: 2 },
  { code: 'USD', symbol: '$', name: '美元', decimals: 2 },
  { code: 'EUR', symbol: '€', name: '欧元', decimals: 2 },
  { code: 'BRL', symbol: 'R$', name: '巴西雷亚尔', decimals: 2 },
  { code: 'RUB', symbol: '₽', name: '俄罗斯卢布', decimals: 2 }
]

const DEFAULT_CURRENCY = { code: 'CNY', symbol: '¥', name: '人民币', decimals: 2 }

function createStoreConfig(overrides = {}) {
  const changeCurrency = jest.fn().mockResolvedValue(DEFAULT_CURRENCY)
  const loadCurrencies = jest.fn().mockResolvedValue(null)

  const state = {
    currencies: [...AVAILABLE_CURRENCIES],
    currency: { ...DEFAULT_CURRENCY },
    ...overrides.state
  }

  const getters = {
    currentCurrency: state => state.currency,
    availableCurrencies: state => state.currencies.length > 0 ? state.currencies : [...AVAILABLE_CURRENCIES],
    ...overrides.getters
  }

  const actions = {
    changeCurrency,
    loadCurrencies,
    ...overrides.actions
  }

  return {
    state,
    getters,
    actions,
    mockChangeCurrency: changeCurrency,
    mockLoadCurrencies: loadCurrencies
  }
}

function createStore(config) {
  const { state, getters, actions } = config
  return new Vuex.Store({ state, getters, actions })
}

const mockMessageSuccess = jest.fn()

function createWrapper(store, options = {}) {
  return shallowMount(CurrencySwitcher, {
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

describe('CurrencySwitcher.vue', () => {
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
    expect(wrapper.find('.currency-switcher').exists()).toBe(true)
    expect(wrapper.find('.currency-switcher-trigger').exists()).toBe(true)
  })

  it('显示当前货币的 symbol 和 code', () => {
    const symbolEl = wrapper.find('.currency-symbol')
    const codeEl = wrapper.find('.currency-code')
    expect(symbolEl.exists()).toBe(true)
    expect(symbolEl.text()).toBe('¥')
    expect(codeEl.exists()).toBe(true)
    expect(codeEl.text()).toBe('CNY')
  })

  it('trigger 的 title 属性绑定了 $t("common.switch_currency")', () => {
    const trigger = wrapper.find('.currency-switcher-trigger')
    expect(trigger.attributes('title')).toBe('切换货币')
  })

  it('下拉列表渲染正确项数', () => {
    const items = wrapper.findAllComponents({ name: 'ElDropdownItem' })
    expect(items.length).toBe(AVAILABLE_CURRENCIES.length)
  })

  it('每个下拉项包含货币名称、symbol 和 code', () => {
    const items = wrapper.findAllComponents({ name: 'ElDropdownItem' })
    items.wrappers.forEach((item, index) => {
      const expected = AVAILABLE_CURRENCIES[index]
      const html = item.html()
      expect(html).toContain(expected.name)
      expect(html).toContain(`(${expected.symbol} ${expected.code})`)
    })
  })

  it('当前选中的货币对应的下拉项有 is-active class', () => {
    const items = wrapper.findAllComponents({ name: 'ElDropdownItem' })
    const activeItems = items.wrappers.filter(item => item.classes('is-active'))
    expect(activeItems.length).toBe(1)
    expect(activeItems[0].props('command')).toBe('CNY')
  })

  it('handleChange 调用 changeCurrency action 并显示成功消息', async () => {
    wrapper.vm.handleChange('USD')
    await wrapper.vm.$nextTick()
    expect(storeConfig.mockChangeCurrency).toHaveBeenCalled()
    const callArgs = storeConfig.mockChangeCurrency.mock.calls[0]
    expect(callArgs[1]).toEqual(AVAILABLE_CURRENCIES.find(c => c.code === 'USD'))
    await Promise.resolve()
    expect(mockMessageSuccess).toHaveBeenCalledWith('货币已切换')
  })

  it('handleChange 相同值时不重复调用 action', () => {
    wrapper.vm.handleChange('CNY')
    expect(storeConfig.mockChangeCurrency).not.toHaveBeenCalled()
    expect(mockMessageSuccess).not.toHaveBeenCalled()
  })

  it('handleChange 货币不在列表中时不调用 action', () => {
    wrapper.vm.handleChange('INVALID')
    expect(storeConfig.mockChangeCurrency).not.toHaveBeenCalled()
    expect(mockMessageSuccess).not.toHaveBeenCalled()
  })

  it('handleVisibleChange visible=true 时调用 loadCurrencies', () => {
    wrapper.vm.handleVisibleChange(true)
    expect(storeConfig.mockLoadCurrencies).toHaveBeenCalled()
  })

  it('handleVisibleChange visible=false 时不调用 loadCurrencies', () => {
    wrapper.vm.handleVisibleChange(false)
    expect(storeConfig.mockLoadCurrencies).not.toHaveBeenCalled()
  })

  it('空可用货币列表时回退到默认列表', () => {
    const emptyConfig = createStoreConfig({
      state: { currencies: [], currency: { ...DEFAULT_CURRENCY } },
      getters: {
        availableCurrencies: () => [...AVAILABLE_CURRENCIES]
      }
    })
    const emptyStore = createStore(emptyConfig)
    const w = createWrapper(emptyStore)
    expect(w.exists()).toBe(true)
    const items = w.findAllComponents({ name: 'ElDropdownItem' })
    expect(items.length).toBe(AVAILABLE_CURRENCIES.length)
    w.destroy()
  })

  it('当前货币不在可用列表中时仍能显示', () => {
    const invalidConfig = createStoreConfig({
      state: {
        currencies: [...AVAILABLE_CURRENCIES],
        currency: { code: 'INVALID', symbol: '?', name: '未知货币', decimals: 2 }
      }
    })
    const invalidStore = createStore(invalidConfig)
    const w = createWrapper(invalidStore)
    const symbolEl = w.find('.currency-symbol')
    const codeEl = w.find('.currency-code')
    expect(symbolEl.text()).toBe('?')
    expect(codeEl.text()).toBe('INVALID')
    w.destroy()
  })

  it('切换到不同货币后下拉项的 is-active 正确更新', () => {
    const newConfig = createStoreConfig({
      state: {
        currencies: [...AVAILABLE_CURRENCIES],
        currency: { code: 'USD', symbol: '$', name: '美元', decimals: 2 }
      }
    })
    const newStore = createStore(newConfig)
    const w = createWrapper(newStore)
    const items = w.findAllComponents({ name: 'ElDropdownItem' })
    const activeItems = items.wrappers.filter(item => item.classes('is-active'))
    expect(activeItems.length).toBe(1)
    expect(activeItems[0].props('command')).toBe('USD')
    w.destroy()
  })

  it('loadCurrencies 失败时不抛出异常', () => {
    const failingConfig = createStoreConfig({
      actions: {
        loadCurrencies: jest.fn().mockRejectedValue(new Error('Network error'))
      }
    })
    const failingStore = createStore(failingConfig)
    const w = createWrapper(failingStore)
    expect(() => w.vm.handleVisibleChange(true)).not.toThrow()
    w.destroy()
  })

  it('el-dropdown 绑定了正确的 trigger', () => {
    const dropdown = wrapper.findComponent({ name: 'ElDropdown' })
    expect(dropdown.exists()).toBe(true)
    expect(dropdown.props('trigger')).toBe('click')
  })
})
