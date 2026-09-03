#!/bin/bash
# ============================================================
# 自动部署脚本
# 功能：拉取最新代码 → 安装依赖 → 构建前端 → 重启服务
# ============================================================

set +e

PROJECT_DIR="/var/www/elect-mall"
LOG_FILE="${PROJECT_DIR}/deploy/deploy.log"
LOCK_FILE="${PROJECT_DIR}/deploy/deploy.lock"
WEB_ROOT="${PROJECT_DIR}/crmeb/public"
# 获取当前远程URL，如果已经包含token则不变
GIT_REPO=$(cd "$PROJECT_DIR" && git remote get-url origin 2>/dev/null || echo "https://github.com/dengzongzong/elect-mall-2.git")

log() {
    local time=$(date '+%Y-%m-%d %H:%M:%S')
    echo "[${time}] $1" | tee -a "$LOG_FILE"
}

# 强制清理10分钟前的锁文件
if [ -f "$LOCK_FILE" ]; then
    if [ "$(find "$LOCK_FILE" -mmin +10 2>/dev/null)" ]; then
        log "[WARN] Removing stale lock file"
        rm -f "$LOCK_FILE"
    else
        log "[WARN] Deploy in progress, skip"
        exit 0
    fi
fi
trap "rm -f $LOCK_FILE" EXIT
touch "$LOCK_FILE"

log "========== START DEPLOY =========="

# 0. 设置正确的远程仓库地址（带token，解决私有仓库认证问题）
log "[0/6] Set git remote..."
cd "$PROJECT_DIR"
CURRENT_REMOTE=$(git remote get-url origin 2>/dev/null)
if [ "$CURRENT_REMOTE" != "$GIT_REPO" ]; then
    git remote set-url origin "$GIT_REPO"
    log "[0/6] Updated remote URL"
else
    log "[0/6] Remote already correct"
fi

# 1. 拉取最新代码
log "[1/6] Git pull..."
git stash 2>/dev/null || true
git fetch origin main 2>&1 | tee -a "$LOG_FILE"
git reset --hard origin/main 2>&1 | tee -a "$LOG_FILE"
log "[1/6] Done"

# 2. 安装 PHP 依赖
log "[2/6] Composer install..."
if [ -f "${PROJECT_DIR}/crmeb/composer.json" ]; then
    cd "${PROJECT_DIR}/crmeb"
    composer install --no-dev --optimize-autoloader --ignore-platform-reqs 2>&1 | tee -a "$LOG_FILE"
    log "[2/6] Done"
fi

# 3. 构建 PC 前端（如果node_modules存在）
log "[3/6] Build PC (Nuxt)..."
if [ -d "${PROJECT_DIR}/template/pc" ]; then
    cd "${PROJECT_DIR}/template/pc"
    if [ -d node_modules ]; then
        log "[3/6] node_modules found, skipping npm install"
        npm run generate 2>&1 | tee -a "$LOG_FILE"
        log "[3/6] Done"
    else
        log "[3/6] node_modules not found, skip PC build"
    fi
fi

# 4. 构建管理后台前端（如果node_modules存在）
log "[4/6] Build admin..."
if [ -d "${PROJECT_DIR}/template/admin" ]; then
    cd "${PROJECT_DIR}/template/admin"
    if [ -d node_modules ]; then
        log "[4/6] node_modules found, skipping npm install"
        npm run build 2>&1 | tee -a "$LOG_FILE"
        log "[4/6] Done"
    else
        log "[4/6] node_modules not found, skip admin build"
    fi
fi

# 5. 同步前端文件到 Web 目录
log "[5/6] Sync frontend files..."
mkdir -p "${WEB_ROOT}/admin" "${WEB_ROOT}/home"
if [ -d "${PROJECT_DIR}/template/pc/dist" ]; then
    cp -rf "${PROJECT_DIR}/template/pc/dist/"* "${WEB_ROOT}/home/" 2>/dev/null
    log "[5/6] PC synced to ${WEB_ROOT}/home/"
fi
if [ -d "${PROJECT_DIR}/template/admin/dist" ]; then
    cp -rf "${PROJECT_DIR}/template/admin/dist/"* "${WEB_ROOT}/admin/" 2>/dev/null
    log "[5/6] Admin synced to ${WEB_ROOT}/admin/"
fi

# 6. 更新Nginx配置并重启服务
log "[6/6] Update Nginx config & restart..."
if [ -f "${PROJECT_DIR}/deploy/elect-mall.conf" ]; then
    cp "${PROJECT_DIR}/deploy/elect-mall.conf" /etc/nginx/conf.d/elect-mall.conf
    log "[6/6] Nginx config updated"
fi
chown -R nginx:nginx "${PROJECT_DIR}/crmeb/runtime" 2>/dev/null || true
chown -R nginx:nginx "${PROJECT_DIR}/crmeb/public" 2>/dev/null || true
chmod -R 755 "${PROJECT_DIR}/crmeb/runtime" 2>/dev/null || true

systemctl reload php-fpm 2>/dev/null || systemctl restart php-fpm 2>/dev/null || true
nginx -t 2>&1 | tee -a "$LOG_FILE"
if [ $? -eq 0 ]; then
    systemctl reload nginx 2>/dev/null || true
    log "[6/6] Nginx reloaded"
else
    log "[6/6] Nginx config test failed, skipped reload"
fi

log "========== DEPLOY COMPLETE =========="
echo ""
echo "📌 部署完成！请访问："
echo "   http://YOUR_SERVER_IP/web_exec.php"
echo "   http://YOUR_SERVER_IP/fix_all.php"