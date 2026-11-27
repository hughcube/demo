ARG BASE_IMAGE=php:8.5-cli-alpine3.24


#########################################################################################################
#### 时区
#########################################################################################################
FROM ${BASE_IMAGE} AS tz-builder

RUN apk update && apk add --no-cache tzdata


#########################################################################################################
#### 运行基础镜像
#########################################################################################################
FROM ${BASE_IMAGE}

ARG SWOOLE_VERSION="v6.2.6"
ARG SWOOLE_HTTP_SERVER_NAME="hz"

ENV APP_BASE_PATH                   /data/app
ENV APP_STORAGE_PATH                ${APP_BASE_PATH}/storage

ENV LOG_PATH                        ${APP_STORAGE_PATH}/logs

ENV OCTANE_STATE_FILE               ${APP_STORAGE_PATH}/run/octane-server-state.json

# OPcache AOT 文件落盘开关:
# - 0 (默认/推荐): 纯共享内存常驻模式。配合 opcache.preload 与 jit=disable，全量字节码直接驻留
#   共享内存(SHM)，避免 /tmp/opcache 落盘导致镜像膨胀与 cgroup 页缓存上升。
# - 1 (可选): 离线文件缓存模式。适用于短生命周期 CLI/定时任务脚本构建，在构建阶段动态开启
#   opcache.file_cache=/tmp/opcache 并将全量代码编译为 .php.bin 固化至磁盘。
ENV OPCACHE_COMPILE_FILES           0

COPY --from=tz-builder /usr/share/zoneinfo/Asia/Shanghai /etc/localtime

RUN set -eux; \
    echo "Asia/Shanghai" > /etc/timezone; \
    # \
    # Install build & runtime dependencies (Alpine 3.24 musl) \
    apk update; \
    apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        curl-dev \
        linux-headers \
        openssl-dev \
        pcre-dev \
        pcre2-dev \
        zlib-dev; \
    apk add --no-cache \
        bash \
        libstdc++ \
        libpq-dev \
        gmp-dev \
        icu-dev \
        libzip-dev \
        oniguruma-dev; \
    docker-php-source extract; \
    # \
    # Install core PHP extensions \
    docker-php-ext-install -j"$(nproc)" pcntl bcmath pdo_pgsql gmp intl zip mbstring sockets; \
    # \
    # Install PECL extensions \
    pecl install xhprof apcu xlswriter redis; \
    docker-php-ext-enable xhprof apcu xlswriter redis; \
    # \
    # Install / Configure Zend OPcache (Alpine 轻量高并发基线: 512M + save_comments=1 + jit=disable) \
    if ! php -m | grep -qi opcache; then docker-php-ext-enable opcache; fi; \
    rm -rf /tmp/opcache /var/log/swoole; \
    mkdir -p /tmp/opcache /var/log/swoole "${APP_BASE_PATH}" "${LOG_PATH}"; \
    rm -rf /usr/local/etc/php/conf.d/*opcache.ini; \
    { \
        php -r "echo version_compare(PHP_VERSION, '8.5.0', '<') ? 'zend_extension=opcache' . PHP_EOL : '; PHP 8.5+ 默认内置 opcache,无需 zend_extension=opcache' . PHP_EOL;"; \
        echo "opcache.enable=1 ; 启用 opcache"; \
        echo "opcache.enable_cli=1 ; CLI 模式也启用 (适用于 Octane 及常驻守护进程)"; \
        echo "opcache.jit=disable ; 关闭 JIT，避免多态 Guard 判定与侧退出导致 Web 吞吐负优化"; \
        echo "opcache.jit_buffer_size=0 ; 禁用 JIT 缓冲，释放共享内存，纯靠 Preload 实现极致低延迟"; \
        echo "opcache.memory_consumption=512 ; 512MB OPcache 共享内存，充裕承载全量字节码与注释"; \
        echo "opcache.interned_strings_buffer=64 ; 64MB 驻留字符串缓冲区"; \
        echo "opcache.max_accelerated_files=100000 ; 最大缓存文件数"; \
        echo "opcache.validate_timestamps=0 ; 禁用时间戳验证 (生产环境代码不可变)"; \
        echo "opcache.revalidate_freq=86400 ; validate_timestamps=0 时生效兜底"; \
        echo "opcache.revalidate_path=0 ; 禁用路径验证"; \
        echo "opcache.validate_permission=0 ; 禁用权限验证"; \
        echo "opcache.save_comments=1 ; 保留注释 (兼容依赖注解/反射的框架与扩展)"; \
        echo "opcache.enable_file_override=1 ; 优化 file_exists/is_file 等调用"; \
        echo "opcache.max_file_size=0 ; 不限制文件大小"; \
        echo "opcache.file_update_protection=0 ; 文件更新保护时间 0秒"; \
        echo "opcache.use_cwd=0 ; 使用绝对路径作为缓存键"; \
        echo "; file_cache 仅在 OPCACHE_COMPILE_FILES=1 (短 CLI 落盘预编译) 时动态开启，避免 Octane 页缓存膨胀与磁盘竞争"; \
        echo "opcache.huge_code_pages=0 ; Alpine (musl) 禁用大页代码段，消除启动告警并控制内存锁定"; \
        echo "opcache.preload=${APP_BASE_PATH}/preload.php ; 预加载脚本"; \
        echo "opcache.preload_user=www-data ; 与 Swoole Worker 降权用户严格一致"; \
        echo "opcache.error_log=${LOG_PATH}/php-opcache-error.log ; opcache 错误日志"; \
        echo "opcache.blacklist_filename=/usr/local/etc/php/opcache.blacklist ; 黑名单文件"; \
    } > /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini; \
    echo "<?php " > "${APP_BASE_PATH}/preload.php"; \
    touch /var/log/swoole/swoole_http.log /usr/local/etc/php/opcache.blacklist; \
    chown -R www-data:www-data "${APP_BASE_PATH}" "${LOG_PATH}" /tmp/opcache /var/log/swoole; \
    chmod 0755 /tmp/opcache /var/log/swoole; \
    chmod 0664 /var/log/swoole/swoole_http.log; \
    # \
    # Install Swoole \
    mkdir -p /usr/src/php/ext/swoole; \
    curl -sfL "https://github.com/swoole/swoole-src/archive/${SWOOLE_VERSION}.tar.gz" -o swoole.tar.gz; \
    tar xfz swoole.tar.gz --strip-components=1 -C /usr/src/php/ext/swoole; \
    if [ -n "${SWOOLE_HTTP_SERVER_NAME}" ]; then \
        sed -i "s/#define SW_HTTP_SERVER_SOFTWARE \"swoole-http-server\"/#define SW_HTTP_SERVER_SOFTWARE \"${SWOOLE_HTTP_SERVER_NAME}\"/" "/usr/src/php/ext/swoole/include/swoole_config.h"; \
    fi; \
    docker-php-ext-configure swoole; \
    docker-php-ext-install -j"$(nproc)" swoole; \
    rm -f swoole.tar.gz; \
    # \
    # Clear build dependencies & temp caches \
    docker-php-source delete; \
    pecl clear-cache || true; \
    apk del --no-network .build-deps; \
    rm -rf /tmp/opcache/* "${LOG_PATH}"/* /tmp/pear/*; \
    # \
    # Verify all required extensions load cleanly & inspect Swoole \
    php -r 'foreach(["pcntl","bcmath","pdo_pgsql","gmp","intl","zip","mbstring","sockets","xhprof","apcu","xlswriter","redis","swoole","Zend OPcache"] as $e){ if(!extension_loaded($e)){fwrite(STDERR,"MISSING EXT: $e\n"); exit(1);} }' && php --ri swoole

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
CMD mkdir -p /var/log/swoole && touch /var/log/swoole/swoole_http.log && chown -R www-data:www-data /var/log/swoole && chmod 0755 /var/log/swoole && chmod 0664 /var/log/swoole/swoole_http.log && \
    export LARAVEL_OCTANE=1 && exec /usr/local/bin/php \
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
