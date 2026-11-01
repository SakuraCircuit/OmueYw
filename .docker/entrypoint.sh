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

# 容器内自动装配
if [ ! -f .env ]; then
    cp .env.example .env
    echo "已从 .env.example 生成 .env"
fi

grep -q '^APP_KEY="base64:' .env || php artisan key:generate --force >/dev/null 2>&1 || true

php artisan config:clear >/dev/null 2>&1 || true
php artisan app:check-app-status || echo "数据表初始化失败，请检查 DB_* 环境变量"

exec "$@"
