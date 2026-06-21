<template>
  <div class="not-found">
    <el-result
      icon="warning"
      :title="$t('common.no_data')"
      :sub-title="$t('common.no_permission')"
    >
      <template slot="extra">
        <el-button type="primary" @click="goBack">{{ $t('common.back') }}</el-button>
      </template>
    </el-result>
  </div>
</template>

<script>
export default {
  name: 'NotFound',
  methods: {
    goBack() {
      this.$router.back()
    }
  }
}
</script>

<style lang="scss" scoped>
.not-found {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
}
</style>
