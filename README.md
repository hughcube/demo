# Demo（Laravel 13）

一个用于演示的 Laravel 高性能骨架项目，集成了阿里云 OSS、函数计算、Octane (Swoole)、Caddy 网关等常用组件，方便在本地或容器内快速体验。

> 架构细节与性能调优请参阅：[高性能与高并发架构深度解析指南](./PERFORMANCE.md)

## 环境要求
- PHP 8.4+，Composer，。
- 可选：Docker / Docker Compose（仓库已提供 `docker-compose.yml`）。

## 快速开始（推荐容器方式）
1. 复制环境变量：`cp .env.example .env`（如需自定义，可直接编辑 `.env`）。
2. 启动容器：`docker compose up -d`。
3. 进入容器安装依赖（必须使用下方代理命令，否则国内网络无法拉取依赖）：
   ```bash
   http_proxy=http://host.docker.internal:7890 https_proxy=http://host.docker.internal:7890 composer install -vvv
   ```
4. 启动服务：容器默认已在 5012 暴露 80 端口，可直接访问；若需要手动启动可执行 `php artisan serve --host=0.0.0.0 --port=80`。

## 本地运行（非容器）
1. 安装 PHP 扩展：pdo、opcache、xhprof（如果只本地开发可跳过 xhprof）。
2. 初始化：
   ```bash
   cp .env.example .env
   php artisan key:generate
   php artisan migrate
   ```

## 其他说明
- 代理命令是必须的：仓库依赖了私有/国内镜像的包，未设置代理会导致 Composer 安装失败。
- 如果使用自定义镜像或镜像站，可调整 `.env` 中的 `COMPOSER_PROXY` 或自行配置 Composer 源。
