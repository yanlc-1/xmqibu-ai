# 部署与维护
源码仓库 https://github.com/yanlc-1/xmqibu-ai ，主分支 `main`。本地目录 `/opt/www/xmqibu-ai`，服务器检出目录 `/www/xmqibu-ai`，域名 https://xmqibu.com 。

## 数据库与采集
AI 站使用 attendance 所在的 MySQL 实例（服务器 `mysql4437` 容器、外网端口 `4437`）中的独立 `xmqibu_ai` 数据库。与 attendance 数据表和账号隔离。`entries` 保存正文与来源，`editions` 保存每期 bundle，`collection_runs` 保存成功记录。Web 容器只配置 SELECT 账号；本地采集使用独立写入账号，通过 SSH 隧道连接到服务器的 `127.0.0.1:4437`，不在源码或日志中存储密码。外部 MySQL 客户端如需连接，使用服务器地址、端口 `4437` 和获授权的专用账号；该端口是现有实例端口，不是新开放端口。

本地每日北京时间 12:00 的 Codex 任务先从数据库读取全量历史，筛选 AI 新闻与新项目，生成并核验 JSON，然后执行 `scripts/publish-local.sh data/edition-YYYY-MM-DD.json`。写入在单个 MySQL 事务内完成，失败保留此前内容。线上 PHP 页面每次只读取已提交的数据，不运行采集，不接收发布请求，也不生成静态 release。`scripts/publish-remote.sh` 是兼容旧任务的本地入口，转调用同一个本地写入脚本。

本地专用配置保存在被 Git 忽略的 `data/database.local.php`；服务器只读配置在 `/www/xmqibu-ai-private/reader.php`。本地配置中的 DSN 端口应从 `getenv('XMQIBU_AI_DB_PORT')` 读取，由隧道脚本在每次调用时设置，固定写 `4437` 将无法连接本地隧道。示例 DSN：`'mysql:host=127.0.0.1;port='.getenv('XMQIBU_AI_DB_PORT').';dbname=xmqibu_ai;charset=utf8mb4'`。两份配置都应返回 `dsn`、`user`、`password` 三个字段。不要将真实配置提交到 Git、复制到聊天或日志。数据库数据持久化属于 MySQL 实例，GitHub 不包含数据。

## 代码上线
与 attendance 一样：GitHub `main` 更新触发签名校验 Webhook；服务器拉取源码、构建版本化 Docker 镜像、启动候选容器并检查 `/health.json`，通过后替换正式容器 `xmqibu-ai-app`。正式容器仅监听服务器 `127.0.0.1:19092`，宿主 Nginx 负责 HTTPS 与反向代理。Webhook 服务仅监听 `127.0.0.1:19095`，入口为 `https://xmqibu.com/-/github-webhook`，只接受签名有效、仓库为 `yanlc-1/xmqibu-ai`、分支为 `main` 的 push。服务器还保留每 5 分钟检查新提交的 cron 作为兜底。

部署脚本：`ops/deploy/deploy-from-git.sh`；镜像构建：`ops/deploy/build-image.sh`；容器切换与健康检查：`ops/deploy/deploy-image.sh`；上一镜像回滚：`ops/deploy/rollback-image.sh`。部署日志 `/var/log/xmqibu-ai-deploy.log`。仓库 CI 运行 Node、PHP 测试和 PHP 语法检查；上线后还应检查 GitHub CI、服务器提交、容器健康、网页和详情页。

## 切换与回滚
首次切换前保留旧静态站 `/www/xmqibu-ai/site/current`、原专用数据库容器 `xmqibu-ai-mysql` 和 Nginx 配置备份。先验证新数据库记录数、内容哈希、只读账号及候选 Web 容器，再将宿主 Nginx 切到 `ops/deploy/host-nginx.conf` 并执行 `nginx -t`、reload。失败可恢复备份 Nginx 配置，旧静态页面继续可用。后续镜像失败由部署脚本保留或恢复旧容器；也可手动执行 `ops/deploy/rollback-image.sh`。数据库迁移后不要再向旧容器写入，否则回滚必须先评估数据差异。

## 已知限制
当前 MySQL 实例的外网 `4437` 端口已开放，AI 站账号应保持独立且最小权限；本地自动采集仍通过 SSH 隧道，避免数据库口令经公网明文传输。尚未配置异地数据库备份。Web 镜像沿用服务器已有的 PHP 7.1/Nginx 基础镜像以匹配 attendance 的部署环境，已关闭 PHP 文件上传、POST 数据读取和 session upload progress；后续应升级到受支持的 PHP 运行时。
