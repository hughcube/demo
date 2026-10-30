ARG BASE_IMAGE=crpi-j5t8nqf1b869w80g.cn-shenzhen.personal.cr.aliyuncs.com/stratum/demo-octane:base-latest

#########################################################################################################
#### 阶段一：代码构建与预热 (Builder Stage)
#### 核心目的：在构建层完成依赖安装、静态缓存固化、预加载分析与全量预热，避免运行镜像膨胀
#########################################################################################################
FROM ${BASE_IMAGE}-composer AS builder

# composer 私有仓库的账号密码
ARG COMPOSER_HZCUBE_USERNAME
ARG COMPOSER_HZCUBE_PASSWORD

# 标记当前处于构建阶段，通知框架跳过 Telescope/日志/APM 等运行期副作用，防止连接失败阻塞构建
ARG APP_BUILDING=1

# 生产镜像默认排除 dev 依赖，大幅降低 vendor 目录文件数与磁盘 I/O 读写损耗
ARG COMPOSER_NO_DEV=1

# 让构建期的 artisan 命令(如 config:cache)提前感知 Swoole 环境并固化配置
ARG LARAVEL_OCTANE=1

# 常驻内存进程池与网络拓扑参数：
# 通过 ARG 在镜像构建阶段传入，以支持不同部署环境（如测试/生产、不同 CPU/内存规格实例）构建专有镜像；
# 在构建阶段执行 php artisan config:cache 时，将环境变量直接固化进配置缓存，消除运行期动态读取解析开销。
ARG OCTANE_MAX_WORKERS=2
# 单 Worker 累计处理请求上限（达到后该 Worker 优雅退出并由 Swoole Manager 重新 fork 补位）：
# - 默认值 500：兼顾稳定性，防范业务代码中微小的全局变量/静态属性内存慢泄漏；
# - 高吞吐调优建议：若业务已全面保障严格无状态（配合骨架内的 OCTANE_GARBAGE=128 垃圾回收），
#   在万级 QPS 高并发生产场景下，建议通过 --build-arg OCTANE_MAX_REQUESTS=10000~50000 调大该值，
#   以避免 Worker 进程高频重启造成的 CPU 上下文切换尖刺与 MySQL/Redis 热连接频繁重建损耗。
ARG OCTANE_MAX_REQUESTS=500
# Swoole Task Worker 异步工作进程数（独立 OS 进程，由 Manager fork 派生）：
# - 默认值 0：纯无状态 REST API 服务且未开启 tick 心跳时，强烈推荐设为 0！
#   每个 Task Worker 都会完整引导一次 Laravel 框架（bootWorker），空转时白占约 20MB 物理内存(PSS)与 55MB 表面内存(RSS)；
# - 何时开启：当且仅当业务代码显式调用了 Octane::concurrently() 并发闭包，或配置了 octane.tick=true 周期心跳，
#   或需要使用 Swoole $server->task() 抛出进程内异步任务时才需要开启；
# - 开多少：
#   * 绝大多数无状态 API / 外部队列架构：固定设为 0；
#   * 存在轻量并发闭包时：建议设为与 CPU 核心数保持 1:1 或等于 OCTANE_MAX_WORKERS 的一半（如 1C/2C 实例配 1，4C 配 2）；
#   * 注意：重型/耗时任务建议优先投递到独立 Redis/DB 异步队列，避免在 Web 容器内堆叠过多 Task Worker 争抢主机算力。
ARG OCTANE_MAX_TASK_WORKERS=0

# Swoole Master 主进程内负责网络 I/O 多路复用(epoll)的 Reactor 线程数（POSIX 线程，非进程）：
# - 默认值 1：在 Master 进程内运行，负责 TCP 握手(accept)、非阻塞数据收发并分发给 Worker 处理；
# - 何时开启/调大：当单容器独占规格达到 >=4C/8C，且处于数万 QPS 高并发、大报文网络吞吐场景，
#   单 Reactor 线程的 epoll 事件循环跑满 100% CPU 成为网络 I/O 瓶颈时才需按需调大；
# - 开多少：
#   * 1C ~ 2C 规格或容器多租户混部环境（如当前服务器）：强烈建议固定设为 1！单 epoll 线程可轻松抗住万级 QPS，
#     盲目调大会导致多个 Reactor 线程争抢 accept 互斥锁与 epoll 锁，引发激烈的 CPU 上下文切换与缓存颠簸(Cache Bouncing)；
#   * 4C 规格高吞吐场景：建议设为 2；
#   * 8C 及以上高配独立物理机/独占节点：建议设为与 CPU 核心数 1:1 或等于 OCTANE_MAX_WORKERS（如 4~8）。
ARG OCTANE_REACTOR_NUM=1

# 生产镜像构建版本号（在流水线中通过 --build-arg BUILD_VERSION=<commit/tag> 注入）：
# - 云效(Aliyun Flow)流水线配置：在「镜像构建并推送至阿里云镜像仓库」任务的「构建参数 (ARGS)」中配置：
#   {"BASE_IMAGE":"${DOCKER_IMAGE_REPO}:${DOCKER_IMAGE_BASE_TAG}","BUILD_VERSION":"${CI_COMMIT_ID:-${BUILD_NUMBER}}"}
# - 云效流水线内置环境变量说明：
#   * ${CI_COMMIT_ID}：当前最后一次代码提交的 8 位短 Commit ID（推荐作为业务版本）
#   * ${CI_COMMIT_SHA}：最后一次代码提交的 40 位完整 Commit SHA
#   * ${CI_COMMIT_REF_NAME}：代码库的分支名或 Tag 名（如 master）
#   * ${BUILD_NUMBER}：流水线运行自然自增编号（1, 2, 3...）
ARG BUILD_VERSION="dev"

COPY . .

# 固化构建版本元数据文件(B方式)，运行期零开销读取
RUN php -r "echo json_encode(['build_version' => getenv('BUILD_VERSION') ?: 'dev', 'build_time' => date('c')], JSON_UNESCAPED_SLASHES);" > version.json

# 为后续 opcache:create-preload 准备合法 PHP 脚本起点
RUN echo "<?php " > preload.php

# 清理历史构建缓存与 Git 元数据，防止脏缓存污染与镜像层无效膨胀
RUN rm -rf /app/bootstrap/cache/*
RUN find /app -name '.git' | xargs rm -rf

# 使用慧哲镜像
#RUN composer config --global repositories.hzcube composer https://packagist.x4k.net/composer
#RUN composer config --global --auth http-basic.packagist.x4k.net ${COMPOSER_HZCUBE_USERNAME} ${COMPOSER_HZCUBE_PASSWORD}

# 生产级依赖安装：
RUN composer install --prefer-dist --optimize-autoloader $([ "${COMPOSER_NO_DEV}" = "1" ] && echo "--no-dev") --profile

# 权威类映射(Classmap Authoritative)：
# - 将全部 PSR-4/PSR-0 命名空间规则扁平化转换为 classmap 绝对路径数组(O(1) 内存寻址)；
# - 强制通知自动加载器“找不到类即不存在”，彻底杜绝运行时使用 file_exists 穿透扫描磁盘 I/O
RUN composer dump-autoload --optimize --classmap-authoritative

# 框架静态四大核心缓存预热(AOT 提前固化)：
# - config:cache：将几十个散落的配置文件合并为单一扁平 PHP 数组，运行时零配置解析开销
# - event:cache：提前发现并固化所有事件监听器映射，彻底消除运行时反射推导与目录遍历
# - view:cache：构建期完成 Blade 模板编译，杜绝首批用户请求命中时的 CPU 尖刺与磁盘写锁竞争
# - route:cache：将路由表预编译为正则表达式分发树，实现极速路由匹配
RUN php artisan config:clear && php artisan config:cache
RUN php artisan event:clear && php artisan event:cache
RUN php artisan view:clear && php artisan view:cache
RUN php artisan route:clear && php artisan route:cache

# OPcache 预加载机制：
# - 构建期生成核心依赖类的常驻内存加载清单(preload.php)并预执行验证；
# - 容器在 Serverless/Octane 启动时将高频核心类一次性固化到全局只读共享内存，跨 Worker 零开销共享
RUN php artisan opcache:create-preload
RUN php preload.php

# 在本地 SQLite 跑通数据迁移，提前拦截模型、表结构定义与索引语法断层
RUN rm -rf "${APP_BASE_PATH}/database/database.sqlite"
RUN touch "${APP_BASE_PATH}/database/database.sqlite"
RUN php artisan migrate --force --database=sqlite

# 提前生成 Swoole 状态文件与运行描述信息，省去容器启动时的探测耗时，实现秒级拉起
RUN php artisan octane:prepare \
        --host="0.0.0.0" \
        --port=80 \
        --workers="${OCTANE_MAX_WORKERS}" \
        --task-workers="${OCTANE_MAX_TASK_WORKERS}" \
        --max-requests="${OCTANE_MAX_REQUESTS}" \
        --state-file="$OCTANE_STATE_FILE"

#########################################################################################################
#### 阶段二：生产运行环境 (Runtime Stage)
#### 核心目的：剥离全部构建工具，只保留极简运行产物，并完成全量字节码 AOT 离线预编译
#########################################################################################################
FROM ${BASE_IMAGE}

# OPcache AOT 文件落盘编译开关:
# 读取基础镜像环境变量 OPCACHE_COMPILE_FILES (默认为 0，纯内存常驻模式)；
# 亦支持流水线在特定场景下通过 --build-arg OPCACHE_COMPILE_FILES=1 显式开启文件落盘预编译。
ARG OPCACHE_COMPILE_FILES

# 标记构建阶段（见 builder 阶段注释）
ARG APP_BUILDING=1

# 标记 Octane 运行模式（见 builder 阶段注释）
ARG LARAVEL_OCTANE=1

# 从 builder 层仅复制预热优化完的应用成品，剔除 Composer、Git 等重型构建工具，并赋予应用运行用户 www-data 完整属主权限
COPY --from=builder --chown=www-data:www-data ${APP_BASE_PATH} ${APP_BASE_PATH}

# ==============================================================================
# OPcache 全量字节码离线编译落盘控制 (opcache:compile-files):
# - 纯内存常驻模式 (OPCACHE_COMPILE_FILES=0，默认/推荐):
#   跳过落盘编译。配合 preload.php 与 JIT=1255，核心不可变类与热点机器码全部直接进驻
#   共享内存(SHM)，彻底消灭 /tmp/opcache 磁盘 I/O，并精简 50MB~80MB 镜像推拉体积；
# - 文件落盘编译模式 (OPCACHE_COMPILE_FILES=1):
#   针对超低内存规格或极端 Serverless 冷启动场景，将全量类编译为 .php.bin 二进制文件固化至磁盘。
# ==============================================================================
RUN if [ "${OPCACHE_COMPILE_FILES:-0}" = "1" ]; then \
        mkdir -p /tmp/opcache && chown -R www-data:www-data /tmp/opcache && \
        su -s /bin/sh - www-data -c "php -d opcache.enable_cli=1 /data/app/artisan opcache:compile-files"; \
    fi
