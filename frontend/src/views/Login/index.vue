<template>
  <div class="login-page">
    <div class="login-container">
      <h2 class="login-title">{{ $t('common.app_name') }}</h2>
      <el-form
        ref="loginForm"
        :model="loginForm"
        :rules="rules"
        class="login-form"
        @keyup.enter.native="handleLogin"
      >
        <el-form-item prop="username">
          <el-input
            v-model="loginForm.username"
            :placeholder="$t('common.username')"
            prefix-icon="el-icon-user"
          />
        </el-form-item>
        <el-form-item prop="password">
          <el-input
            v-model="loginForm.password"
            type="password"
            :placeholder="$t('common.password')"
            prefix-icon="el-icon-lock"
            show-password
          />
        </el-form-item>
        <el-form-item>
          <el-checkbox v-model="loginForm.remember">
            {{ $t('common.remember_me') }}
          </el-checkbox>
          <el-link type="primary" style="float: right;">
            {{ $t('common.forgot_password') }}
          </el-link>
        </el-form-item>
        <el-form-item>
          <el-button
            type="primary"
            style="width: 100%;"
            :loading="loading"
            @click="handleLogin"
          >
            {{ $t('common.login') }}
          </el-button>
        </el-form-item>
      </el-form>

      <div class="login-footer">
        <LanguageSwitcher />
      </div>
    </div>
  </div>
</template>

<script>
import LanguageSwitcher from '@/components/LanguageSwitcher.vue'

export default {
  name: 'Login',
  components: { LanguageSwitcher },
  data() {
    return {
      loading: false,
      loginForm: {
        username: '',
        password: '',
        remember: false
      },
      rules: {
        username: [
          { required: true, message: this.$t('common.required_field'), trigger: 'blur' }
        ],
        password: [
          { required: true, message: this.$t('common.required_field'), trigger: 'blur' }
        ]
      }
    }
  },
  methods: {
    handleLogin() {
      this.$refs.loginForm.validate(valid => {
        if (!valid) return
        this.loading = true
        setTimeout(() => {
          this.loading = false
          this.$store.commit('SET_TOKEN', 'demo-token')
          this.$store.commit('SET_USER_INFO', { name: 'Admin' })
          this.$message.success(this.$t('common.login_success'))
          this.$router.push(this.$route.query.redirect || '/dashboard')
        }, 800)
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.login-page {
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

  .login-container {
    width: 400px;
    padding: 40px 36px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);

    .login-title {
      text-align: center;
      margin: 0 0 32px 0;
      color: #303133;
      font-size: 22px;
    }

    .login-footer {
      margin-top: 20px;
      display: flex;
      justify-content: center;
    }
  }
}
</style>
