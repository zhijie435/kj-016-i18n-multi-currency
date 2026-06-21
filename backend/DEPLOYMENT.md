# 内容审核标注平台 - 多语言多币种模块部署文档

## 目录

1. [环境要求](#环境要求)
2. [环境变量配置](#环境变量配置)
3. [数据库迁移与种子](#数据库迁移与种子)
4. [队列配置](#队列配置)
5. [定时任务配置](#定时任务配置)
6. [一键部署命令](#一键部署命令)
7. [验收测试命令](#验收测试命令)
8. [运维命令速查](#运维命令速查)
9. [故障排查](#故障排查)

---

## 环境要求

### 软件版本

| 软件 | 版本要求 |
|------|----------|
| PHP | >= 7.4 / >= 8.0 |
| MySQL | >= 5.7 或 >= 8.0 |
| Redis | >= 5.0 |
| Composer | >= 2.0 |
| Laravel | 8.x |

### PHP 扩展

```bash
# 必需扩展
php-common
php-cli
php-fpm
php-mysql
php-redis
php-mbstring
php-xml
php-json
php-zip
php-curl
php-bcmath
```

---

## 环境变量配置

### 配置文件位置

- 模板文件：`backend/.env.example`
- 实际配置：`backend/.env`

### 多语言多币种核心配置

```dotenv
# ==================== Multi-Language & Multi-Currency ====================

# 语言配置
APP_DEFAULT_LOCALE=zh_CN          # 默认语言
APP_FALLBACK_LOCALE=en            # 回退语言
APP_TIMEZONE=Asia/Shanghai        # 时区

# 币种配置
APP_DEFAULT_CURRENCY=CNY          # 默认币种

# 汇率 API 配置
EXCHANGE_RATE_API_ENABLED=false   # 是否启用自动汇率更新
EXCHANGE_RATE_API_SOURCE=manual   # 数据源 (manual, exchangerate-api, fixer, etc.)
EXCHANGE_RATE_API_KEY=            # API Key
EXCHANGE_RATE_API_URL=            # API URL (支持占位符: {base}, {targets}, {key})
EXCHANGE_RATE_AUTO_UPDATE=false   # 是否自动更新汇率
EXCHANGE_RATE_UPDATE_INTERVAL=3600  # 更新间隔（秒）
EXCHANGE_RATE_CACHE_TTL=1800      # 汇率缓存 TTL（秒）

# 缓存配置
LOCALE_CACHE_TTL=3600             # 语言缓存 TTL
CURRENCY_CACHE_TTL=3600           # 币种缓存 TTL

# 功能开关
MULTI_CURRENCY_ENABLED=true       # 是否启用多币种
MULTI_LANGUAGE_ENABLED=true       # 是否启用多语言

# 币种转换精度
CURRENCY_CONVERSION_PRECISION=8   # 转换精度（小数位）
CURRENCY_ROUNDING_MODE=half_up    # 舍入模式 (half_up, half_down, half_even)

# 队列配置
QUEUE_CONNECTION=redis            # 队列连接驱动 (sync, database, redis)
QUEUE_REDIS_QUEUE=default         # 默认队列名称
QUEUE_FAILED_DRIVER=database-uuids

# 缓存配置
CACHE_STORE=redis                 # 缓存驱动
CACHE_PREFIX=biaozhu_             # 缓存前缀
```

### 汇率 API URL 占位符说明

API URL 支持以下占位符：

| 占位符 | 说明 | 示例 |
|--------|------|------|
| `{base}` | 基准货币 | `CNY` |
| `{targets}` | 目标货币列表（逗号分隔） | `USD,EUR,BRL,RUB` |
| `{key}` | API Key | `your-api-key` |

**示例配置：**

```dotenv
# ExchangeRate-API (https://www.exchangerate-api.com/)
EXCHANGE_RATE_API_URL=https://v6.exchangerate-api.com/v6/{key}/latest/{base}

# Fixer.io (https://fixer.io/)
EXCHANGE_RATE_API_URL=http://data.fixer.io/api/latest?access_key={key}&base={base}&symbols={targets}

# Open Exchange Rates (https://openexchangerates.org/)
EXCHANGE_RATE_API_URL=https://openexchangerates.org/api/latest.json?app_id={key}&base={base}&symbols={targets}
```

---

## 数据库迁移与种子

### 相关文件

| 文件 | 说明 |
|------|------|
| `database/migrations/2024_01_01_000001_create_locales_table.php` | 语言表迁移 |
| `database/migrations/2024_01_01_000002_create_channels_table.php` | 渠道表迁移 |
| `database/migrations/2024_01_01_000003_add_currency_to_channels_table.php` | 渠道表币种字段 |
| `database/migrations/2024_01_01_000004_create_currencies_table.php` | 币种表迁移 |
| `database/migrations/2024_01_01_000005_create_currency_exchange_rates_table.php` | 汇率表迁移 |

### 迁移命令

```bash
# 运行所有迁移
php artisan migrate --force

# 回滚最后一批迁移
php artisan migrate:rollback

# 重置所有迁移
php artisan migrate:reset

# 重置并重新运行所有迁移
php artisan migrate:fresh --seed
```

### 种子文件

| 文件 | 说明 |
|------|------|
| `database/seeders/LocaleSeeder.php` | 语言种子数据 |
| `database/seeders/CurrencySeeder.php` | 币种种子数据 |
| `database/seeders/CurrencyExchangeRateSeeder.php` | 汇率种子数据 |
| `database/seeders/ChannelSeeder.php` | 渠道种子数据 |
| `database/seeders/DatabaseSeeder.php` | 总调度器 |

### 种子命令

```bash
# 运行所有种子
php artisan db:seed --force

# 运行指定种子
php artisan db:seed --class=LocaleSeeder --force
php artisan db:seed --class=CurrencySeeder --force
php artisan db:seed --class=CurrencyExchangeRateSeeder --force
php artisan db:seed --class=ChannelSeeder --force

# 先迁移再填充种子
php artisan migrate:fresh --seed --force
```

### 预置数据

#### 语言（Locale）

| Code | Name | Native Name | Flag |
|------|------|-------------|------|
| zh_CN | 简体中文 | 简体中文 | 🇨🇳 |
| en | English | English | 🇺🇸 |
| pt_BR | Portuguese | Português | 🇧🇷 |
| ru | Russian | Русский | 🇷🇺 |

#### 币种（Currency）

| Code | Name | Symbol | Decimals |
|------|------|--------|----------|
| CNY | 人民币 | ¥ | 2 |
| USD | 美元 | $ | 2 |
| EUR | 欧元 | € | 2 |
| BRL | 巴西雷亚尔 | R$ | 2 |
| RUB | 俄罗斯卢布 | ₽ | 2 |

#### 渠道（Channel）

| Code | Name | Locale | Currency |
|------|------|--------|----------|
| cn_main | 中国主站 | zh_CN | CNY |
| us_main | US Main | en | USD |
| eu_main | EU Main | en | EUR |
| br_main | Brasil Principal | pt_BR | BRL |
| ru_main | Россия Основной | ru | RUB |

---

## 队列配置

### 队列配置文件

`config/queue.php`

### 队列类型

| 队列名称 | 用途 | 重试次数 | 超时时间 |
|----------|------|----------|----------|
| `default` | 默认队列 | 3 | 60s |
| `exchange_rates` | 汇率更新队列 | 5 | 120s |
| `locale_sync` | 语言同步队列 | 3 | 60s |
| `cache_warmup` | 缓存预热队列 | 2 | 300s |

### 队列任务（Jobs）

| 任务类 | 说明 | 队列 |
|--------|------|------|
| `UpdateExchangeRatesJob` | 更新汇率 | exchange_rates |
| `SyncLocalesJob` | 同步语言配置 | locale_sync |
| `SyncChannelsJob` | 同步渠道配置 | default |
| `WarmupCacheJob` | 预热缓存 | cache_warmup |

### 队列工作进程配置

#### 使用 Supervisor 管理队列进程

```ini
# /etc/supervisor/conf.d/biaozhu-queue.conf

[program:biaozhu-queue-default]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/backend/artisan queue:work redis --queue=default --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/backend/storage/logs/queue-default.log

[program:biaozhu-queue-exchange-rates]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/backend/artisan queue:work redis --queue=exchange_rates --sleep=5 --tries=5 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/backend/storage/logs/queue-exchange-rates.log

[program:biaozhu-queue-locale-sync]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/backend/artisan queue:work redis --queue=locale_sync --sleep=10 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/backend/storage/logs/queue-locale-sync.log

[program:biaozhu-queue-cache-warmup]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/backend/artisan queue:work redis --queue=cache_warmup --sleep=10 --tries=2 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/backend/storage/logs/queue-cache-warmup.log
```

#### Supervisor 命令

```bash
# 重新加载配置
supervisorctl reread
supervisorctl update

# 查看状态
supervisorctl status

# 启动/停止/重启
supervisorctl start biaozhu-queue-default:*
supervisorctl stop biaozhu-queue-default:*
supervisorctl restart biaozhu-queue-default:*

# 查看日志
tail -f /path/to/backend/storage/logs/queue-default.log
```

### 手动运行队列

```bash
# 运行默认队列
php artisan queue:work redis --queue=default --tries=3

# 运行所有队列
php artisan queue:work redis --queue=default,exchange_rates,locale_sync,cache_warmup --tries=3

# 只运行汇率更新队列
php artisan queue:work redis --queue=exchange_rates --tries=5

# 一次性处理所有任务（调试用）
php artisan queue:listen --queue=exchange_rates
```

### 队列运维命令

```bash
# 查看队列状态
php artisan queue:monitor redis:default
php artisan queue:monitor redis:exchange_rates

# 重试失败的任务
php artisan queue:retry all
php artisan queue:retry <uuid>

# 清除失败的任务
php artisan queue:flush

# 查看失败的任务
php artisan queue:failed

# 删除失败的任务
php artisan queue:forget <uuid>

# 清除批次记录
php artisan queue:prune-batches

# 清除失败记录
php artisan queue:prune-failed
```

---

## 定时任务配置

### 配置文件

`app/Console/Kernel.php`

### 调度任务列表

| 任务 | 频率 | 时间 | 队列 | 说明 |
|------|------|------|------|------|
| 汇率自动更新 | 动态 | 按 `EXCHANGE_RATE_UPDATE_INTERVAL` | exchange_rates | 需要 `EXCHANGE_RATE_AUTO_UPDATE=true` |
| 缓存预热（语言/币种） | 每日 | 02:00 | cache_warmup | 每日凌晨预热语言和币种缓存 |
| 缓存预热（汇率） | 每6小时 | - | cache_warmup | 每6小时预热汇率缓存 |
| 语言配置同步 | 每周 | 周一 01:00 | locale_sync | 每周同步一次语言配置 |
| 渠道配置同步 | 每周 | 周一 01:30 | default | 每周同步一次渠道配置 |
| 缓存清理 | 每周 | 周日 00:00 | - | 每周清理一次所有缓存 |
| 清理批次记录 | 每日 | - | - | 每日清理过期批次 |
| 清理失败记录 | 每周 | - | - | 每周清理失败任务 |

### 配置 Cron

```bash
# 编辑 crontab
crontab -e

# 添加以下内容（请替换为实际路径）
* * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
```

### 调度相关命令

```bash
# 查看所有调度任务
php artisan schedule:list

# 手动运行调度器（测试用）
php artisan schedule:run

# 手动运行指定任务
php artisan schedule:test

# 查看下一次运行时间
php artisan schedule:work
```

---

## 一键部署命令

### 完整部署

```bash
# 方式一：使用一键部署命令（推荐）
php artisan setup:multi-currency --all

# 方式二：分步执行
php artisan setup:multi-currency --migrate
php artisan setup:multi-currency --seed
php artisan setup:multi-currency --sync
php artisan setup:multi-currency --warmup
php artisan setup:multi-currency --acceptance
```

### 部署步骤说明

| 步骤 | 命令 | 说明 |
|------|------|------|
| 1. 安装依赖 | `composer install` | 安装 PHP 依赖 |
| 2. 生成密钥 | `php artisan key:generate` | 生成应用密钥 |
| 3. 数据库迁移 | `php artisan migrate --force` | 执行数据库迁移 |
| 4. 种子数据 | `php artisan db:seed --force` | 填充初始数据 |
| 5. 同步配置 | `php artisan locales:sync --force` | 同步语言配置 |
| 6. 同步渠道 | `php artisan channels:sync --force` | 同步渠道配置 |
| 7. 更新汇率 | `php artisan exchange-rates:update` | 更新汇率数据 |
| 8. 缓存预热 | `php artisan cache:warmup` | 预热所有缓存 |
| 9. 验收测试 | `php artisan acceptance:test` | 运行验收测试 |

### 部署脚本示例

```bash
#!/bin/bash
# deploy.sh

set -e

PROJECT_DIR="/path/to/backend"
cd $PROJECT_DIR

echo "========================================"
echo "  开始部署多语言多币种模块"
echo "========================================"

echo ""
echo "[1/9] 拉取最新代码..."
git pull origin main

echo ""
echo "[2/9] 安装依赖..."
composer install --no-dev --optimize-autoloader

echo ""
echo "[3/9] 生成应用密钥..."
php artisan key:generate --force

echo ""
echo "[4/9] 清除旧缓存..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear

echo ""
echo "[5/9] 重建配置缓存..."
php artisan config:cache
php artisan route:cache

echo ""
echo "[6/9] 执行数据库迁移..."
php artisan migrate --force

echo ""
echo "[7/9] 填充种子数据..."
php artisan db:seed --force

echo ""
echo "[8/9] 一键配置多语言多币种..."
php artisan setup:multi-currency --sync --warmup

echo ""
echo "[9/9] 运行验收测试..."
php artisan acceptance:test

echo ""
echo "========================================"
echo "  部署完成！"
echo "========================================"

echo ""
echo "请启动队列工作进程："
echo "  supervisorctl restart biaozhu-queue-*"
echo ""
echo "或手动运行队列："
echo "  php artisan queue:work redis --queue=default,exchange_rates,locale_sync,cache_warmup --tries=3"
echo ""
```

---

## 验收测试命令

### 验收测试套件

| 套件 | 测试项 | 说明 |
|------|--------|------|
| `quick` | 5项 | 快速检查核心配置 |
| `default` | 9项 | 默认验收（推荐部署后使用） |
| `full` | 12项 | 完整功能测试 |

### 测试命令

```bash
# 默认验收（推荐）
php artisan acceptance:test

# 快速验收
php artisan acceptance:test --suite=quick

# 完整验收
php artisan acceptance:test --suite=full

# JSON 格式输出
php artisan acceptance:test --output=json
```

### 测试项说明

| 测试名称 | quick | default | full | 说明 |
|----------|-------|---------|------|------|
| locale_config | ✓ | ✓ | ✓ | 语言配置检查 |
| locale_database | ✓ | ✓ | ✓ | 语言数据检查 |
| locale_switch | - | ✓ | ✓ | 语言切换功能 |
| currency_config | ✓ | ✓ | ✓ | 币种配置检查 |
| currency_database | ✓ | ✓ | ✓ | 币种数据检查 |
| exchange_rate_conversion | - | ✓ | ✓ | 汇率转换功能 |
| exchange_rate_matrix | - | - | ✓ | 汇率矩阵生成 |
| channel_config | - | ✓ | ✓ | 渠道配置检查 |
| channel_locale_currency | - | - | ✓ | 渠道关联检查 |
| database_integrity | ✓ | ✓ | ✓ | 数据库完整性 |
| language_files | - | ✓ | ✓ | 语言文件检查 |
| middleware_config | - | - | ✓ | 中间件配置检查 |

### 示例输出

```
$ php artisan acceptance:test

Running acceptance test suite: default
============================================================

Testing: locale_config
  ✓ Passed: Default locale: zh_CN, available: 4

Testing: locale_database
  ✓ Passed: Enabled locales: 4, default: zh_CN

Testing: locale_switch
  ✓ Passed: All zh_CN,en,pt_BR,ru locales accessible

Testing: currency_config
  ✓ Passed: Default currency: CNY, available: 5

Testing: currency_database
  ✓ Passed: Enabled currencies: 5, default: CNY

Testing: exchange_rate_conversion
  ✓ Passed: 100 CNY = $13.89 (rate: 0.1389)

Testing: channel_config
  ✓ Passed: Enabled channels: 5

Testing: database_integrity
  ✓ Passed: locales: 4, currencies: 5, channels: 5, rates: 20

Testing: language_files
  ✓ Passed: All language files present

============================================================

Test Results: 9/9 passed

+---------------------------+----------+-----------------------------------+
| Test                      | Status   | Message                           |
+---------------------------+----------+-----------------------------------+
| locale_config             | ✓ PASS   | Default locale: zh_CN,...         |
| locale_database           | ✓ PASS   | Enabled locales: 4, default: ...  |
| locale_switch             | ✓ PASS   | All zh_CN,en,pt_BR,ru...          |
| currency_config           | ✓ PASS   | Default currency: CNY, ...        |
| currency_database         | ✓ PASS   | Enabled currencies: 5, ...        |
| exchange_rate_conversion  | ✓ PASS   | 100 CNY = $13.89 (rate: 0.1389)   |
| channel_config            | ✓ PASS   | Enabled channels: 5               |
| database_integrity        | ✓ PASS   | locales: 4, currencies: 5, ...    |
| language_files            | ✓ PASS   | All language files present        |
+---------------------------+----------+-----------------------------------+
```

---

## 运维命令速查

### 数据管理命令

```bash
# ===== 语言管理 =====

# 同步语言配置到数据库
php artisan locales:sync
php artisan locales:sync --force           # 强制更新
php artisan locales:sync --code=zh_CN      # 只同步指定语言
php artisan locales:sync --code=zh_CN --code=en
php artisan locales:sync --queue           # 异步执行

# ===== 币种管理 =====

# 更新汇率
php artisan exchange-rates:update
php artisan exchange-rates:update --base=USD
php artisan exchange-rates:update --target=EUR --target=BRL
php artisan exchange-rates:update --source=api
php artisan exchange-rates:update --queue    # 异步执行
php artisan exchange-rates:update --force    # 强制执行

# ===== 渠道管理 =====

# 同步渠道配置
php artisan channels:sync
php artisan channels:sync --force
php artisan channels:sync --code=cn_main
php artisan channels:sync --queue

# ===== 缓存管理 =====

# 缓存预热
php artisan cache:warmup
php artisan cache:warmup --type=locales
php artisan cache:warmup --type=currencies
php artisan cache:warmup --type=exchange_rates
php artisan cache:warmup --locale=zh_CN --currency=CNY
php artisan cache:warmup --queue

# 清除缓存
php artisan cache:clear              # 清除所有缓存
php artisan config:clear             # 清除配置缓存
php artisan route:clear              # 清除路由缓存
php artisan view:clear               # 清除视图缓存

# 重建缓存
php artisan config:cache             # 重建配置缓存
php artisan route:cache              # 重建路由缓存
php artisan optimize                 # 优化（相当于 config:cache + route:cache）
```

### 队列任务调度命令

```bash
# 手动调度任务

# 手动更新汇率（分发到队列）
php artisan tinker
>>> App\Jobs\UpdateExchangeRatesJob::dispatch();

# 手动同步语言
>>> App\Jobs\SyncLocalesJob::dispatch();

# 手动同步渠道
>>> App\Jobs\SyncChannelsJob::dispatch();

# 手动预热缓存
>>> App\Jobs\WarmupCacheJob::dispatch();

# 同步执行（不使用队列）
>>> App\Jobs\UpdateExchangeRatesJob::dispatchSync();
```

### 数据库操作命令

```bash
# ===== 迁移 =====

# 执行迁移
php artisan migrate --force
php artisan migrate --pretend          # 模拟执行，查看 SQL

# 回滚
php artisan migrate:rollback           # 回滚最后一批
php artisan migrate:rollback --step=5  # 回滚最后5个

# 重置
php artisan migrate:reset              # 回滚所有
php artisan migrate:fresh              # 重置并重新运行
php artisan migrate:fresh --seed       # 重置并填充种子

# ===== 种子 =====

# 运行种子
php artisan db:seed --force
php artisan db:seed --class=CurrencySeeder --force

# 获取数据
php artisan tinker
>>> App\Models\Locale::all();
>>> App\Models\Currency::all();
>>> App\Models\CurrencyExchangeRate::active()->get();
>>> App\Models\Channel::with('locale')->get();
```

---

## 故障排查

### 常见问题

#### 1. 汇率更新失败

**现象：** `exchange-rates:update` 命令执行失败

**排查步骤：**

```bash
# 查看日志
tail -f storage/logs/laravel.log
tail -f storage/logs/queue-exchange-rates.log

# 检查 API 配置
grep EXCHANGE_RATE .env

# 测试 API 连接
curl -v "https://api.exchangerate-api.com/v6/YOUR_KEY/latest/CNY"

# 手动运行测试
php artisan tinker
>>> $service = app(App\Services\ExchangeRateService::class);
>>> $service->getLatest('CNY', 'USD');
```

**常见原因：**
- API Key 未配置或无效
- 网络连接问题（防火墙、DNS）
- 目标货币不支持
- API 调用频率超限

**解决方案：**
- 检查 `.env` 中的 `EXCHANGE_RATE_API_*` 配置
- 确保服务器可以访问外部 API
- 降级使用内置 mock 数据（`EXCHANGE_RATE_API_ENABLED=false`）

---

#### 2. 语言切换不生效

**现象：** 设置 `X-App-Locale` 头或 `locale` 参数后，语言未切换

**排查步骤：**

```bash
# 检查中间件配置
php artisan route:list

# 检查语言文件是否存在
ls -la resources/lang/

# 检查数据库中的语言数据
php artisan tinker
>>> App\Models\Locale::enabled()->pluck('code');

# 手动测试
curl -H "X-App-Locale: en" http://localhost/api/test
curl "http://localhost/api/test?locale=pt_BR"
```

**常见原因：**
- `SetLocale` 中间件未注册或顺序不对
- 语言代码不正确（区分大小写）
- 语言文件缺失
- 缓存未清理

**解决方案：**
- 确保 `SetLocale` 中间件在 `api` 中间件组中
- 运行 `php artisan locales:sync --force`
- 运行 `php artisan cache:clear`

---

#### 3. 队列任务不执行

**现象：** 任务分发后一直处于 pending 状态

**排查步骤：**

```bash
# 检查队列工作进程状态
supervisorctl status
ps aux | grep queue:work

# 检查队列连接配置
grep QUEUE .env

# 检查 Redis 连接
php artisan tinker
>>> Redis::ping();

# 查看失败的任务
php artisan queue:failed

# 查看日志
tail -f storage/logs/queue-default.log
tail -f storage/logs/laravel.log
```

**常见原因：**
- 队列工作进程未启动或已崩溃
- Redis 连接失败
- 队列名称配置错误
- `QUEUE_CONNECTION` 设置为 `sync`（同步执行）

**解决方案：**
- 启动队列工作进程：`php artisan queue:work`
- 检查 Supervisor 配置
- 验证 Redis 连接
- 重启队列：`supervisorctl restart biaozhu-queue-*`

---

#### 4. 定时任务不执行

**现象：** 调度任务未按计划运行

**排查步骤：**

```bash
# 检查 cron 配置
crontab -l

# 查看调度列表
php artisan schedule:list

# 手动运行调度器（查看输出）
php artisan schedule:run -v

# 检查日志
grep -i "schedule" storage/logs/laravel.log
```

**常见原因：**
- cron 未配置或配置错误
- artisan 路径不正确
- 文件权限问题
- PHP 路径不正确

**解决方案：**
- 确认 cron 配置：`* * * * * cd /path && php artisan schedule:run`
- 检查 PHP 路径：`which php`
- 检查文件权限
- 手动测试：`cd /path && php artisan schedule:run`

---

#### 5. 币种转换错误

**现象：** 调用 `convert` 方法抛出异常或返回 null

**排查步骤：**

```bash
# 检查币种数据
php artisan tinker
>>> App\Models\Currency::enabled()->pluck('code');

# 检查汇率数据
>>> App\Models\CurrencyExchangeRate::active()->count();
>>> App\Models\CurrencyExchangeRate::getLatestRate('CNY', 'USD');

# 手动测试转换
>>> $service = app(App\Services\ExchangeRateService::class);
>>> $service->convert(100, 'CNY', 'USD');

# 查看详细转换信息
>>> $service->convertWithDetail(100, 'CNY', 'USD');
```

**常见原因：**
- 汇率数据不存在
- 币种未启用
- 汇率已过期

**解决方案：**
- 运行 `php artisan exchange-rates:update` 更新汇率
- 运行 `php artisan db:seed --class=CurrencyExchangeRateSeeder --force`
- 检查币种是否启用：`App\Models\Currency::enabled()->get()`

---

### 日志路径

| 日志文件 | 说明 |
|----------|------|
| `storage/logs/laravel.log` | 应用主日志 |
| `storage/logs/queue-default.log` | 默认队列日志 |
| `storage/logs/queue-exchange-rates.log` | 汇率队列日志 |
| `storage/logs/queue-locale-sync.log` | 语言同步队列日志 |
| `storage/logs/queue-cache-warmup.log` | 缓存预热队列日志 |

### 常用调试命令

```bash
# 查看配置
php artisan config:show
php artisan config:get app.available_locales
php artisan config:get app.available_currencies

# 查看路由
php artisan route:list

# 查看中间件
php artisan route:list -v

# 查看任务调度
php artisan schedule:list

# 数据库连接测试
php artisan tinker
>>> DB::connection()->getPdo();

# Redis 连接测试
>>> Redis::ping();

# 清除所有缓存
php artisan optimize:clear
```

---

## 附录

### 配置文件一览

| 文件 | 说明 |
|------|------|
| [config/app.php](file:///Users/wuzhijie/Documents/xiaohongshu/biaozhu/tishiwen/003-内容审核标注平台/backend/config/app.php) | 应用配置（语言、币种） |
| [config/queue.php](file:///Users/wuzhijie/Documents/xiaohongshu/biaozhu/tishiwen/003-内容审核标注平台/backend/config/queue.php) | 队列配置 |
| [.env.example](file:///Users/wuzhijie/Documents/xiaohongshu/biaozhu/tishiwen/003-内容审核标注平台/backend/.env.example) | 环境变量模板 |

### 核心类文件

| 类型 | 文件 |
|------|------|
| **Models** | [Locale.php](file:///Users/wuzhijie/Documents/xiaohongshu/biaozhu/tishiwen/003-内容审核标注平台/backend/app/Models/Locale.php) |
| | [Currency.php](file:///Users/wuzhijie/Documents/xiaohongshu/biaozhu/tishiwen/003-内容审核标注平台/backend/app/Models/Currency.php) |
| | [CurrencyExchangeRate.php](file:///Users/wuzhijie/Documents/xiaohongshu/biaozhu/tishiwen/003-内容审核标注平台/backend/app/Models/CurrencyExchangeRate.php) |
| **Jobs** | [UpdateExchangeRatesJob.php](file:///Users/wuzhijie/Documents/xiaohongshu/biaozhu/tishiwen/003-内容审核标注平台/backend/app/Jobs/UpdateExchangeRatesJob.php) |
| | [SyncLocalesJob.php](file:///Users/wuzhijie/Documents/xiaohongshu/biaozhu/tishiwen/003-内容审核标注平台/backend/app/Jobs/SyncLocalesJob.php) |
| | [SyncChannelsJob.php](file:///Users/wuzhijie/Documents/xiaohongshu/biaozhu/tishiwen/003-内容审核标注平台/backend/app/Jobs/SyncChannelsJob.php) |
| | [WarmupCacheJob.php](file:///Users/wuzhijie/Documents/xiaohongshu/biaozhu/tishiwen/003-内容审核标注平台/backend/app/Jobs/WarmupCacheJob.php) |
| **Commands** | [UpdateExchangeRatesCommand.php](file:///Users/wuzhijie/Documents/xiaohongshu/biaozhu/tishiwen/003-内容审核标注平台/backend/app/Console/Commands/UpdateExchangeRatesCommand.php) |
| | [SyncLocalesCommand.php](file:///Users/wuzhijie/Documents/xiaohongshu/biaozhu/tishiwen/003-内容审核标注平台/backend/app/Console/Commands/SyncLocalesCommand.php) |
| | [SyncChannelsCommand.php](file:///Users/wuzhijie/Documents/xiaohongshu/biaozhu/tishiwen/003-内容审核标注平台/backend/app/Console/Commands/SyncChannelsCommand.php) |
| | [WarmupCacheCommand.php](file:///Users/wuzhijie/Documents/xiaohongshu/biaozhu/tishiwen/003-内容审核标注平台/backend/app/Console/Commands/WarmupCacheCommand.php) |
| | [AcceptanceTestCommand.php](file:///Users/wuzhijie/Documents/xiaohongshu/biaozhu/tishiwen/003-内容审核标注平台/backend/app/Console/Commands/AcceptanceTestCommand.php) |
| | [SetupMultiCurrencyCommand.php](file:///Users/wuzhijie/Documents/xiaohongshu/biaozhu/tishiwen/003-内容审核标注平台/backend/app/Console/Commands/SetupMultiCurrencyCommand.php) |
| **Seeders** | [DatabaseSeeder.php](file:///Users/wuzhijie/Documents/xiaohongshu/biaozhu/tishiwen/003-内容审核标注平台/backend/database/seeders/DatabaseSeeder.php) |
| | [LocaleSeeder.php](file:///Users/wuzhijie/Documents/xiaohongshu/biaozhu/tishiwen/003-内容审核标注平台/backend/database/seeders/LocaleSeeder.php) |
| | [CurrencySeeder.php](file:///Users/wuzhijie/Documents/xiaohongshu/biaozhu/tishiwen/003-内容审核标注平台/backend/database/seeders/CurrencySeeder.php) |
| | [CurrencyExchangeRateSeeder.php](file:///Users/wuzhijie/Documents/xiaohongshu/biaozhu/tishiwen/003-内容审核标注平台/backend/database/seeders/CurrencyExchangeRateSeeder.php) |
| **Console** | [Kernel.php](file:///Users/wuzhijie/Documents/xiaohongshu/biaozhu/tishiwen/003-内容审核标注平台/backend/app/Console/Kernel.php) |

---

**文档版本：** 1.0.0  
**最后更新：** 2026-06-21
