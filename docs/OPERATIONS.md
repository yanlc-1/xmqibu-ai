# 部署与维护
源码仓库 https://github.com/yanlc-1/xmqibu-ai ，`main` 为服务器部署来源。本地工作目录 `/opt/www/xmqibu-ai`；服务器检出目录 `/www/xmqibu-ai`；域名 https://xmqibu.com 和 https://www.xmqibu.com 。
服务器：现有SSH入口 root@111.231.204.222:34185，复用本机密钥，禁止将密钥写入仓库。
数据库：独立容器 xmqibu-ai-mysql / MySQL5.7，专用网络 xmqibu-ai-private（internal），无宿主机端口映射；数据库 xmqibu_ai，账户仅有该库权限。384MB内存上限。
存储：editions 保存原始当期JSON，entries 保存可检索条目和按日Star快照，collection_runs 保存成功运行记录。失败由发布进程非零退出和Codex通知报告。
网站：PHP CLI短时容器执行，运行完自动退出；静态站不依赖PHP常驻。HTTPS由宿主机Nginx直接服务 /www/xmqibu-ai/site/current 。HTTP容器 xmqibu-ai-web 跳转HTTPS，并保留ACME和verification.html验证路径。
私密目录 `/www/xmqibu-ai-private`（权限700）保存数据库文件、运行凭据、旧站压缩备份及原HTTPS配置；禁止复制或输出凭据。源代码与数据库均不在Web根目录中。
旧容器 xmqibu 已停止，原目录 /www/xmqibu 保留。没有删除旧站文件。

## 代码发布
本地修改先运行 `npm test`、`php -n tests/validate.php`、`php -n tests/dedupe.php` 和 PHP 语法检查，提交并推送到 GitHub `main`。服务器 crontab 每 5 分钟执行 `scripts/deploy-server.sh`：拉取 `main`，确认服务器无本地代码修改，检出新提交，再从 MySQL 现有内容重建一个静态 release 并原子切换。失败时还原旧代码，旧页面继续服务；下一轮可重试。部署记录在 `/var/log/xmqibu-ai-deploy.log`，手动执行相同脚本可立即发布。
`data/`、`site/` 和 `/www/xmqibu-ai-private/` 不进 Git；其中 `site/current` 是 Nginx 服务目录，数据库文件仍在 `/www/xmqibu-ai-private/mysql`。不要在服务器直接修改跟踪文件，或使用 `git clean` 删除运行数据。

## 内容发布
`scripts/publish-remote.sh data/edition-YYYY-MM-DD.json`，细节见 DAILY-RUNBOOK.md。
成功判断：命令成功、health.json日期与条目数量匹配、详情页可读。不要将仅有上传成功当成发布成功。

## 回滚页面
在服务器使用 `ls -lt /www/xmqibu-ai/site/releases` 找到明确的上一成功版本。创建临时相对软链指向 `releases/<已确认版本>`，再 `mv -Tf` 原子替换 current；不得猜版本或删除数据库。页面回滚不回退MySQL历史，后续重建要明确采用哪个版本的bundle。
如恢复旧站：停止 xmqibu-ai-web，启动原 xmqibu，恢复私密目录中的 xmqibu-https.conf.original，再 nginx -t 和 reload。注意原HTTPS配置本来就指向失效旧服务，仅用于恢复原状态，并不保证HTTPS正常。

## 备份与限制
上线时保留了原站压缩备份；新内容JSON同时存在本地与服务器data目录，MySQL数据持久化挂载。尚未配置异地数据库备份，应在后续运维阶段补齐。GitHub 只保存代码，不保存 MySQL 数据和每日内容 JSON。
每次发布保留静态release；长期运行时需保留近期成功版本并清理更早的构建产物，不能删除 current 指向目录。
当前数据源不覆盖全网，GitHub周趋势也不是全仓库严格排名。说明必须保留。任务在本机Codex运行，非服务器定时程序。
