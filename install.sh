#!/usr/bin/env bash
# install.sh —— 一键安装脚本
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$APP_DIR"

log() { printf '\033[32m[install]\033[0m %s\n' "$*"; }
warn() { printf '\033[33m[install]\033[0m %s\n' "$*"; }
die() { printf '\033[31m[install] %s\033[0m\n' "$*" >&2; exit 1; }

# ---------- 1. 依赖检查 ----------
command -v php >/dev/null 2>&1 || die "未找到 php，请安装 PHP 8.2 或 8.3"
command -v composer >/dev/null 2>&1 || die "未找到 composer"

PHP_VER="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
case "$PHP_VER" in
    8.2|8.3) ;;
    *) die "需要 PHP 8.2/8.3，当前为 $PHP_VER" ;;
esac

php -m | grep -qi '^pdo_mysql$' || die "缺少 pdo_mysql 扩展"
php -m | grep -qi '^mbstring$'  || die "缺少 mbstring 扩展"
php -m | grep -qi '^openssl$'   || die "缺少 openssl 扩展"
log "PHP $PHP_VER 环境检查通过"

# ---------- 2. 安装依赖 ----------
log "安装 composer 依赖"
composer install --optimize-autoloader --no-interaction --no-progress

# ---------- 3. 生成 .env ----------
if [ ! -f .env ]; then
    cp .env.example .env
    log "已从 .env.example 生成 .env"
fi

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-hklist}"
DB_USERNAME="${DB_USERNAME:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"
APP_URL="${APP_URL:-http://127.0.0.1:8000}"
APP_NAME="${APP_NAME:-HkList}"
HKLIST_ADMIN_PASSWORD="${HKLIST_ADMIN_PASSWORD:-admin}"
HKLIST_PARSE_PASSWORD="${HKLIST_PARSE_PASSWORD:-admin}"

set_env() {
    local k="$1" v="$2"
    if grep -q "^${k}=" .env; then
        # 用 | 作分隔符，避免值中的 / 破坏 sed
        sed -i.bak "s|^${k}=.*|${k}=\"${v}\"|" .env && rm -f .env.bak
    else
        printf '%s="%s"\n' "$k" "$v" >> .env
    fi
}

# ---------- 4. 建库 ----------
if command -v mysql >/dev/null 2>&1; then
    log "确保数据库 ${DB_DATABASE} 存在"
    MYSQL_PWD="$DB_PASSWORD" mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" \
        -e "CREATE DATABASE IF NOT EXISTS \`${DB_DATABASE}\` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" \
        || die "建库失败，请检查数据库账号与权限"
else
    warn "未找到 mysql 客户端，跳过自动建库，请预先创建数据库 ${DB_DATABASE}"
fi

set_env APP_NAME               "$APP_NAME"
set_env APP_ENV                "production"
set_env APP_DEBUG              "false"
set_env APP_URL                "$APP_URL"
set_env DB_CONNECTION          "mysql"
set_env DB_HOST                "$DB_HOST"
set_env DB_PORT                "$DB_PORT"
set_env DB_DATABASE            "$DB_DATABASE"
set_env DB_USERNAME            "$DB_USERNAME"
set_env DB_PASSWORD            "$DB_PASSWORD"
set_env HKLIST_ADMIN_PASSWORD  "$HKLIST_ADMIN_PASSWORD"
set_env HKLIST_PARSE_PASSWORD  "$HKLIST_PARSE_PASSWORD"
log "已写入 .env"

# ---------- 5. 生成密钥 ----------
if ! grep -q '^APP_KEY="base64:' .env; then
    php artisan key:generate --force --ansi
    log "已生成 APP_KEY"
else
    log "APP_KEY 已存在，跳过"
fi

# ---------- 6. 初始化数据表 ----------
php artisan config:clear >/dev/null 2>&1 || true
php artisan app:check-app-status || die "数据表初始化失败，请检查 DB_* 配置"

php artisan config:cache >/dev/null 2>&1 || true
php artisan route:cache  >/dev/null 2>&1 || true
log "数据表初始化完成"

# ---------- 7. 完成 ----------
cat <<EOF

安装完成。

  访问地址     ${APP_URL}
  后台密码     ${HKLIST_ADMIN_PASSWORD}
  解析密码     ${HKLIST_PARSE_PASSWORD}

启动服务：
  php artisan serve --host=0.0.0.0 --port=8000

建议安装后立即修改上述两个密码（.env 中的 HKLIST_ADMIN_PASSWORD / HKLIST_PARSE_PASSWORD）。

EOF
