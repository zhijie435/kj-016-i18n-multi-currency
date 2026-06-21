<template>
  <el-dropdown
    trigger="click"
    @command="handleChange"
    class="currency-switcher"
    @visible-change="handleVisibleChange"
  >
    <span class="currency-switcher-trigger" :title="$t('common.switch_currency')">
      <i class="el-icon-money"></i>
      <span class="currency-symbol">{{ currentCurrency.symbol }}</span>
      <span class="currency-code">{{ currentCurrency.code }}</span>
      <i class="el-icon-arrow-down el-icon--right"></i>
    </span>
    <el-dropdown-menu slot="dropdown" class="currency-dropdown-menu">
      <el-dropdown-item
        v-for="currency in currencyList"
        :key="currency.code"
        :command="currency.code"
        :class="{ 'is-active': currency.code === currentCurrency.code }"
        class="currency-dropdown-item"
      >
        <span class="currency-name">{{ currency.name }}</span>
        <span class="currency-symbol-info">({{ currency.symbol }} {{ currency.code }})</span>
      </el-dropdown-item>
    </el-dropdown-menu>
  </el-dropdown>
</template>

<script>
import { mapGetters, mapActions } from 'vuex'

export default {
  name: 'CurrencySwitcher',
  computed: {
    ...mapGetters(['currentCurrency', 'availableCurrencies']),
    currencyList() {
      return this.availableCurrencies
    }
  },
  methods: {
    ...mapActions(['changeCurrency', 'loadCurrencies']),
    handleChange(currencyCode) {
      if (currencyCode === this.currentCurrency.code) return
      const currency = this.availableCurrencies.find(c => c.code === currencyCode)
      if (currency) {
        this.changeCurrency(currency).then(() => {
          this.$message.success(this.$t('common.currency_changed'))
        })
      }
    },
    handleVisibleChange(visible) {
      if (visible) {
        this.loadCurrencies().catch(() => {})
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.currency-switcher {
  .currency-switcher-trigger {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 8px;
    border-radius: 4px;
    transition: background 0.2s;

    &:hover {
      background: #f5f7fa;
    }

    .currency-symbol {
      font-weight: 600;
      color: #e6a23c;
    }

    .currency-code {
      font-size: 12px;
      color: #606266;
    }
  }
}

.currency-dropdown-menu {
  .el-dropdown-menu__item {
    &.is-active {
      color: #409eff;
      font-weight: 600;
      background-color: #ecf5ff;
    }

    .currency-dropdown-item {
      display: flex;
      align-items: center;
      gap: 8px;

      .currency-name {
        font-weight: 500;
      }

      .currency-symbol-info {
        color: #e6a23c;
        font-size: 12px;
      }
    }
  }
}
</style>
