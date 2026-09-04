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
# 从 .git_token 文件读取token（webhook.php 负责写入此文件）
# 如果文件不存在或为空，则使用公开地址
GIT_TOKEN_FILE="${PROJECT_DIR}/deploy/.git_token"
if [ -f "$GIT_TOKEN_FILE" ]; then
    GIT_TOKEN=$(cat "$GIT_TOKEN_FILE" 2>/dev/null | tr -d '\n\r')
fi
if [ -n "$GIT_TOKEN" ]; then
    GIT_REPO="https://dengzongzong:${GIT_TOKEN}@github.com/dengzongzong/elect-mall-2.git"
else
    GIT_REPO="https://github.com/dengzongzong/elect-mall-2.git"
fi

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

# 0. 修复.git目录权限（使用sudo，因为.git可能被root所有或设置了chattr +i）
log "[0/8] Fix .git permissions (via sudo)..."
# 关键修复：先移除不可变属性（chattr +i），否则chmod无效
sudo chattr -R -i "${PROJECT_DIR}/.git" 2>/dev/null || true
log "[0/8] Immutable flag removed (chattr -i)"
# 然后修复权限
sudo chmod -R 777 "${PROJECT_DIR}/.git" 2>/dev/null || true
sudo find "${PROJECT_DIR}/.git" -type d -exec chmod 777 {} \; 2>/dev/null || true
sudo find "${PROJECT_DIR}/.git" -type f -exec chmod 666 {} \; 2>/dev/null || true
log "[0/8] Done"

# 1. 设置正确的远程仓库地址（带token，解决私有仓库认证问题）
log "[1/8] Set git remote..."
cd "$PROJECT_DIR"
# 使用 git remote show 获取远程URL（兼容旧版git）
CURRENT_REMOTE=$(git remote show origin 2>/dev/null | grep "Fetch URL" | awk '{print $3}')
if [ "$CURRENT_REMOTE" != "$GIT_REPO" ]; then
    git remote set-url origin "$GIT_REPO"
    log "[1/8] Updated remote URL"
    else
    log "[1/8] Remote already correct"
fi

# 2. 拉取最新代码
log "[2/8] Git pull..."
git stash 2>/dev/null || true
git fetch origin main 2>&1 | tee -a "$LOG_FILE"
git reset --hard origin/main 2>&1 | tee -a "$LOG_FILE"
log "[2/8] Done"

# 3. 安装 PHP 依赖
log "[3/8] Composer install..."
if [ -f "${PROJECT_DIR}/crmeb/composer.json" ]; then
    cd "${PROJECT_DIR}/crmeb"
    composer install --no-dev --optimize-autoloader --ignore-platform-reqs 2>&1 | tee -a "$LOG_FILE"
    log "[3/8] Done"
fi

# 4. 构建 PC 前端（自动安装依赖）
log "[4/8] Build PC (Nuxt)..."
if [ -d "${PROJECT_DIR}/template/pc" ]; then
    cd "${PROJECT_DIR}/template/pc"
    if [ ! -d node_modules ]; then
        log "[4/8] Installing npm dependencies..."
        npm install --legacy-peer-deps 2>&1 | tee -a "$LOG_FILE"
        log "[4/8] npm install done"
    else
        log "[4/8] node_modules found, skipping npm install"
    fi
    npm run generate 2>&1 | tee -a "$LOG_FILE"
    log "[4/8] Build done"
fi

# 5. 构建管理后台前端（自动安装依赖）
log "[5/8] Build admin..."
if [ -d "${PROJECT_DIR}/template/admin" ]; then
    cd "${PROJECT_DIR}/template/admin"
    if [ ! -d node_modules ]; then
        log "[5/8] Installing npm dependencies..."
        npm install --legacy-peer-deps 2>&1 | tee -a "$LOG_FILE"
        log "[5/8] npm install done"
    else
        log "[5/8] node_modules found, skipping npm install"
    fi
    npm run build 2>&1 | tee -a "$LOG_FILE"
    log "[5/8] Build done"
fi

# 6. 同步前端文件到 Web 目录
log "[6/8] Sync frontend files..."
mkdir -p "${WEB_ROOT}/admin" "${WEB_ROOT}/home"
if [ -d "${PROJECT_DIR}/template/pc/dist" ]; then
    cp -rf "${PROJECT_DIR}/template/pc/dist/"* "${WEB_ROOT}/home/" 2>/dev/null
    log "[6/8] PC synced to ${WEB_ROOT}/home/"
fi
if [ -d "${PROJECT_DIR}/template/admin/dist" ]; then
    cp -rf "${PROJECT_DIR}/template/admin/dist/"* "${WEB_ROOT}/admin/" 2>/dev/null
    log "[6/8] Admin synced to ${WEB_ROOT}/admin/"
fi
# 同步crmeb/public目录下的PHP工具文件（fix_all.php, web_exec.php, check_categories.php等）
if [ -d "${PROJECT_DIR}/crmeb/public" ]; then
    # 所有php文件已经在${PROJECT_DIR}/crmeb/public，不需要复制
    # 但是需要确保权限正确
    sudo chown -R nginx:nginx "${PROJECT_DIR}/crmeb/public" 2>/dev/null || true
    sudo chmod 644 "${PROJECT_DIR}/crmeb/public"/*.php 2>/dev/null || true
    log "[6/8] crmeb/public PHP permissions fixed"
fi
# 也同步sql文件到项目根目录
if [ -f "${PROJECT_DIR}/import_categories.sql" ]; then
    cp "${PROJECT_DIR}/import_categories.sql" "${PROJECT_DIR}/" 2>/dev/null
fi

# 7. 更新Nginx配置并重启服务（使用sudo提权）
log "[7/8] Update Nginx config & restart..."
if [ -f "${PROJECT_DIR}/deploy/elect-mall.conf" ]; then
    sudo cp "${PROJECT_DIR}/deploy/elect-mall.conf" /etc/nginx/conf.d/elect-mall.conf 2>/dev/null
    if [ $? -eq 0 ]; then
        log "[7/8] Nginx config updated via sudo cp"
    else
        log "[7/8] WARN: sudo cp failed, try PHP file_put_contents..."
        # 使用PHP写入（不依赖sudo）
        php -r "
            \$src = '${PROJECT_DIR}/deploy/elect-mall.conf';
            \$dst = '/etc/nginx/conf.d/elect-mall.conf';
            \$content = file_get_contents(\$src);
            if (\$content !== false) {
                \$written = file_put_contents(\$dst, \$content);
                if (\$written !== false) {
                    echo 'PHP wrote ' . \$written . ' bytes to ' . \$dst;
                    exit(0);
                }
            }
            echo 'PHP write failed';
            exit(1);
        " 2>/dev/null && log "[7/8] Nginx config updated via PHP" || log "[7/8] WARN: All methods failed to write Nginx config"
    fi
fi
sudo chown -R nginx:nginx "${PROJECT_DIR}/crmeb/runtime" 2>/dev/null || true
sudo chown -R nginx:nginx "${PROJECT_DIR}/crmeb/public" 2>/dev/null || true
sudo chmod -R 755 "${PROJECT_DIR}/crmeb/runtime" 2>/dev/null || true

sudo systemctl reload php-fpm 2>/dev/null || sudo systemctl restart php-fpm 2>/dev/null || true
sudo nginx -t 2>&1 | tee -a "$LOG_FILE"
if [ $? -eq 0 ]; then
    sudo systemctl reload nginx 2>/dev/null || true
    log "[7/8] Nginx reloaded"
else
    log "[7/8] Nginx config test failed, skipped reload"
fi

# 8. 自动执行数据库升级脚本（如果有 import_categories.sql）
log "[8/8] Auto execute database upgrade SQL..."
PROJECT_ROOT="${PROJECT_DIR}"
SQL_FILE="${PROJECT_ROOT}/import_categories.sql"
if [ -f "${SQL_FILE}" ]; then
    # 读取数据库配置从 .env 文件
    DB_HOST=$(grep 'HOSTNAME' "${PROJECT_ROOT}/crmeb/.env" | cut -d'=' -f2 | xargs)
    DB_NAME=$(grep 'DATABASE' "${PROJECT_ROOT}/crmeb/.env" | cut -d'=' -f2 | xargs)
    DB_USER=$(grep 'USERNAME' "${PROJECT_ROOT}/crmeb/.env" | cut -d'=' -f2 | xargs)
    DB_PASS=$(grep 'PASSWORD' "${PROJECT_ROOT}/crmeb/.env" | cut -d'=' -f2 | xargs)
    DB_PORT=$(grep 'HOSTPORT' "${PROJECT_ROOT}/crmeb/.env" | cut -d'=' -f2 | xargs)
    
    # 使用默认值
    [ -z "$DB_HOST" ] && DB_HOST="127.0.0.1"
    [ -z "$DB_NAME" ] && DB_NAME="crmeb31"
    [ -z "$DB_USER" ] && DB_USER="root"
    [ -z "$DB_PASS" ] && DB_PASS="root"
    [ -z "$DB_PORT" ] && DB_PORT="3306"
    
    SQL_EXECUTED=false
    
    # 方法1：使用 mysql 命令行
    if command -v mysql >/dev/null 2>&1; then
        log "[8/8] Method 1: Using mysql command..."
        mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < "${SQL_FILE}" 2>&1 | tee -a "$LOG_FILE"
        if [ ${PIPESTATUS[0]} -eq 0 ]; then
            log "[8/8] ✓ SQL executed successfully via mysql command"
            SQL_EXECUTED=true
        else
            log "[8/8] ⚠ mysql command failed, trying PHP fallback..."
        fi
    fi
    
    # 方法2：使用 PHP 执行 SQL（兜底方案）
    if [ "$SQL_EXECUTED" = false ]; then
        log "[8/8] Method 2: Using PHP PDO..."
        php -r '
            $sql = file_get_contents("'"${SQL_FILE}"'");
            if ($sql === false) { echo "Cannot read SQL file\n"; exit(1); }
            
            // 解析SQL，按分号分割语句
            $statements = explode(";", $sql);
            
            $host = "'"${DB_HOST}"'";
            $db   = "'"${DB_NAME}"'";
            $user = "'"${DB_USER}"'";
            $pass = "'"${DB_PASS}"'";
            $port = "'"${DB_PORT}"'";
            
            try {
                $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                $count = 0;
                foreach ($statements as $stmt) {
                    $stmt = trim($stmt);
                    if (empty($stmt)) continue;
                    // 跳过 SELECT 语句（只执行 DML/DDL/SET）
                    if (preg_match("/^(SELECT)\s/i", $stmt)) continue;
                    $pdo->exec($stmt);
                    $count++;
                    echo "  [OK] Statement #$count\n";
                }
                
                echo "✓ Total $count statements executed successfully\n";
            } catch (Exception $e) {
                echo "✗ Error: " . $e->getMessage() . "\n";
                exit(1);
            }
        ' 2>&1 | tee -a "$LOG_FILE"
        
        if [ ${PIPESTATUS[0]} -eq 0 ]; then
            log "[8/8] ✓ SQL executed successfully via PHP"
            SQL_EXECUTED=true
        else
            log "[8/8] ✗ SQL execution failed (both methods)"
        fi
    fi
    
    if [ "$SQL_EXECUTED" = true ]; then
        # SQL执行成功后，备份并删除SQL文件（避免重复执行）
        mv "${SQL_FILE}" "${PROJECT_ROOT}/import_categories.sql.executed.$(date +%Y%m%d%H%M%S)" 2>/dev/null
        log "[8/8] SQL file moved to backup"
    fi
else
    log "[8/8] No import_categories.sql found, skip"
fi

log "========== DEPLOY COMPLETE =========="
echo ""
echo "📌 部署完成！请访问："
echo "   http://YOUR_SERVER_IP/web_exec.php"
echo "   http://YOUR_SERVER_IP/fix_all.php"