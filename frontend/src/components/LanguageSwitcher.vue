<template>
  <el-dropdown
    trigger="click"
    @command="handleChange"
    class="locale-switcher"
    @visible-change="handleVisibleChange"
  >
    <span class="locale-switcher-trigger" :title="$t('common.switch_language')">
      <span class="locale-flag">{{ currentLocaleInfo.flag }}</span>
      <span class="locale-label">{{ currentLocaleInfo.native }}</span>
      <i class="el-icon-arrow-down el-icon--right"></i>
    </span>
    <el-dropdown-menu slot="dropdown" class="locale-dropdown-menu">
      <el-dropdown-item
        v-for="item in localeList"
        :key="item.code"
        :command="item.code"
        :class="{ 'is-active': item.code === currentLocale }"
        class="locale-dropdown-item"
      >
        <span class="locale-flag">{{ item.flag }}</span>
        <span class="locale-native">{{ item.native }}</span>
        <span class="locale-name">({{ item.name }})</span>
      </el-dropdown-item>
    </el-dropdown-menu>
  </el-dropdown>
</template>

<script>
import { mapGetters } from 'vuex'

export default {
  name: 'LanguageSwitcher',
  computed: {
    ...mapGetters(['currentLocale', 'currentLocaleInfo', 'availableLocaleList']),
    localeList() {
      return this.availableLocaleList
    }
  },
  methods: {
    handleChange(locale) {
      if (locale === this.currentLocale) return
      this.$store.dispatch('changeLocale', locale).then(() => {
        this.$message.success(this.$t('common.language_changed'))
      })
    },
    handleVisibleChange(visible) {
      if (visible) {
        this.$store.dispatch('loadServerLocales').catch(() => {})
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.locale-switcher {
  .locale-switcher-trigger {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 8px;
    border-radius: 4px;
    transition: background 0.2s;

    &:hover {
      background: #f5f7fa;
    }
  }

  .locale-flag {
    font-size: 16px;
  }

  .locale-label {
    font-size: 13px;
  }
}

.locale-dropdown-menu {
  .el-dropdown-menu__item {
    &.is-active {
      color: #409eff;
      font-weight: 600;
      background-color: #ecf5ff;
    }

    .locale-dropdown-item {
      display: flex;
      align-items: center;
      gap: 8px;

      .locale-flag {
        font-size: 16px;
      }

      .locale-native {
        font-weight: 500;
      }

      .locale-name {
        color: #909399;
        font-size: 12px;
      }
    }
  }
}
</style>
