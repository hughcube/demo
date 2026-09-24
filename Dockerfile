ARG BASE_IMAGE=crpi-vrt7o5qsysdzjsbh.cn-shanghai.personal.cr.aliyuncs.com/zycube/demo:base-latest

#########################################################################################################
#### 阶段一：代码构建与预热 (Builder Stage)
#### 核心目的：在构建层完成依赖安装、静态缓存固化、预加载分析与全量预热，避免运行镜像膨胀
#########################################################################################################
FROM ${BASE_IMAGE}-composer AS builder

# composer 慧哲私有仓库的账号密码
ARG COMPOSER_HZCUBE_USERNAME
ARG COMPOSER_HZCUBE_PASSWORD

# 标记当前处于构建阶段，通知框架跳过 Telescope/日志/APM 等运行期副作用，防止连接失败阻塞构建
ARG APP_BUILDING=1

# 生产镜像默认排除 dev 依赖，大幅降低 vendor 目录文件数与磁盘 I/O 读写损耗
ARG COMPOSER_NO_DEV=1

# 让构建期的 artisan 命令(如 config:cache)提前感知 Swoole 环境并固化配置
ARG LARAVEL_OCTANE=1

# 构建期固化 Worker/Reactor 等常驻进程池拓扑参数，消除容器启动探测开销
ARG OCTANE_GARBAGE=50
ARG OCTANE_MAX_WORKERS=2
ARG OCTANE_MAX_REQUESTS=500
ARG OCTANE_MAX_TASK_WORKERS=1
ARG OCTANE_MAX_EXECUTION_TIME=30
ARG OCTANE_REACTOR_NUM=1

# 生产镜像构建版本号（在流水线中通过 --build-arg BUILD_VERSION=<commit/tag> 注入）
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
# - --prefer-dist：优先使用预打包压缩包，减少 Git 握手与网络传输
# - --optimize-autoloader：生成快速类映射，加快加载效率
# - --profile：输出各阶段耗时与内存使用，辅助构建性能分析
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
RUN php artisan migrate --force

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

# 是否跳过 OPcache 预编译（dev 构建文件数过多可能导致 OOM，可传 --build-arg SKIP_OPCACHE_COMPILE=1 跳过）
ARG SKIP_OPCACHE_COMPILE=0

# 标记构建阶段（见 builder 阶段注释）
ARG APP_BUILDING=1

# 标记 Octane 运行模式（见 builder 阶段注释）
ARG LARAVEL_OCTANE=1

# 从 builder 层仅复制预热优化完的应用成品，剔除 Composer、Git 等重型构建工具
COPY --from=builder  ${APP_BASE_PATH} ${APP_BASE_PATH}

# OPcache 全量字节码离线编译(AOT Compile)：
# 在容器镜像封存前，将全量业务与 vendor 代码编译为 OPcache 操作码(Bytecode)存入持久缓存；
# 容器启动后首批请求直接命中内存中的操作码，彻底消除运行时词法与语法解析延迟，实现真正的“全热态零延迟冷启动”
RUN if [ "${SKIP_OPCACHE_COMPILE}" = "0" ]; then php /data/app/artisan opcache:compile-files; fi
