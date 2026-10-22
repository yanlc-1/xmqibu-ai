# 部署与维护
源码仓库 https://github.com/yanlc-1/xmqibu-ai ，主分支 `main`。本地目录 `/opt/www/xmqibu-ai`，服务器检出目录 `/www/xmqibu-ai`，域名 https://xmqibu.com 。

## 数据库与采集
AI 站使用用户已建立的独立 MySQL 数据库 `xmqibu-ai`。主机、端口和账号由用户在私有文件中配置，不再假设与 attendance 共用实例或固定端口。`entries` 保存正文与来源，`editions` 保存每期 bundle，`collection_runs` 保存成功记录。Web 容器使用只读账号；本地采集使用可读写账号。数据库建表执行 `php scripts/install-schema.php data/database.local.php`，该账号需暂有 CREATE 权限；也可由数据库管理员在 `xmqibu-ai` 库中执行 `scripts/schema.sql`。建表后撤销应用账号的 CREATE 权限。

本地每日北京时间 12:00 的 Codex 任务先从数据库读取全量历史，筛选 AI 新闻与新项目，生成并核验 JSON，然后执行 `scripts/publish-local.sh data/edition-YYYY-MM-DD.json`。写入在单个 MySQL 事务内完成，失败保留此前内容。线上 PHP 页面每次只读取已提交的数据，不运行采集，不接收发布请求，也不生成静态 release。`scripts/publish-remote.sh` 是兼容旧任务的本地入口，转调用同一个本地写入脚本。

本地私有配置 `/opt/www/xmqibu-ai/data/database.local.php` 由模板 `data/database.local.example.php` 复制；服务器只读配置 `/www/xmqibu-ai-private/reader.php` 由模板 `ops/deploy/reader.example.php` 复制。两份配置都返回 `dsn`、`user`、`password`，可选 `options` 数组用于 TLS。若使用 CA 证书，将服务器证书放在 `/www/xmqibu-ai-private/ca.pem`，部署脚本会只读挂载为容器内的 `/secrets/ca.pem`。连接地址和端口按实际数据库填写；服务器配置中的主机必须能从 `nginx-proxy` 网络上的 Web 容器访问。验证本地连接可运行 `php scripts/check-database.php data/database.local.php`；服务器端还需通过候选容器的 `/health.json` 验证。不要将真实配置提交到 Git、复制到聊天或日志。数据库数据持久化属于 MySQL 实例，GitHub 不包含数据。

初次迁移先在旧服务器用 `scripts/export-legacy.php` 导出旧 `xmqibu_ai` 的三张表；本地用 `scripts/import-legacy.php` 导入空的 `xmqibu-ai`。导入脚本要求目标三张表均为空，保存原始记录 ID、正文和内容哈希，出错时回滚事务。迁移后核对期数、条目数、运行记录数，再允许新库接收每日采集。

## 代码上线
与 attendance 一样：GitHub `main` 更新触发签名校验 Webhook；服务器拉取源码、构建版本化 Docker 镜像、启动候选容器并检查 `/health.json`，通过后替换正式容器 `xmqibu-ai-app`。正式容器仅监听服务器 `127.0.0.1:19092`，宿主 Nginx 负责 HTTPS 与反向代理。Webhook 服务仅监听 `127.0.0.1:19095`，入口为 `https://xmqibu.com/-/github-webhook`，只接受签名有效、仓库为 `yanlc-1/xmqibu-ai`、分支为 `main` 的 push。服务器还保留每 5 分钟检查新提交的 cron 作为兜底。

部署脚本：`ops/deploy/deploy-from-git.sh`；镜像构建：`ops/deploy/build-image.sh`；容器切换与健康检查：`ops/deploy/deploy-image.sh`；上一镜像回滚：`ops/deploy/rollback-image.sh`。部署日志 `/var/log/xmqibu-ai-deploy.log`。仓库 CI 运行 Node、PHP 测试和 PHP 语法检查；上线后还应检查 GitHub CI、服务器提交、容器健康、网页和详情页。

## 切换与回滚
首次切换前保留旧静态站 `/www/xmqibu-ai/site/current`、原专用数据库容器 `xmqibu-ai-mysql` 和 Nginx 配置备份。先验证新数据库记录数、内容哈希、只读账号及候选 Web 容器，再将宿主 Nginx 切到 `ops/deploy/host-nginx.conf` 并执行 `nginx -t`、reload。失败可恢复备份 Nginx 配置，旧静态页面继续可用。后续镜像失败由部署脚本保留或恢复旧容器；也可手动执行 `ops/deploy/rollback-image.sh`。数据库迁移后不要再向旧容器写入，否则回滚必须先评估数据差异。

## 已知限制
数据库端口与网络访问由用户管理；经公网连接时应启用 MySQL TLS 并限制可访问来源。账号保持独立且最小权限。尚未配置异地数据库备份。Web 镜像沿用服务器已有的 PHP 7.1/Nginx 基础镜像以匹配 attendance 的部署环境，已关闭 PHP 文件上传、POST 数据读取和 session upload progress；后续应升级到受支持的 PHP 运行时。
