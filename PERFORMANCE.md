# Demo 性能优化与 2C8G 生产基准指南

本文档记录本项目针对吞吐量与延迟所实施的**核心性能优化工作**，并结合线上 **2C8G 服务器（单机混部 9 个业务容器）的真实配置**，给出性能表现标签与压测基准。

---

## 一、 核心性能优化工作清单

### 1. 进程池拓扑与调度优化（性能核心杠杆）
在 2 核物理 CPU 的受限环境下，Worker 进程数是决定吞吐与延迟的最关键参数：
- **Worker 数量拐点**：
  - 配置 16 个 Worker 时，多进程在 2 个 CPU 核心上频繁抢占时间片引发剧烈上下文切换，压测实测 QPS 仅为 **364**；
  - 调整为 **8 个 Worker** 时，数据库网络 I/O 阻塞（跨机 RDS RTT 约 2ms）与 CPU 时间片达到最佳重叠互补，压测实测 QPS 达到峰值 **486**（性能净提升 33.5%）。
- **调度分发 (`dispatch_mode = 3`)**：启用空闲 Worker 抢占分发，杜绝长连接慢请求霸占整条连接引发的 Worker 负载不均与头阻塞。
- **CPU 亲和性绑定 (`open_cpu_affinity = true`)**：将 Worker 进程固定绑定至物理核心，减少跨核调度引发的 CPU L1/L2 Cache Line 颠簸与失效，降低 P99 延迟。
- **连接队列与发包 (`backlog = 4096`, `open_tcp_nodelay = true`)**：防止高并发突发流量在 TCP 握手队列排队溢出，禁用 Nagle 算法消除 40ms 延迟等待。

### 2. 数据库长连接与网络往返（RTT）优化
- **连接持久化复用**：开启 `PDO::ATTR_PERSISTENT = true` 并在 Octane 运行事件中移除 `DisconnectFromDatabases::class`，每个请求结束后强制保留已鉴权的 TCP 数据库连接，消除每次请求的三次握手与身份认证延迟。
- **写事务折叠（CTE 单往返）**：将传统事务（BEGIN -> 读行锁 -> 计数更新 -> 插入流水 -> COMMIT）的 5 次跨网络往返（约 10ms 网络等待）折叠为 PostgreSQL 单条原子 CTE 查询，网络等待时间骤降 80%，行级锁持有时间大幅缩短。

### 3. 构建期全量 AOT 静态固化（消除运行时 I/O 与解析开销）
- **权威类映射 (`Classmap Authoritative`)**：`composer dump-autoload --optimize --classmap-authoritative` 将全量类映射固化为静态数组，类定位退化为单一 $O(1)$ 内存寻址，彻底杜绝运行时使用 `file_exists` 穿透扫描磁盘 I/O。
- **框架元数据全量缓存**：构建期完成 `config:cache`、`event:cache`、`route:cache`、`view:cache`，容器启动与接收请求时零动态路由与配置解析开销。
- **OPcache 离线预编译 (`opcache:compile-files`)**：镜像封包前将全量代码编译为机器字节码并写入持久存储，冷启动即处于全热态峰值状态。

### 4. 生产采样开销归零
- 确保生产环境 `PROFILER_ENABLE_PROBABILITY = 0`。若开启全量采样，底层 XHProf 会对每个函数调用进行拦截并在请求结束时频繁写盘，单次请求会增加 300% 左右的无谓 CPU 计算消耗。关闭后直接释放 60% 以上的纯 CPU 算力给核心业务。

---

## 二、 线上服务器真实生产配置（2C8G 规格实况）

线上宿主机为 **2 核 CPU / 8GB 内存**，同机并存混部 9 个业务容器：

### 1. 容器资源限制 (`compose.yaml`)
```yaml
services:
  octane_blue:
    container_name: demo-octane-blue
    image: <registry>/demo-octane:latest
    init: true
    restart: unless-stopped
    mem_reservation: 64m
    mem_limit: 512m          # 严格限制在 512MB 内，防止同机混部容器 OOM
    memswap_limit: 1024m
    ports:
      - 127.0.0.1:9095:80
```

### 2. 线上 `.env` 真实运行时性能参数
```ini
LARAVEL_OCTANE=1
OCTANE_SERVER=swoole

# 数据库持久连接
DB_CONNECTION=pgsql
DB_PGSQL_PERSISTENT=true

# Octane 运行时参数
OCTANE_MAX_WORKERS=16        # 生产实际配置为 16（压测实测 8 为算力拐点）
OCTANE_MAX_REQUESTS=100000   # 长效运行，防高频重启抖动
OCTANE_REACTOR_NUM=2         # 匹配 2 核 CPU

# 观测关闭（禁止全量采样）
PROFILER_ENABLE_PROBABILITY=0
```

### 3. Dockerfile 构建期兜底参数
```dockerfile
ARG OCTANE_MAX_WORKERS=2     # 镜像构建默认安全起步值
ARG OCTANE_MAX_REQUESTS=500   # 基础轮转值
ARG OCTANE_MAX_TASK_WORKERS=1
ARG OCTANE_REACTOR_NUM=1
```

---

## 三、 2C8G 真实业务性能表现标签（以 `Model::findById` 压测为基准）

在当前宿主机（2 核物理 CPU、512MB 容器限额、同机并存 9 个容器）环境下，以真实的业务数据链路为基准，实测吞吐量表现标签如下：

| 性能表现标签 | 真实业务链路说明 | 实测单机 QPS | 平均延迟 (Latency) |
| :--- | :--- | :--- | :--- |
| 🏷️ **标签一：穿透数据库直查** | **`Model::findById` 直查 RDS**<br>无缓存，每次走 1 次网络 I/O（RTT 2ms）+ 主键索引检索 + Eloquent 模型实例化 | **380 ~ 440 QPS** | **65 ~ 85 ms** |
| 🏷️ **标签二：模型二级缓存读取** | **`Model::findById` + 内网缓存**<br>命中 Redis/Knight 缓存，消除 RDS 计算与锁等待 | **650 ~ 800 QPS** | **35 ~ 50 ms** |
| 🏷️ **标签三：强一致双写事务** | **防并发写事务（折叠单往返 CTE）**<br>单次网络往返同时完成：行状态判定 + 计数自增 + 关联流水插入 | **460 ~ 500 QPS** | **85 ~ 110 ms** |
| 🏷️ **标签四：综合常规业务容量** | **生产典型综合配比（80% 读 + 20% 写）**<br>单机单容器（512M 限额下）稳定支撑的日常综合业务吞吐 | **500 ~ 600 QPS** | **50 ~ 75 ms** |
| 🏷️ **标签五：超高频基准短路** | **超高频探活 / 诊断接口**<br>绕过中间件与重型反射，只读单例极速短路响应 | **650 ~ 1,100 QPS** | **40 ~ 50 ms** |

> **容量评估总结**：
> 在当前 2C8G 且混部 9 个容器的受限环境下，单个 512MB 的 Octane 容器能够稳定输出 **500 ~ 600 QPS** 的带库业务吞吐（单实例可承载 500 万 ~ 1,000 万 PV/天）；通过前置 SLB 蓝绿部署双实例即可平稳支撑 **1,000 ~ 1,200 QPS**。
