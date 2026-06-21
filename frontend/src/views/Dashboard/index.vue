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
      <el-col :span="12">
        <el-card>
          <div slot="header">{{ $t('common.language') }} / {{ $t('common.currency') }}</div>
          <div class="locale-demo">
            <p>
              <strong>{{ $t('common.current_language') }}:</strong>
              {{ $store.getters.currentLocaleInfo.flag }}
              {{ $store.getters.currentLocaleInfo.native }}
              ({{ $store.getters.currentLocaleInfo.name }})
            </p>
            <p>
              <strong>{{ $t('common.currency') }}:</strong>
              <span class="currency-badge">
                {{ currentCurrency.symbol }} {{ currentCurrency.name }} ({{ currentCurrency.code }})
              </span>
            </p>
            <p v-if="currentChannel">
              <strong>{{ $t('common.channel') }}:</strong>
              {{ currentChannel.name }}
              <span v-if="currentChannel.locale" class="info-note">
                [{{ currentChannel.locale.native_name }}]
              </span>
            </p>
          </div>
        </el-card>
      </el-col>
      <el-col :span="12">
        <el-card>
          <div slot="header">🔄 {{ $t('common.currency') }} Converter</div>
          <div class="converter">
            <el-form :inline="false" label-position="top" size="small">
              <el-form-item label="Amount">
                <el-input-number v-model="convertAmount" :min="0" :precision="2" style="width: 100%;" />
              </el-form-item>
              <el-row :gutter="10">
                <el-col :span="11">
                  <el-form-item label="From">
                    <el-select v-model="fromCode" placeholder="Select" style="width: 100%;">
                      <el-option
                        v-for="c in availableCurrencies"
                        :key="c.code"
                        :label="`${c.symbol} ${c.name} (${c.code})`"
                        :value="c.code"
                      />
                    </el-select>
                  </el-form-item>
                </el-col>
                <el-col :span="2" style="display: flex; align-items: center; justify-content: center; padding-top: 28px;">
                  <i class="el-icon-right" style="color: #909399; font-size: 18px;"></i>
                </el-col>
                <el-col :span="11">
                  <el-form-item label="To">
                    <el-select v-model="toCode" placeholder="Select" style="width: 100%;">
                      <el-option
                        v-for="c in availableCurrencies"
                        :key="c.code"
                        :label="`${c.symbol} ${c.name} (${c.code})`"
                        :value="c.code"
                      />
                    </el-select>
                  </el-form-item>
                </el-col>
              </el-row>
              <el-button type="primary" size="small" @click="doConvert" :loading="converting" style="width: 100%;">
                Convert
              </el-button>
            </el-form>
            <div v-if="convertResult" class="convert-result">
              <div class="convert-from">{{ convertResult.formatted_from }}</div>
              <div class="convert-arrow">=</div>
              <div class="convert-to">{{ convertResult.formatted_to }}</div>
              <div class="convert-rate" v-if="convertResult.rate !== undefined">
                Rate: 1 {{ convertResult.from_currency }} = {{ Number(convertResult.rate).toFixed(4) }} {{ convertResult.to_currency }}
              </div>
            </div>
            <div v-else-if="convertError" class="convert-error">
              <i class="el-icon-warning"></i> {{ convertError }}
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <el-row :gutter="20" style="margin-top: 20px;">
      <el-col :span="24">
        <el-card>
          <div slot="header">
            <span>📊 Exchange Rates</span>
            <el-button size="mini" style="float: right;" @click="refreshRates" :loading="loadingRates">
              <i class="el-icon-refresh"></i> Refresh
            </el-button>
          </div>
          <div class="rates-section">
            <el-table
              v-if="rateTable.length > 0"
              :data="rateTable"
              size="small"
              border
              stripe
              style="width: 100%;"
            >
              <el-table-column prop="from" label="From" width="120" />
              <el-table-column prop="to" label="To" width="120" />
              <el-table-column prop="rate" label="Rate" width="140">
                <template slot-scope="scope">
                  <span class="rate-value">{{ Number(scope.row.rate).toFixed(6) }}</span>
                </template>
              </el-table-column>
              <el-table-column prop="effective_date" label="Effective Date" width="140" />
              <el-table-column prop="status" label="Status" width="100">
                <template slot-scope="scope">
                  <el-tag v-if="scope.row.is_active" type="success" size="mini">Active</el-tag>
                  <el-tag v-else type="info" size="mini">Inactive</el-tag>
                </template>
              </el-table-column>
            </el-table>
            <el-empty v-else description="暂无汇率数据" :image-size="80" />
          </div>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script>
import { mapGetters, mapActions } from 'vuex'

export default {
  name: 'Dashboard',
  data() {
    return {
      convertAmount: 100,
      fromCode: this.$store.getters.currentCurrency.code,
      toCode: 'USD',
      converting: false,
      convertResult: null,
      convertError: '',
      loadingRates: false,
      _ratesLoaded: false
    }
  },
  computed: {
    ...mapGetters(['currentCurrency', 'availableCurrencies', 'currentChannelInfo', 'activeExchangeRates']),
    currentChannel() {
      return this.currentChannelInfo
    },
    rateTable() {
      return this.activeExchangeRates.map(r => ({
        from: `${r.from_currency_code}`,
        to: `${r.to_currency_code}`,
        rate: r.rate,
        effective_date: r.effective_date || '-',
        is_active: r.is_active
      }))
    }
  },
  watch: {
    currentCurrency: {
      handler(newVal) {
        if (newVal && newVal.code && this.fromCode !== newVal.code) {
          this.fromCode = newVal.code
          this.convertResult = null
        }
      },
      immediate: true
    }
  },
  mounted() {
    this.initializeRates()
  },
  methods: {
    ...mapActions(['loadExchangeRates']),
    async initializeRates() {
      if (!this._ratesLoaded) {
        await this.refreshRates()
        this._ratesLoaded = true
      }
    },
    async refreshRates() {
      this.loadingRates = true
      try {
        await this.loadExchangeRates()
      } finally {
        this.loadingRates = false
      }
    },
    async doConvert() {
      if (!this.convertAmount || this.convertAmount <= 0) {
        this.convertError = '请输入有效金额'
        this.convertResult = null
        return
      }
      if (!this.fromCode || !this.toCode) {
        this.convertError = '请选择币种'
        this.convertResult = null
        return
      }
      this.converting = true
      this.convertError = ''
      this.convertResult = null
      try {
        const result = await this.$store.dispatch('convertAmount', {
          amount: this.convertAmount,
          fromCode: this.fromCode,
          toCode: this.toCode
        })
        if (result && result.success) {
          this.convertResult = result
        } else {
          this.convertError = result?.message || '转换失败'
        }
      } catch (e) {
        this.convertError = '转换请求异常'
      } finally {
        this.converting = false
      }
    }
  }
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

    p {
      margin: 8px 0;
      line-height: 1.8;
    }

    .currency-badge {
      display: inline-block;
      padding: 2px 10px;
      background: #fdf6ec;
      color: #e6a23c;
      border: 1px solid #f5dab1;
      border-radius: 4px;
      font-size: 13px;
      font-weight: 500;
    }

    .info-note {
      color: #909399;
      font-size: 12px;
    }
  }

  .converter {
    padding: 6px 0;

    .convert-result {
      margin-top: 16px;
      padding: 14px;
      background: #f0f9eb;
      border-radius: 6px;
      text-align: center;
      border: 1px solid #e1f3d8;

      .convert-from {
        font-size: 15px;
        color: #606266;
        font-weight: 500;
      }

      .convert-arrow {
        margin: 4px 0;
        color: #67c23a;
        font-weight: 600;
      }

      .convert-to {
        font-size: 20px;
        color: #67c23a;
        font-weight: 700;
      }

      .convert-rate {
        margin-top: 10px;
        font-size: 12px;
        color: #909399;
        padding-top: 8px;
        border-top: 1px dashed #c2e7b0;
      }
    }

    .convert-error {
      margin-top: 16px;
      padding: 10px 14px;
      background: #fef0f0;
      color: #f56c6c;
      border-radius: 6px;
      border: 1px solid #fbc4c4;
      font-size: 13px;

      i {
        margin-right: 6px;
      }
    }
  }

  .rates-section {
    padding: 6px 0;

    .rate-value {
      font-family: 'SF Mono', Monaco, Menlo, Consolas, monospace;
      color: #409eff;
      font-weight: 500;
    }
  }
}
</style>
