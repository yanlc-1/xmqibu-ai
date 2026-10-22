# 启步 AI
面向中文AI爱好者与开发者的资讯站，MySQL存储、数据库驱动的只读页面，每天北京时间12:00由Codex在本地采集与整理。

- [设计与数据口径](docs/PLAN.md)
- [每天采集与发布](docs/DAILY-RUNBOOK.md)
- [部署与回滚](docs/OPERATIONS.md)
- [OpenSpec](openspec/changes/add-ai-digest/proposal.md)

本地：`npm ci --ignore-scripts` → `npm run collect` → 基于原文编写当日bundle → `php -n scripts/preview.php data/edition-YYYY-MM-DD.json .preview`。
验证：`npm test`；`npm audit --audit-level=low`；`php -n tests/validate.php`；`php -n tests/dedupe.php`；`php tests/web.php`；`openspec validate update-db-driven-deployment --strict`。
预览：`python3 -m http.server 18763 --bind 127.0.0.1 --directory .preview`。
正式内容发布：`scripts/publish-local.sh data/edition-YYYY-MM-DD.json`。本地通过 SSH 隧道写入 MySQL，线上容器只读展示。日常任务不修改站点代码或服务器配置。

代码通过 GitHub `main` 管理；服务器拉取代码并构建版本化 Web 镜像，按健康检查切换容器。数据库凭据和内容文件不进 Git，详见[部署说明](docs/OPERATIONS.md)。
