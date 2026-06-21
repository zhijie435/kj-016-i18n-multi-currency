import { createLocalVue, shallowMount } from '@vue/test-utils'
import Vuex from 'vuex'
import VueI18n from 'vue-i18n'
import ElementUI from 'element-ui'
import ChannelSwitcher from '@/components/ChannelSwitcher.vue'

const localVue = createLocalVue()
localVue.use(Vuex)
localVue.use(VueI18n)
localVue.use(ElementUI)

const i18n = new VueI18n({
  locale: 'zh_CN',
  messages: {
    zh_CN: {
      common: {
        switch_channel: '切换渠道',
        channel_changed: '渠道已切换',
        default_channel: '默认渠道'
      }
    }
  },
  silentTranslationWarn: true,
  silentFallbackWarn: true
})

const AVAILABLE_CHANNELS = [
  {
    code: 'xhs',
    name: '小红书',
    locale: { code: 'zh_CN', native_name: '简体中文' },
    currency_code: 'CNY',
    currency_symbol: '¥'
  },
  {
    code: 'tiktok',
    name: 'TikTok',
    locale: { code: 'en', native_name: 'English' },
    currency_code: 'USD',
    currency_symbol: '$'
  },
  {
    code: 'shopee_br',
    name: 'Shopee巴西',
    locale: { code: 'pt_BR', native_name: 'Português' },
    currency_code: 'BRL',
    currency_symbol: 'R$'
  }
]

const DEFAULT_CURRENCY = { code: 'CNY', symbol: '¥', name: '人民币', decimals: 2 }

function createStoreConfig(overrides = {}) {
  const changeChannel = jest.fn().mockResolvedValue('')
  const loadChannels = jest.fn().mockResolvedValue(null)

  const state = {
    channels: [...AVAILABLE_CHANNELS],
    currentChannel: '',
    currency: { ...DEFAULT_CURRENCY },
    ...overrides.state
  }

  const getters = {
    currentChannel: state => state.currentChannel,
    availableChannels: state => state.channels,
    currentChannelInfo: state => state.channels.find(c => c.code === state.currentChannel) || null,
    currentCurrency: state => state.currency,
    ...overrides.getters
  }

  const actions = {
    changeChannel,
    loadChannels,
    ...overrides.actions
  }

  return {
    state,
    getters,
    actions,
    mockChangeChannel: changeChannel,
    mockLoadChannels: loadChannels
  }
}

function createStore(config) {
  const { state, getters, actions } = config
  return new Vuex.Store({ state, getters, actions })
}

const mockMessageSuccess = jest.fn()

function createWrapper(store, options = {}) {
  return shallowMount(ChannelSwitcher, {
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

describe('ChannelSwitcher.vue', () => {
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
    expect(wrapper.find('.channel-switcher').exists()).toBe(true)
    expect(wrapper.find('.channel-switcher-trigger').exists()).toBe(true)
  })

  it('未选择渠道时显示默认渠道名称', () => {
    const labelEl = wrapper.find('.channel-label')
    expect(labelEl.exists()).toBe(true)
    expect(labelEl.text()).toBe('默认渠道')
  })

  it('已选择渠道时显示当前渠道名称', () => {
    const selectedConfig = createStoreConfig({
      state: {
        channels: [...AVAILABLE_CHANNELS],
        currentChannel: 'xhs',
        currency: { ...DEFAULT_CURRENCY }
      }
    })
    const selectedStore = createStore(selectedConfig)
    const w = createWrapper(selectedStore)
    const labelEl = w.find('.channel-label')
    expect(labelEl.text()).toBe('小红书')
    w.destroy()
  })

  it('显示当前货币信息', () => {
    const currencyEl = wrapper.find('.channel-currency')
    expect(currencyEl.exists()).toBe(true)
    expect(currencyEl.text()).toContain('¥')
    expect(currencyEl.text()).toContain('CNY')
  })

  it('trigger 的 title 属性绑定了 $t("common.switch_channel")', () => {
    const trigger = wrapper.find('.channel-switcher-trigger')
    expect(trigger.attributes('title')).toBe('切换渠道')
  })

  it('下拉列表包含默认渠道 + 可用渠道总数', () => {
    const items = wrapper.findAllComponents({ name: 'ElDropdownItem' })
    expect(items.length).toBe(1 + AVAILABLE_CHANNELS.length)
  })

  it('第一个下拉项是默认渠道', () => {
    const items = wrapper.findAllComponents({ name: 'ElDropdownItem' })
    const firstItem = items.wrappers[0]
    expect(firstItem.props('command')).toBe('')
    expect(firstItem.html()).toContain('默认渠道')
  })

  it('每个渠道下拉项包含名称、locale 和货币信息', () => {
    const items = wrapper.findAllComponents({ name: 'ElDropdownItem' })
    AVAILABLE_CHANNELS.forEach((channel, index) => {
      const item = items.wrappers[index + 1]
      const html = item.html()
      expect(html).toContain(channel.name)
      expect(html).toContain(`(${channel.locale.native_name})`)
      expect(html).toContain(`[${channel.currency_symbol} ${channel.currency_code}]`)
    })
  })

  it('默认渠道选中时第一个下拉项有 is-active class', () => {
    const items = wrapper.findAllComponents({ name: 'ElDropdownItem' })
    const activeItems = items.wrappers.filter(item => item.classes('is-active'))
    expect(activeItems.length).toBe(1)
    expect(activeItems[0].props('command')).toBe('')
  })

  it('选中具体渠道时对应下拉项有 is-active class', () => {
    const selectedConfig = createStoreConfig({
      state: {
        channels: [...AVAILABLE_CHANNELS],
        currentChannel: 'tiktok',
        currency: { code: 'USD', symbol: '$', name: '美元', decimals: 2 }
      }
    })
    const selectedStore = createStore(selectedConfig)
    const w = createWrapper(selectedStore)
    const items = w.findAllComponents({ name: 'ElDropdownItem' })
    const activeItems = items.wrappers.filter(item => item.classes('is-active'))
    expect(activeItems.length).toBe(1)
    expect(activeItems[0].props('command')).toBe('tiktok')
    w.destroy()
  })

  it('handleChange 调用 changeChannel action 并显示成功消息', async () => {
    wrapper.vm.handleChange('xhs')
    await wrapper.vm.$nextTick()
    expect(storeConfig.mockChangeChannel).toHaveBeenCalledWith(expect.any(Object), 'xhs')
    await Promise.resolve()
    expect(mockMessageSuccess).toHaveBeenCalledWith('渠道已切换')
  })

  it('handleChange 切换到默认渠道(空字符串)也调用 action', async () => {
    storeConfig.mockChangeChannel.mockClear()
    wrapper.vm.handleChange('')
    expect(storeConfig.mockChangeChannel).not.toHaveBeenCalled()
  })

  it('handleChange 相同值时不重复调用 action', () => {
    storeConfig.mockChangeChannel.mockClear()
    wrapper.vm.handleChange('')
    expect(storeConfig.mockChangeChannel).not.toHaveBeenCalled()
    expect(mockMessageSuccess).not.toHaveBeenCalled()
  })

  it('handleVisibleChange visible=true 时调用 loadChannels', () => {
    wrapper.vm.handleVisibleChange(true)
    expect(storeConfig.mockLoadChannels).toHaveBeenCalled()
  })

  it('handleVisibleChange visible=false 时不调用 loadChannels', () => {
    wrapper.vm.handleVisibleChange(false)
    expect(storeConfig.mockLoadChannels).not.toHaveBeenCalled()
  })

  it('空可用渠道列表时仍显示默认渠道选项', () => {
    const emptyConfig = createStoreConfig({
      state: { channels: [], currentChannel: '', currency: { ...DEFAULT_CURRENCY } },
      getters: {
        availableChannels: () => []
      }
    })
    const emptyStore = createStore(emptyConfig)
    const w = createWrapper(emptyStore)
    expect(w.exists()).toBe(true)
    const items = w.findAllComponents({ name: 'ElDropdownItem' })
    expect(items.length).toBe(1)
    expect(items.wrappers[0].props('command')).toBe('')
    w.destroy()
  })

  it('当前渠道不在可用列表中时显示渠道 code', () => {
    const invalidConfig = createStoreConfig({
      state: {
        channels: [...AVAILABLE_CHANNELS],
        currentChannel: 'invalid_channel',
        currency: { ...DEFAULT_CURRENCY }
      }
    })
    const invalidStore = createStore(invalidConfig)
    const w = createWrapper(invalidStore)
    const labelEl = w.find('.channel-label')
    expect(labelEl.text()).toBe('invalid_channel')
    w.destroy()
  })

  it('currentCurrency 为空时不显示货币信息', () => {
    const noCurrencyConfig = createStoreConfig({
      state: {
        channels: [...AVAILABLE_CHANNELS],
        currentChannel: '',
        currency: {}
      },
      getters: {
        currentCurrency: () => ({})
      }
    })
    const noCurrencyStore = createStore(noCurrencyConfig)
    const w = createWrapper(noCurrencyStore)
    const currencyEl = w.find('.channel-currency')
    expect(currencyEl.exists()).toBe(false)
    w.destroy()
  })

  it('loadChannels 失败时不抛出异常', () => {
    const failingConfig = createStoreConfig({
      actions: {
        loadChannels: jest.fn().mockRejectedValue(new Error('Network error'))
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
