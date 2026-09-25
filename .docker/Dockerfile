ARG BASE_IMAGE=php:8.5.0-cli-alpine3.21


#########################################################################################################
#### 时区
#########################################################################################################
FROM ${BASE_IMAGE} AS tz-builder

RUN apk update && apk add tzdata


#########################################################################################################
#### 运行基础镜像
#########################################################################################################
FROM ${BASE_IMAGE}

ARG SWOOLE_VERSION="v6.2.0"
ARG SWOOLE_HTTP_SERVER_NAME="hz"

ENV APP_BASE_PATH                   /data/app
ENV APP_STORAGE_PATH                ${APP_BASE_PATH}/storage

ENV LOG_PATH                        ${APP_STORAGE_PATH}/logs

ENV OCTANE_STATE_FILE               ${APP_STORAGE_PATH}/run/octane-server-state.json

COPY --from=tz-builder /usr/share/zoneinfo/Asia/Shanghai /etc/localtime

RUN set -eux; \
    # \
    # Update
    apk update; \
    #
    # Install build dependencies
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS; docker-php-source extract; \
    #
    # Install Bash
    apk add --no-cache bash; \
    #
    # Install Pcntl
    docker-php-ext-install pcntl; \
    #
    # Install BCMath
    docker-php-ext-install bcmath; \
    #
    # Install PDO_PGSQL
    apk add --no-cache libpq-dev; docker-php-ext-install pdo_pgsql; \
    #
    # Install xhprof ext
    pecl install xhprof; docker-php-ext-enable xhprof; \
    #
    # Install APCU ext
    pecl install apcu; docker-php-ext-enable apcu; \
    #
    # Install GMP
    apk add --no-cache gmp-dev; docker-php-ext-install gmp; \
    #
    # Install Intl
    apk add --no-cache icu-dev; docker-php-ext-install intl; \
    #
    # Install Zip
    apk add --no-cache libzip-dev; docker-php-ext-install zip; \
    #
    # Install Xlswriter
    pecl install xlswriter; docker-php-ext-enable xlswriter; \
    #
    # Install Redis
    pecl install redis; docker-php-ext-enable redis; \
    #
    # Install Mbstring (oniguruma for regex)
    apk add --no-cache oniguruma-dev; docker-php-ext-install mbstring; \
    #
    # Install Zend OpCache
    # 说明: 配置 opcache 以优化 PHP 性能,适用于生产环境 (Swoole/Octane)
    # 注意: 以下配置针对 Laravel Octane + Swoole,与 artisan serve 的配置要求不同
    # PHP 8.5+ 默认启用 opcache,无需手动 enable
    if ! php -m | grep -qi opcache; then docker-php-ext-enable opcache; fi; \
    rm -rf /tmp/opcache; mkdir -p /tmp/opcache; chmod -R 0777 /tmp/opcache; \
    rm -rf /usr/local/etc/php/conf.d/*opcache.ini; touch /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini; \
    { \
        php -r "echo version_compare(PHP_VERSION, '8.5.0', '<') ? 'zend_extension=opcache' . PHP_EOL : '; PHP 8.5+ 默认内置 opcache,无需 zend_extension=opcache' . PHP_EOL;"; \
        echo "opcache.enable=1 ; 启用 opcache"; \
        echo "opcache.enable_cli=1 ; CLI 模式也启用 (适用于 Octane)"; \
        echo "opcache.jit=disable ; 显式禁用 JIT (避免误开启/额外内存开销)"; \
        echo "opcache.jit_buffer_size=0 ; 禁用 JIT buffer"; \
        echo ";opcache.jit=1205 ; (可选) 启用 JIT,建议压测后再启用"; \
        echo ";opcache.jit_buffer_size=256M ; (可选) JIT 缓冲区大小"; \
        echo "opcache.memory_consumption=512 ; opcache 内存大小 512MB"; \
        echo "opcache.interned_strings_buffer=64 ; 字符串缓冲区 64MB (存储类名/函数名等)"; \
        echo "opcache.max_accelerated_files=100000 ; 最大缓存文件数 (建议略大于 PHP 文件总数)"; \
        echo "opcache.validate_timestamps=0 ; 禁用时间戳验证 (生产环境不检查文件修改,性能最佳)"; \
        echo "opcache.revalidate_freq=86400 ; 重新验证频率 (validate_timestamps=0 时无效)"; \
        echo "opcache.revalidate_path=0 ; 禁用路径验证 (使用完整路径)"; \
        echo "opcache.validate_permission=0 ; 禁用权限验证 (提升性能)"; \
        echo "opcache.save_comments=0 ; 不保留注释 (已用 route:cache/event:cache, 运行时无需反射)"; \
        echo "opcache.enable_file_override=1 ; 启用 file_exists 等函数的缓存优化 (代码不变更时更合适)"; \
        echo "opcache.max_file_size=0 ; 不限制文件大小"; \
        echo "opcache.file_update_protection=0 ; 文件更新保护时间 0秒 (代码不变更时可设为 0)"; \
        echo "opcache.use_cwd=0 ; 使用绝对路径作为缓存键"; \
        echo "opcache.file_cache=/tmp/opcache ; 文件缓存目录 (持久化缓存到磁盘)"; \
        echo "opcache.file_cache_consistency_checks=0 ; 禁用文件缓存一致性检查 (提升性能)"; \
        echo "opcache.huge_code_pages=0 ; 容器内无法访问宿主机 HugePages,关闭以消除启动警告"; \
        echo "opcache.preload=${APP_BASE_PATH}/preload.php ; 预加载脚本 (启动时加载常用类, PHP 7.4+)"; \
        echo "opcache.preload_user=root ; 预加载用户 (以 root 运行时必须指定)"; \
        echo "opcache.error_log=${LOG_PATH}/php-opcache-error.log ; opcache 错误日志"; \
        echo "opcache.blacklist_filename=/usr/local/etc/php/opcache.blacklist ; 黑名单文件 (指定不缓存的目录)"; \
    } > /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini; \
    # 创建必要的目录和文件 \
    mkdir -p "${APP_BASE_PATH}" "${LOG_PATH}"; echo "<?php " > "${APP_BASE_PATH}/preload.php"; \
    rm -rf /usr/local/etc/php/opcache.blacklist; touch /usr/local/etc/php/opcache.blacklist; \
    # \
    # Install Swoole \
    apk add --no-cache libstdc++ libpq; \
    apk add --no-cache --virtual .build-deps-ext-swoole curl-dev linux-headers openssl-dev pcre-dev pcre2-dev zlib-dev; \
    docker-php-ext-install sockets; \
    mkdir -p /usr/src/php/ext/swoole; \
    curl -sfL https://github.com/swoole/swoole-src/archive/${SWOOLE_VERSION}.tar.gz -o swoole.tar.gz; \
    tar xfz swoole.tar.gz --strip-components=1 -C /usr/src/php/ext/swoole; \
    if [ -n "${SWOOLE_HTTP_SERVER_NAME}" ]; then \
        sed -i "s/#define SW_HTTP_SERVER_SOFTWARE \"swoole-http-server\"/#define SW_HTTP_SERVER_SOFTWARE \"${SWOOLE_HTTP_SERVER_NAME}\"/"  "/usr/src/php/ext/swoole/include/swoole_config.h"; \
    fi; \
    docker-php-ext-configure swoole; \
    docker-php-ext-install -j$(nproc) swoole; \
    rm -f swoole.tar.gz; apk del --no-network .build-deps-ext-swoole;  \
    # \
    # Clear \
    docker-php-source delete; pecl clear-cache || true; apk del --no-network .build-deps; \
    rm -rf "/tmp/opcache/*" "${LOG_PATH}/*" "/tmp/pear/"; \
    # \
    # test
    php -m

WORKDIR ${APP_BASE_PATH}

# ==============================================================================
# 生产运行时启动入口与 PHP 极致性能参数说明:
# - expose_php="Off"              : 隐藏 PHP 版本指纹，减少响应头字节开销与安全信息泄漏
# - variables_order="EGPCS"       : 确保解析环境变量(E)与服务器变量(S)，常驻进程与容器化场景必需
# - auto_globals_jit="On"         : 启用超全局变量 JIT 按需初始化，跳过未访问全局变量的注册损耗
# - pcre.jit="1"                  : 开启正则 JIT 机器码加速，大幅提升路由正则分发树匹配性能
# - output_buffering="4096"       : 开启 4KB 响应块缓冲，合并小数据包，减少底层网络 write 系统调用
# - default_socket_timeout="3"    : 严格限制网络调用超时为 3s，防止微服务/外部网络异常挂死 Worker 进程
# - realpath_cache_size="32M"     : 扩容真实路径缓存至 32MB，完全覆盖全量 Autoload 与 Preload 文件 stat
# - realpath_cache_ttl="86400"    : 路径缓存有效期设为 1 天(86400s)，生产代码不变更，彻底消除文件存在性检查开销
# - memory_limit="512M"           : 限制单 Worker 内存上限，配合 Octane 的垃圾回收防爆内存
# - zend.assertions="-1"          : 生产环境彻底关闭断言代码生成，零 CPU 执行损耗
# - error_reporting="E_ALL"       : 全量捕获异常与错误，配合常驻进程平滑记录
# - error_log="${LOG_PATH}/..."   : 将错误统一重定向到标准持久化日志文件
# ==============================================================================
CMD LARAVEL_OCTANE=1 /usr/local/bin/php \
        -d expose_php="Off" \
        -d variables_order="EGPCS" \
        -d auto_globals_jit="On" \
        -d pcre.jit="1" \
        -d output_buffering="4096" \
        -d default_socket_timeout="3" \
        -d realpath_cache_size="32M" \
        -d realpath_cache_ttl="86400" \
        -d memory_limit="512M" \
        -d zend.assertions="-1" \
        -d error_reporting="E_ALL" \
        -d error_log="${LOG_PATH}/php-error.log" \
        "${APP_BASE_PATH}/vendor/bin/swoole-server" "${OCTANE_STATE_FILE}"
