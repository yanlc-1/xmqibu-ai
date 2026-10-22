# 启步 AI
面向中文AI爱好者与开发者的资讯站，MySQL存储、静态页面发布，每天北京时间12:00由Codex采集与整理。

- [设计与数据口径](docs/PLAN.md)
- [每天采集与发布](docs/DAILY-RUNBOOK.md)
- [部署与回滚](docs/OPERATIONS.md)
- [OpenSpec](openspec/changes/add-ai-digest/proposal.md)

本地：`npm ci --ignore-scripts` → `npm run collect` → 基于原文编写当日bundle → `php -n scripts/preview.php data/edition-YYYY-MM-DD.json .preview`。
验证：`npm test`；`php -n tests/validate.php`；`openspec validate add-ai-digest --strict`。
预览：`python3 -m http.server 18763 --bind 127.0.0.1 --directory .preview`。
正式内容发布：`scripts/publish-remote.sh data/edition-YYYY-MM-DD.json`。日常任务不修改站点代码或服务器配置。

代码通过 GitHub `main` 管理；服务器每 5 分钟检查新提交并从 MySQL 现有内容重建静态页面。数据库与内容文件独立留在服务器，详见[部署说明](docs/OPERATIONS.md)。
