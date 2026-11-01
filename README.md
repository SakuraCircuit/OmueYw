# OmueYw

基于 Laravel 11 的网盘分享解析服务。

## 特性

- 支持 7 种解析模式的调用顺序，集中在 `app/Http/Controllers/Parsers/` 便于阅读
- 卡密（token）配额管理、IP 绑定、到期控制
- 账号池：cookie / 开放平台 / 企业套餐 / 下载券四类
- 代理节点与直链转发
- 黑名单、解析记录、邮件通知

## 快速安装

### 方式一：Docker Compose

```bash
git clone https://github.com/SakuraCircuit/OmueYw.git
cd OmueYw
mkdir -p data
docker compose up -d
```

完成后访问 `http://127.0.0.1:8080`。默认后台密码与解析密码均为 `admin`。

可用环境变量覆盖默认值：

```bash
APP_PORT=9000 \
MYSQL_ROOT_PASSWORD=your_password \
HKLIST_ADMIN_PASSWORD=your_admin_password \
HKLIST_PARSE_PASSWORD=your_parse_password \
docker compose up -d
```

### 方式二：安装脚本

适用于已有 PHP 8.2/8.3 与 MySQL 的环境。

```bash
git clone https://github.com/SakuraCircuit/OmueYw.git
cd OmueYw

DB_HOST=127.0.0.1 \
DB_PORT=3306 \
DB_USERNAME=root \
DB_PASSWORD=your_db_password \
bash install.sh
```

脚本会依次完成：环境检查 → `composer install` → 生成 `.env` → 建库 → 生成 `APP_KEY` → 初始化数据表。

完成后启动：

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

### 方式三：手动安装

```bash
composer install --optimize-autoloader --no-interaction
cp .env.example .env
php artisan key:generate
# 编辑 .env，填写 DB_CONNECTION / DB_HOST / DB_PORT / DB_DATABASE / DB_USERNAME / DB_PASSWORD
php artisan app:check-app-status
php artisan serve
```

## 环境要求

| 组件 | 版本 |
| --- | --- |
| PHP | 8.2 或 8.3 |
| 扩展 | pdo_mysql、mbstring、openssl、tokenizer、fileinfo |
| MySQL | 5.7 及以上 |
| Composer | 2.x |

## 目录

```
app/Http/Controllers/Parsers/      解析层（7 种模式）
app/Http/Controllers/Api/          网盘接口封装
app/Console/Commands/              命令行工具（初始化、清理、巡检）
app/Http/Middleware/               请求中间件
config/hklist.php                  业务配置项
install.sh                         一键安装脚本
docker-compose.yml                 Docker 编排
```

## 配置项

主要环境变量（完整清单见 `.env.example`）：

| 变量 | 说明 | 默认值 |
| --- | --- | --- |
| `HKLIST_ADMIN_PASSWORD` | 后台管理密码 | `admin` |
| `HKLIST_PARSE_PASSWORD` | 解析接口密码 | `admin` |
| `HKLIST_TOKEN_PARSE_MODE` | 卡密解析模式 0-7 | `1` |
| `HKLIST_GUEST_PARSE_MODE` | 游客解析模式 0-7 | `1` |
| `HKLIST_MAX_ONCE` | 单次最多解析文件数 | `5` |
| `HKLIST_LIMIT_CN` | 是否限制仅中国大陆访问 | `false` |
| `HKLIST_PROXY_ENABLE` | 是否启用全局代理 | `false` |

## 命令行

```bash
php artisan app:check-app-status    # 初始化数据表（幂等）
php artisan app:update-file-lists   # 更新文件列表索引
```

## 许可

MIT
