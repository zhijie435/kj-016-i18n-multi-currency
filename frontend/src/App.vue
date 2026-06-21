<template>
  <el-container class="app-container" direction="vertical">
    <el-header v-if="isLogin" class="app-header">
      <div class="header-left">
        <h1 class="logo">{{ $t('common.app_name') }}</h1>
      </div>
      <div class="header-right">
        <ChannelSwitcher />
        <CurrencySwitcher />
        <LanguageSwitcher />
        <el-dropdown @command="handleCommand">
          <span class="user-info">
            <i class="el-icon-user"></i>
            {{ userInfo?.name || 'User' }}
            <i class="el-icon-arrow-down el-icon--right"></i>
          </span>
          <el-dropdown-menu slot="dropdown">
            <el-dropdown-item command="logout" divided>{{ $t('common.logout') }}</el-dropdown-item>
          </el-dropdown-menu>
        </el-dropdown>
      </div>
    </el-header>
    <el-container direction="horizontal">
      <el-aside v-if="isLogin" width="220px" class="app-aside">
        <el-menu
          :default-active="$route.path"
          router
          background-color="#304156"
          text-color="#bfcbd9"
          active-text-color="#ffd04b"
        >
          <el-menu-item index="/dashboard">
            <i class="el-icon-s-home"></i>
            <span slot="title">{{ $t('menu.dashboard') }}</span>
          </el-menu-item>

          <el-submenu index="/annotation">
            <template slot="title">
              <i class="el-icon-edit"></i>
              <span>{{ $t('menu.annotation') }}</span>
            </template>
            <el-menu-item index="/annotation/tasks">{{ $t('menu.annotation_task') }}</el-menu-item>
            <el-menu-item index="/annotation/work">{{ $t('menu.annotation_work') }}</el-menu-item>
          </el-submenu>

          <el-submenu index="/review">
            <template slot="title">
              <i class="el-icon-view"></i>
              <span>{{ $t('menu.review') }}</span>
            </template>
            <el-menu-item index="/review/tasks">{{ $t('menu.review_task') }}</el-menu-item>
            <el-menu-item index="/review/work">{{ $t('menu.review_work') }}</el-menu-item>
          </el-submenu>

          <el-submenu index="/dataset">
            <template slot="title">
              <i class="el-icon-folder-opened"></i>
              <span>{{ $t('menu.dataset') }}</span>
            </template>
            <el-menu-item index="/dataset/list">{{ $t('menu.dataset_list') }}</el-menu-item>
            <el-menu-item index="/dataset/import">{{ $t('menu.dataset_import') }}</el-menu-item>
          </el-submenu>

          <el-submenu index="/system">
            <template slot="title">
              <i class="el-icon-setting"></i>
              <span>{{ $t('menu.system') }}</span>
            </template>
            <el-menu-item index="/system/users">{{ $t('menu.user_list') }}</el-menu-item>
            <el-menu-item index="/system/roles">{{ $t('menu.role') }}</el-menu-item>
            <el-menu-item index="/system/config">{{ $t('menu.system_config') }}</el-menu-item>
            <el-menu-item index="/system/logs">{{ $t('menu.operation_log') }}</el-menu-item>
          </el-submenu>
        </el-menu>
      </el-aside>
      <el-main class="app-main">
        <router-view />
      </el-main>
    </el-container>
  </el-container>
</template>

<script>
import { mapGetters } from 'vuex'
import LanguageSwitcher from '@/components/LanguageSwitcher.vue'
import ChannelSwitcher from '@/components/ChannelSwitcher.vue'
import CurrencySwitcher from '@/components/CurrencySwitcher.vue'

export default {
  name: 'App',
  components: { LanguageSwitcher, ChannelSwitcher, CurrencySwitcher },
  computed: {
    ...mapGetters(['isLogin', 'userInfo'])
  },
  methods: {
    handleCommand(command) {
      if (command === 'logout') {
        this.$confirm(this.$t('common.confirm_logout'), this.$t('common.warning'), {
          confirmButtonText: this.$t('common.confirm'),
          cancelButtonText: this.$t('common.cancel'),
          type: 'warning'
        }).then(() => {
          this.$store.dispatch('logout')
          this.$router.push('/login')
          this.$message.success(this.$t('common.logout_success'))
        }).catch(() => {})
      }
    }
  }
}
</script>

<style lang="scss">
.app-header {
  .header-right {
    display: flex;
    align-items: center;
    gap: 12px;
  }
}
</style>
