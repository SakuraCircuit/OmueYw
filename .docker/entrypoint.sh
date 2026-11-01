#!/bin/sh

# 变量
www_path="/var/www/html"
latest_path="/var/www/HkList-Libre"

echo "保留.env文件"
if [ -f "$www_path/.env" ]; then
    cp "$www_path/.env" "$latest_path/.env"
fi

echo "导入文件"
rm -rf "$www_path"
mkdir -p "$www_path"
cp -a "$latest_path/." "$www_path"

exec "$@"
