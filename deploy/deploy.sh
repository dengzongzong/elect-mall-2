#!/bin/bash
# ============================================================
# 自动部署脚本
# 功能：拉取最新代码 → 安装依赖 → 构建前端 → 重启服务
# 调用方式：bash deploy/deploy.sh
# 支持通过 GitHub Webhook 自动触发
# ============================================================

set +e  # 不在错误时退出，手动处理错误

# ====== 配置 ======
PROJECT_DIR="/var/www/elect-mall"
LOG_FILE="${PROJECT_DIR}/deploy/deploy.log"
LOCK_FILE="${PROJECT_DIR}/deploy/deploy.lock"
WEB_ROOT="${PROJECT_DIR}/crmeb/public"

# ====== 日志函数 ======
log() {
    local time=$(date '+%Y-%m-%d %H:%M:%S')
    echo "[${time}] $1" | tee -a "$LOG_FILE"
}

# ====== 锁机制 ======
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

log "========== START DEPLOY =========="

# ====== 1. 拉取最新代码 ======
log "[1/6] Git pull..."
cd "$PROJECT_DIR"
git stash 2>/dev/null
git fetch origin main 2>&1 | tee -a "$LOG_FILE"
git reset --hard origin/main 2>&1 | tee -a "$LOG_FILE"
log "[1/6] Done"

# ====== 2. 安装 PHP 依赖 ======
log "[2/6] Composer install..."
if [ -f "${PROJECT_DIR}/crmeb/composer.json" ]; then
    cd "${PROJECT_DIR}/crmeb"
    composer install --no-dev --optimize-autoloader --ignore-platform-reqs 2>&1 | tee -a "$LOG_FILE"
    log "[2/6] Done"
fi

# ====== 3. 构建 PC 前端 ======
log "[3/6] Build PC (Nuxt)..."
if [ -d "${PROJECT_DIR}/template/pc" ]; then
    cd "${PROJECT_DIR}/template/pc"
    if [ ! -d node_modules ]; then
        npm install 2>&1 | tee -a "$LOG_FILE"
    fi
    npm run generate 2>&1 | tee -a "$LOG_FILE"
    log "[3/6] Done"
fi

# ====== 4. 构建管理后台前端 ======
log "[4/6] Build admin..."
if [ -d "${PROJECT_DIR}/template/admin" ]; then
    cd "${PROJECT_DIR}/template/admin"
    if [ ! -d node_modules ]; then
        npm install 2>&1 | tee -a "$LOG_FILE"
    fi
    npm run build 2>&1 | tee -a "$LOG_FILE"
    log "[4/6] Done"
fi

# ====== 5. 同步前端文件到 Web 目录 ======
log "[5/6] Sync frontend files..."
if [ -d "${PROJECT_DIR}/template/pc/dist" ]; then
    cp -rf "${PROJECT_DIR}/template/pc/dist/"* "${WEB_ROOT}/home/" 2>/dev/null
    log "[5/6] PC synced to ${WEB_ROOT}/home/"
fi
if [ -d "${PROJECT_DIR}/template/admin/dist" ]; then
    cp -rf "${PROJECT_DIR}/template/admin/dist/"* "${WEB_ROOT}/admin/" 2>/dev/null
    log "[5/6] Admin synced to ${WEB_ROOT}/admin/"
fi

# ====== 6. 修复权限并重启服务 ======
log "[6/6] Fix perms & restart services..."
chown -R nginx:nginx "${PROJECT_DIR}/crmeb/runtime" 2>/dev/null
chown -R nginx:nginx "${PROJECT_DIR}/crmeb/public" 2>/dev/null
chmod -R 755 "${PROJECT_DIR}/crmeb/runtime" 2>/dev/null

systemctl reload php-fpm 2>/dev/null || systemctl restart php-fpm 2>/dev/null
nginx -t 2>&1 && systemctl reload nginx
log "[6/6] Done"

log "========== DEPLOY COMPLETE =========="
