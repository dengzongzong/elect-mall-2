#!/bin/bash
# ============================================================
# 自动部署脚本
# 功能：拉取最新代码 → 安装依赖 → 构建前端 → 重启服务
# 调用方式：bash deploy/deploy.sh
# 支持通过 GitHub Webhook 自动触发
# ============================================================

set +e  # 不在错误时退出，手动处理错误

# ====== 配置 ======
# 项目根目录
PROJECT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
# 日志文件
LOG_FILE="${PROJECT_DIR}/deploy/deploy.log"
# 锁文件（防止并发部署）
LOCK_FILE="${PROJECT_DIR}/deploy/deploy.lock"

# ====== 日志函数 ======
log() {
    local time=$(date '+%Y-%m-%d %H:%M:%S')
    echo "[${time}] $1" | tee -a "$LOG_FILE"
}

# ====== 锁机制 ======
if [ -f "$LOCK_FILE" ]; then
    # 检查锁文件是否超过 10 分钟（可能是上次部署卡住了）
    if [ "$(find "$LOCK_FILE" -mmin +10 2>/dev/null)" ]; then
        log "[WARN] 锁文件超过 10 分钟，强制删除"
        rm -f "$LOCK_FILE"
    else
        log "[WARN] 部署正在执行中，跳过本次请求"
        exit 0
    fi
fi
trap "rm -f $LOCK_FILE" EXIT
touch "$LOCK_FILE"

log "=========================================="
log "开始部署 - 项目目录: ${PROJECT_DIR}"

# ====== 1. 拉取最新代码 ======
log "[1/6] 拉取最新代码..."
cd "$PROJECT_DIR"

# 检查是否有未提交的本地修改
if [ -n "$(git status --porcelain)" ]; then
    log "[WARN] 检测到本地修改，暂存中..."
    git stash
fi

git fetch origin main 2>&1 | tee -a "$LOG_FILE"
git reset --hard origin/main 2>&1 | tee -a "$LOG_FILE"
log "[1/6] ✓ 代码已更新到最新版本"

# ====== 2. 安装/更新 PHP 依赖 ======
log "[2/6] 安装 PHP 依赖..."
if [ -f "${PROJECT_DIR}/crmeb/composer.json" ]; then
    cd "${PROJECT_DIR}/crmeb"
    # 使用 --ignore-platform-reqs 避免本地 PHP 版本不匹配导致的安装失败
    composer install --no-dev --optimize-autoloader --ignore-platform-reqs 2>&1 | tee -a "$LOG_FILE"
    log "[2/6] ✓ PHP 依赖安装完成"
else
    log "[2/6] ✓ 无需安装 PHP 依赖"
fi

# ====== 3. 构建 PC 前端 ======
log "[3/6] 构建 PC 前端（Nuxt.js）..."
if [ -d "${PROJECT_DIR}/template/pc" ]; then
    cd "${PROJECT_DIR}/template/pc"

    # 安装依赖（仅在 node_modules 不存在时安装）
    if [ ! -d "node_modules" ]; then
        npm install 2>&1 | tee -a "$LOG_FILE"
    fi

    # 构建
    npm run generate 2>&1 | tee -a "$LOG_FILE"
    if [ $? -eq 0 ]; then
        log "[3/6] ✓ PC 前端构建完成"
    else
        log "[3/6] ⚠ PC 前端构建失败，继续执行后续步骤"
    fi
else
    log "[3/6] ✓ 跳过 PC 前端构建"
fi

# ====== 4. 构建管理后台前端 ======
log "[4/6] 构建管理后台前端..."
if [ -d "${PROJECT_DIR}/template/admin" ]; then
    cd "${PROJECT_DIR}/template/admin"

    if [ ! -d "node_modules" ]; then
        npm install 2>&1 | tee -a "$LOG_FILE"
    fi

    npm run build 2>&1 | tee -a "$LOG_FILE"
    if [ $? -eq 0 ]; then
        log "[4/6] ✓ 管理后台前端构建完成"
    else
        log "[4/6] ⚠ 管理后台前端构建失败，继续执行后续步骤"
    fi
else
    log "[4/6] ✓ 跳过管理后台前端构建"
fi

# ====== 5. 复制前端文件到 Web 目录 ======
log "[5/6] 同步前端文件..."
# 找到 Nginx/Apache 的 web 根目录
# 根据实际情况修改下面的路径
WEB_ROOT=""
if [ -d "/var/www/html" ]; then
    WEB_ROOT="/var/www/html"
elif [ -d "/usr/share/nginx/html" ]; then
    WEB_ROOT="/usr/share/nginx/html"
fi

if [ -n "$WEB_ROOT" ]; then
    if [ -d "${PROJECT_DIR}/template/pc/dist" ]; then
        cp -rf "${PROJECT_DIR}/template/pc/dist/"* "${WEB_ROOT}/home/" 2>/dev/null || true
        log "[5/6] ✓ 前端文件已同步到 ${WEB_ROOT}/home/"
    fi
    if [ -d "${PROJECT_DIR}/template/admin/dist" ]; then
        cp -rf "${PROJECT_DIR}/template/admin/dist/"* "${WEB_ROOT}/admin/" 2>/dev/null || true
        log "[5/6] ✓ 后台文件已同步到 ${WEB_ROOT}/admin/"
    fi
fi

# ====== 6. 重启服务 ======
log "[6/6] 重启服务..."
# 重启 PHP-FPM
if command -v systemctl &> /dev/null; then
    if systemctl is-active --quiet php*-fpm 2>/dev/null; then
        systemctl restart php*-fpm 2>&1 | tee -a "$LOG_FILE"
        log "[6/6] ✓ PHP-FPM 已重启"
    fi
    # 重启 Nginx
    if systemctl is-active --quiet nginx 2>/dev/null; then
        nginx -t 2>&1 | tee -a "$LOG_FILE"
        systemctl reload nginx 2>&1 | tee -a "$LOG_FILE"
        log "[6/6] ✓ Nginx 已重载配置"
    fi
    # 重启 Apache
    if systemctl is-active --quiet apache2 2>/dev/null; then
        systemctl reload apache2 2>&1 | tee -a "$LOG_FILE"
        log "[6/6] ✓ Apache 已重载配置"
    fi
elif command -v service &> /dev/null; then
    service php*-fpm reload 2>/dev/null || true
    service nginx reload 2>/dev/null || true
    service apache2 reload 2>/dev/null || true
fi

log "=========================================="
log "✓ 部署完成！"
log "=========================================="