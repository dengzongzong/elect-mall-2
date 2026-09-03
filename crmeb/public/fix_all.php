<?php
/**
 * 全自动数据库修复脚本
 * 功能：自动扫描所有表，检测并修复双重编码乱码，重置管理员密码
 * 
 * 访问方式：http://你的域名/fix_all.php
 * 使用后请立即删除此文件！
 */

header('Content-Type: text/html; charset=utf-8');

// 加载ThinkPHP环境
$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    die("❌ 找不到 autoload.php，请确认路径正确");
}
require $autoload;

// 读取.env配置
$envFile = __DIR__ . '/../.env';
$config = parseEnv($envFile);

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>全自动数据库修复工具</title>";
echo "<style>
body{font-family:'Microsoft YaHei',sans-serif;max-width:900px;margin:30px auto;padding:20px;line-height:1.8}
h1{color:#2c3e50;border-bottom:3px solid #e74c3c;padding-bottom:10px}
.success{color:#090;padding:5px 10px;background:#e8f8e8;border-radius:4px;margin:2px 0}
.error{color:#c00;padding:5px 10px;background:#fde8e8;border-radius:4px;margin:2px 0}
.warn{color:#960;padding:5px 10px;background:#fff3e0;border-radius:4px;margin:2px 0}
.info{color:#036;padding:5px 10px;background:#e3f2fd;border-radius:4px;margin:2px 0}
table{border-collapse:collapse;width:100%;margin:10px 0}
table td, table th{border:1px solid #ddd;padding:6px 10px;text-align:left}
table th{background:#f5f5f5}
.summary{font-size:18px;padding:15px;background:#e8f8e8;border-radius:8px;margin:15px 0}
</style></head><body>";
echo "<h1>🔧 CRMEB 全自动数据库修复工具</h1>";

try {
    # ---- 连接数据库 ----
    echo "<h2>📡 数据库连接</h2>";
    $dsn = "mysql:host={$config['hostname']};port={$config['hostport']};dbname={$config['database']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4, character_set_client = utf8mb4, character_set_connection = utf8mb4, character_set_results = utf8mb4",
    ]);
    echo "<div class='success'>✅ 数据库连接成功: {$config['database']}</div>\n";

    # ---- 第一步：全面扫描所有表，修复双重编码乱码 ----
    echo "<h2>📝 第一步：扫描所有表，修复中文乱码</h2>";
    
    // 获取所有表
    $stmt = $pdo->query("SHOW TABLES");
    $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<div class='info'>📋 数据库共 " . count($allTables) . " 个表</div>\n";
    
    $totalFixed = 0;
    $totalGarbled = 0;
    $fixedTables = [];
    $skippedTables = [];
    
    echo "<table><tr><th>#</th><th>表名</th><th>字段</th><th>修复行数</th><th>状态</th></tr>\n";
    $tableIndex = 0;
    
    foreach ($allTables as $tableName) {
        $tableIndex++;
        
        // 获取表的字段信息
        $colStmt = $pdo->query("SHOW COLUMNS FROM `{$tableName}`");
        $columns = $colStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 筛选出需要检查的文本字段（varchar, char, text, mediumtext, longtext）
        $textColumns = [];
        foreach ($columns as $col) {
            $type = strtolower($col['Type']);
            $field = $col['Field'];
            // 只处理文本类型字段，排除主键和时间字段
            if (preg_match('/^(varchar|char|text|mediumtext|longtext|tinytext)/', $type)) {
                if (!in_array($field, ['id', 'add_time', 'update_time', 'create_time', 'delete_time', 'status', 'is_del', 'is_show', 'is_hot', 'is_banner', 'is_postage', 'sort', 'visit', 'sales', 'stock', 'price', 'cost', 'ot_price', 'brokerage', 'brokerage_two', 'integral', 'ficti', 'give_integral', 'give_coupon', 'delivery_type', 'attr', 'spec_type', 'image', 'images', 'slider_image', 'video_link', 'image_input', 'url', 'wechat', 'phone', 'province', 'city', 'district', 'address', 'longitude', 'latitude', 'password', 'pwd', 'pay_password', 'token', 'api_token', 'openid', 'unionid', 'spread_openid', 'routine_openid', 'session_key', 'last_ip', 'client_ip', 'icon', 'avatar', 'headimgurl', 'user_type', 'login_type', 'account', 'pwd', 'level', 'ip', 'user_agent', 'email', 'color', 'upload_type', 'link_id', 'unique', 'bar_code', 'code', 'qrcode', 'postage', 'delivery_id', 'delivery_name', 'mark', 'mark_id', 'mark_type', 'path', 'file_path', 'attach', 'attach_type', 'module', 'type', 'extend', 'extend_one', 'extend_two', 'extend_three', 'extend_four', 'extend_five', 'fail_msg', 'error_msg', 'remark', 'admin_remark', 'mer_remark', 'remark_id', 'fail_reason', 'refund_reason', 'cancel_reason', 'reason', 'stop_reason', 'close_reason', 'invalid_reason', 'no_buy_reason', 'no_delivery_reason', 'no_pay_reason', 'no_refund_reason', 'no_write_reason', 'no_writeoff_reason', 'no_verify_reason', 'no_comment_reason', 'no_reply_reason', 'no_agree_reason', 'no_confirm_reason', 'no_sign_reason', 'no_check_reason', 'no_audit_reason', 'no_approve_reason', 'no_apply_reason', 'no_cancel_reason', 'no_close_reason', 'no_invalid_reason'])) {
                    $textColumns[] = $field;
                }
            }
        }
        
        if (empty($textColumns)) {
            continue; // 跳过没有文本字段的表
        }
        
        // 检查每个文本字段是否包含乱码
        $tableFixed = false;
        $garbledCount = 0;
        
        foreach ($textColumns as $field) {
            try {
                // 检查前5行数据是否包含乱码特征
                $checkStmt = $pdo->query("SELECT `{$field}` FROM `{$tableName}` WHERE `{$field}` IS NOT NULL AND `{$field}` != '' LIMIT 5");
                $rows = $checkStmt->fetchAll(PDO::FETCH_COLUMN);
                
                $hasGarbled = false;
                foreach ($rows as $val) {
                    // 检查是否包含双重编码的特征字符
                    if (strpos($val, 'å') !== false || strpos($val, 'æ') !== false || 
                        strpos($val, 'è') !== false || strpos($val, 'é') !== false ||
                        strpos($val, 'ç') !== false || strpos($val, 'ï') !== false) {
                        $hasGarbled = true;
                        break;
                    }
                }
                
                if ($hasGarbled) {
                    // 执行双重编码修复
                    $fixSql = "UPDATE `{$tableName}` SET `{$field}` = CONVERT(BINARY CONVERT(CAST(`{$field}` AS CHAR) USING latin1) USING utf8mb4) WHERE `{$field}` IS NOT NULL AND `{$field}` != ''";
                    $count = $pdo->exec($fixSql);
                    if ($count > 0) {
                        $garbledCount += $count;
                        $tableFixed = true;
                    }
                }
            } catch (Exception $e) {
                // 跳过出错字段
            }
        }
        
        if ($tableFixed) {
            $totalFixed += $garbledCount;
            $totalGarbled++;
            $fixedTables[] = $tableName;
            echo "<tr><td>{$tableIndex}</td><td><strong>{$tableName}</strong></td><td>" . implode(', ', $textColumns) . "</td><td>{$garbledCount}</td><td class='success'>✅ 已修复</td></tr>\n";
        } else {
            $skippedTables[] = $tableName;
            // 不在表格中显示跳过的表，避免太多行
        }
    }
    
    echo "</table>\n";
    
    echo "<div class='summary'>";
    echo "📊 扫描完成：<br>";
    echo "✅ 修复了 <strong>{$totalGarbled}</strong> 个表中 <strong>{$totalFixed}</strong> 条乱码数据<br>";
    if (!empty($fixedTables)) {
        echo "📋 修复的表：<strong>" . implode('</strong>, <strong>', $fixedTables) . "</strong><br>";
    }
    echo "</div>\n";
    
    echo "<div class='success'>✅ 数据库中文乱码修复完成！</div>\n";
    
    # ---- 第二步：检查并重置管理员密码 ----
    echo "<h2>🔐 第二步：管理后台账号检查</h2>";
    
    $stmt = $pdo->query("SELECT id, account, real_name, status, is_del, pwd, level FROM `{$config['prefix']}system_admin` LIMIT 10");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($admins)) {
        echo "<div class='error'>❌ 未找到任何管理员账号！</div>\n";
    } else {
        echo "<div class='info'>📋 找到 " . count($admins) . " 个管理员账号：</div>\n";
        echo "<table><tr><th>ID</th><th>账号</th><th>姓名</th><th>状态</th><th>是否删除</th></tr>";
        
        foreach ($admins as $admin) {
            $statusLabel = $admin['status'] ? '<span class="success">正常</span>' : '<span class="error">禁用</span>';
            $delLabel = $admin['is_del'] ? '<span class="error">已删除</span>' : '<span class="success">正常</span>';
            echo "<tr><td>{$admin['id']}</td><td>{$admin['account']}</td><td>{$admin['real_name']}</td><td>$statusLabel</td><td>$delLabel</td></tr>";
        }
        echo "</table>";
        
        // 重置第一个管理员密码
        if (!empty($admins)) {
            $admin = $admins[0];
            $newPwd = 'admin123';
            $hashedPwd = md5($newPwd);
            
            $updateStmt = $pdo->prepare("UPDATE `{$config['prefix']}system_admin` SET pwd = ?, status = 1, is_del = 0 WHERE id = ?");
            $updateStmt->execute([$hashedPwd, $admin['id']]);
            
            echo "<div class='success'>✅ 管理员账号已重置：</div>\n";
            echo "<div class='info'>📌 账号: <strong>{$admin['account']}</strong></div>\n";
            echo "<div class='info'>📌 密码: <strong>{$newPwd}</strong></div>\n";
        }
    }
    
    echo "<hr>";
    echo "<div class='summary'>🎉 所有修复操作已完成！</div>\n";
    echo "<div class='warn'>⚠️ 请立即删除此文件（fix_all.php），防止被他人利用！</div>\n";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ 错误: " . htmlspecialchars($e->getMessage()) . "</div>\n";
    echo "<div class='error'>位置: " . $e->getFile() . ":" . $e->getLine() . "</div>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
}

echo "</body></html>";

/**
 * 解析.env文件
 */
function parseEnv($file) {
    $defaults = [
        'hostname' => '127.0.0.1',
        'database' => 'crmeb31',
        'username' => 'root',
        'password' => 'root',
        'hostport' => '3306',
        'charset' => 'utf8',
        'prefix' => 'eb_',
    ];
    
    if (!file_exists($file)) return $defaults;
    
    $content = file_get_contents($file);
    $lines = preg_split('/\r\n|\r|\n/', $content);
    $section = '';
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        
        if (strpos($line, '[') === 0 && strpos($line, ']') !== false) {
            $section = strtolower(trim($line, '[]'));
            continue;
        }
        
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = strtolower(trim($key));
            $value = trim($value);
            
            if ($section === 'database') {
                switch ($key) {
                    case 'hostname': $defaults['hostname'] = $value; break;
                    case 'database': $defaults['database'] = $value; break;
                    case 'username': $defaults['username'] = $value; break;
                    case 'password': $defaults['password'] = $value; break;
                    case 'hostport': $defaults['hostport'] = $value; break;
                    case 'charset': $defaults['charset'] = $value; break;
                    case 'prefix': $defaults['prefix'] = $value; break;
                }
            }
        }
    }
    
    return $defaults;
}
?>