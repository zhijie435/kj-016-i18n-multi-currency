<template>
  <el-dropdown
    trigger="click"
    @command="handleChange"
    class="channel-switcher"
    @visible-change="handleVisibleChange"
  >
    <span class="channel-switcher-trigger" :title="$t('common.switch_channel')">
      <i class="el-icon-s-grid"></i>
      <span class="channel-label">{{ currentChannelName }}</span>
      <span v-if="currentCurrency && currentCurrency.symbol" class="channel-currency">
        ({{ currentCurrency.symbol }} {{ currentCurrency.code }})
      </span>
      <i class="el-icon-arrow-down el-icon--right"></i>
    </span>
    <el-dropdown-menu slot="dropdown" class="channel-dropdown-menu">
      <el-dropdown-item
        command=""
        :class="{ 'is-active': !currentChannel }"
        class="channel-dropdown-item"
      >
        <span class="channel-name">{{ $t('common.default_channel') }}</span>
      </el-dropdown-item>
      <el-dropdown-item
        v-for="channel in channels"
        :key="channel.code"
        :command="channel.code"
        :class="{ 'is-active': channel.code === currentChannel }"
        class="channel-dropdown-item"
      >
        <span class="channel-name">{{ channel.name }}</span>
        <span v-if="channel.locale" class="channel-locale">
          ({{ channel.locale.native_name }})
        </span>
        <span v-if="channel.currency_code || channel.currency_symbol" class="channel-currency-info">
          [{{ channel.currency_symbol || '' }} {{ channel.currency_code || '' }}]
        </span>
      </el-dropdown-item>
    </el-dropdown-menu>
  </el-dropdown>
</template>

<script>
import { mapGetters, mapActions } from 'vuex'

export default {
  name: 'ChannelSwitcher',
  computed: {
    ...mapGetters(['currentChannel', 'availableChannels', 'currentChannelInfo', 'currentCurrency']),
    channels() {
      return this.availableChannels
    },
    currentChannelName() {
      if (!this.currentChannel) {
        return this.$t('common.default_channel')
      }
      return this.currentChannelInfo?.name || this.currentChannel
    }
  },
  methods: {
    ...mapActions(['changeChannel', 'loadChannels']),
    handleChange(channelCode) {
      if (channelCode === this.currentChannel) return
      this.changeChannel(channelCode).then(() => {
        this.$message.success(this.$t('common.channel_changed'))
      })
    },
    handleVisibleChange(visible) {
      if (visible) {
        this.loadChannels().catch(() => {})
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.channel-switcher {
  .channel-switcher-trigger {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 8px;
    border-radius: 4px;
    transition: background 0.2s;

    &:hover {
      background: #f5f7fa;
    }

    .channel-currency {
      color: #909399;
      font-size: 12px;
    }
  }

  .channel-label {
    font-size: 13px;
  }
}

.channel-dropdown-menu {
  .el-dropdown-menu__item {
    &.is-active {
      color: #409eff;
      font-weight: 600;
      background-color: #ecf5ff;
    }

    .channel-dropdown-item {
      display: flex;
      align-items: center;
      gap: 8px;

      .channel-name {
        font-weight: 500;
      }

      .channel-locale {
        color: #909399;
        font-size: 12px;
      }

      .channel-currency-info {
        color: #67c23a;
        font-size: 12px;
      }
    }
  }
}
</style>
