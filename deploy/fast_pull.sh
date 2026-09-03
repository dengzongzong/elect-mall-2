#!/bin/bash
# ============================================================
# 快速拉取脚本：只拉取PHP文件，不重新构建前端（因为前端已经构建好了）
# 用于快速修复数据库和登录问题
# ============================================================

set -e

PROJECT_DIR="/var/www/elect-mall"
LOG_FILE="${PROJECT_DIR}/deploy/deploy.log"

log() {
    local time=$(date '+%Y-%m-%d %H:%M:%S')
    echo "[${time}] $1" | tee -a "$LOG_FILE"
}

log "========== FAST PULL START =========="

cd "$PROJECT_DIR"

# 只拉取，不碰node_modules和构建
log "[1/3] Git pull..."
git pull origin main 2>&1 | tee -a "$LOG_FILE"

log "[2/3] Update permissions..."
chown -R nginx:nginx "${PROJECT_DIR}/crmeb/public" 2>/dev/null
chmod -R 755 "${PROJECT_DIR}/crmeb/public" 2>/dev/null

log "[3/3] Reload PHP-FPM..."
systemctl reload php-fpm 2>/dev/null || systemctl restart php-fpm 2>/dev/null

log "========== FAST PULL DONE =========="
echo "Done! Now you can visit:"
echo " - http://your-server/web_exec.php"
echo " - http://your-server/fix_all.php"
