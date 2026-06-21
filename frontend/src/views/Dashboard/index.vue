<template>
  <div class="dashboard">
    <el-row :gutter="20">
      <el-col :span="24">
        <h2>{{ $t('menu.dashboard') }}</h2>
        <p>{{ $t('common.welcome') }}</p>
      </el-col>
    </el-row>

    <el-row :gutter="20" style="margin-top: 20px;">
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stat-card">
            <div class="stat-value">1,280</div>
            <div class="stat-label">{{ $t('menu.annotation_task') }}</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stat-card">
            <div class="stat-value">356</div>
            <div class="stat-label">{{ $t('menu.review_task') }}</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stat-card">
            <div class="stat-value">48</div>
            <div class="stat-label">{{ $t('menu.dataset_list') }}</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stat-card">
            <div class="stat-value">12</div>
            <div class="stat-label">{{ $t('menu.user_list') }}</div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <el-row :gutter="20" style="margin-top: 20px;">
      <el-col :span="24">
        <el-card>
          <div slot="header">{{ $t('common.language') }}</div>
          <div class="locale-demo">
            <p>
              <strong>{{ $t('common.current_language') }}:</strong>
              {{ $store.getters.currentLocaleInfo.flag }}
              {{ $store.getters.currentLocaleInfo.native }}
              ({{ $store.getters.currentLocaleInfo.name }})
            </p>
          </div>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script>
export default {
  name: 'Dashboard'
}
</script>

<style lang="scss" scoped>
.dashboard {
  .stat-card {
    text-align: center;
    padding: 10px 0;

    .stat-value {
      font-size: 32px;
      font-weight: 600;
      color: #409eff;
    }

    .stat-label {
      margin-top: 8px;
      color: #909399;
      font-size: 14px;
    }
  }

  .locale-demo {
    padding: 10px 0;
  }
}
</style>
