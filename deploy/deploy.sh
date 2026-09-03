#!/bin/bash
# ============================================================
# 快速部署脚本（临时简化版，跳过npm构建，快速拉取代码）
# ============================================================

set +e

PROJECT_DIR="/var/www/elect-mall"
LOG_FILE="${PROJECT_DIR}/deploy/deploy.log"
LOCK_FILE="${PROJECT_DIR}/deploy/deploy.lock"
WEB_ROOT="${PROJECT_DIR}/crmeb/public"

log() {
    local time=$(date '+%Y-%m-%d %H:%M:%S')
    echo "[${time}] $1" | tee -a "$LOG_FILE"
}

if [ -f "$LOCK_FILE" ]; then
    if [ "$(find "$LOCK_FILE" -mmin +10 2>/dev/null)" ]; then
        log "[WARN] Lock file > 10min, force remove"
        rm -f "$LOCK_FILE"
    else
        log "[WARN] Deploy in progress, skip"
        exit 0
    fi
fi
trap "rm -f $LOCK_FILE" EXIT
touch "$LOCK_FILE"

log "========== FAST DEPLOY =========="

# 1. 拉取最新代码
log "[1/4] Git pull..."
cd "$PROJECT_DIR"
git stash 2>/dev/null || true
git fetch origin main 2>&1 | tee -a "$LOG_FILE"
git reset --hard origin/main 2>&1 | tee -a "$LOG_FILE"
log "[1/4] Done"

# 2. 同步crmeb/public目录下的文件（PHP和静态文件）
log "[2/4] Sync files..."
# 确保目录存在
mkdir -p "${WEB_ROOT}/admin"
mkdir -p "${WEB_ROOT}/home"
# 如果有新构建的PC前端，同步
if [ -d "${PROJECT_DIR}/template/pc/dist" ]; then
    cp -rf "${PROJECT_DIR}/template/pc/dist/"* "${WEB_ROOT}/home/" 2>/dev/null
    log "[2/4] PC synced"
fi
# 如果有新构建的Admin前端，同步
if [ -d "${PROJECT_DIR}/template/admin/dist" ]; then
    cp -rf "${PROJECT_DIR}/template/admin/dist/"* "${WEB_ROOT}/admin/" 2>/dev/null
    log "[2/4] Admin synced"
fi
log "[2/4] Done"

# 3. 修复权限
log "[3/4] Fix permissions..."
chown -R nginx:nginx "${PROJECT_DIR}/crmeb/runtime" 2>/dev/null || true
chown -R nginx:nginx "${PROJECT_DIR}/crmeb/public" 2>/dev/null || true
chmod -R 755 "${PROJECT_DIR}/crmeb/runtime" 2>/dev/null || true
log "[3/4] Done"

# 4. 重载服务
log "[4/4] Reload services..."
systemctl reload php-fpm 2>/dev/null || systemctl restart php-fpm 2>/dev/null || true
nginx -t 2>&1 | tee -a "$LOG_FILE" && systemctl reload nginx 2>/dev/null || true
log "[4/4] Done"

log "========== FAST DEPLOY DONE =========="