# 依赖构建
FROM composer AS composer

# 复制项目源码
COPY . /app

# 删除 vendor 压缩包减小上下文体积
RUN rm -f vendor.zip

# 安装依赖
RUN composer install --optimize-autoloader --no-interaction --no-progress

FROM huankong233/php-nginx:latest

USER root

RUN apk add --no-cache openssl php83-pdo php83-pdo_mysql mysql-client eudev-libs

# 补全环境
RUN apk update && \
    apk upgrade && \
    apk add --no-cache tzdata && \
    cp /usr/share/zoneinfo/Asia/Shanghai /etc/localtime && \
    echo "Asia/Shanghai" > /etc/timezone

# 复制项目源码
COPY .docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY .docker/fpm-pool.conf ${PHP_INI_DIR}/php-fpm.d/www.conf
COPY .docker/nginx.conf /etc/nginx/nginx.conf
COPY .docker/default.conf /etc/nginx/conf.d/default.conf
COPY .docker/entrypoint.sh /entrypoint.sh

# 复制构建后项目源码
RUN rm -rf /var/www/html
COPY --from=composer /app /var/www/HkList

# 赋权
RUN chmod a+x /entrypoint.sh

###########################################################################

# 默认工作目录
WORKDIR /var/www/html

# 开放端口
EXPOSE 8080

# 映射源码目录
VOLUME ["/var/www/html"]

# 启动
ENTRYPOINT ["/entrypoint.sh"]

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
