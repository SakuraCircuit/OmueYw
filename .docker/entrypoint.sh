#!/bin/sh
set -e

# 变量
www_path="/var/www/html"
latest_path="/var/www/HkList"

echo "保留.env文件"
if [ -f "$www_path/.env" ]; then
    cp "$www_path/.env" "$latest_path/.env"
fi

echo "导入文件"
rm -rf "$www_path"
mkdir -p "$www_path"
cp -a "$latest_path/." "$www_path"

cd "$www_path"

# 首次启动时从模板生成 .env
if [ ! -f .env ]; then
    cp .env.example .env
    echo "已从 .env.example 生成 .env"
fi

# 把容器环境变量写入 .env
set_env() {
    k="$1"; v="$2"
    [ -z "$v" ] && return 0
    if grep -q "^${k}=" .env; then
        sed -i.bak "s|^${k}=.*|${k}=\"${v}\"|" .env && rm -f .env.bak
    else
        printf '%s="%s"\n' "$k" "$v" >> .env
    fi
}

set_env APP_NAME               "${APP_NAME:-}"
set_env APP_URL                "${APP_URL:-}"
set_env APP_ENV                "production"
set_env APP_DEBUG              "false"
set_env DB_CONNECTION          "${DB_CONNECTION:-}"
set_env DB_HOST                "${DB_HOST:-}"
set_env DB_PORT                "${DB_PORT:-}"
set_env DB_DATABASE            "${DB_DATABASE:-}"
set_env DB_USERNAME            "${DB_USERNAME:-}"
set_env DB_PASSWORD            "${DB_PASSWORD:-}"
set_env HKLIST_ADMIN_PASSWORD  "${HKLIST_ADMIN_PASSWORD:-}"
set_env HKLIST_PARSE_PASSWORD  "${HKLIST_PARSE_PASSWORD:-}"

# 生成 APP_KEY
if ! grep -q '^APP_KEY="base64:' .env; then
    key="$(php artisan key:generate --force --show 2>/dev/null | tail -n1)"
    [ -n "$key" ] && set_env APP_KEY "$key"
fi

php artisan config:clear >/dev/null 2>&1 || true

# 等待数据库并初始化（最多 60 秒）
i=0
while [ $i -lt 30 ]; do
    if php artisan app:check-app-status; then
        break
    fi
    i=$((i + 1))
    echo "数据库尚未就绪，重试中（$i/30）"
    sleep 2
done

php artisan config:cache >/dev/null 2>&1 || true

exec "$@"
