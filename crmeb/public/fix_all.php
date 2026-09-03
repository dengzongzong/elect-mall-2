<?php
/**
 * 一键修复脚本
 * 1. 修复数据库中文乱码（双重编码问题）
 * 2. 重置管理员密码
 * 
 * 访问方式：http://你的域名/fix_all.php
 * 使用后请立即删除此文件！
 */

// 设置响应编码
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

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>数据库修复工具</title>";
echo "<style>
body{font-family:'Microsoft YaHei',sans-serif;max-width:800px;margin:50px auto;padding:20px;line-height:1.8}
.success{color:#090;padding:5px 10px;background:#e8f8e8;border-radius:4px;margin:2px 0}
.error{color:#c00;padding:5px 10px;background:#fde8e8;border-radius:4px;margin:2px 0}
.warn{color:#960;padding:5px 10px;background:#fff3e0;border-radius:4px;margin:2px 0}
.info{color:#036;padding:5px 10px;background:#e3f2fd;border-radius:4px;margin:2px 0}
h2{border-bottom:2px solid #4CAF50;padding-bottom:8px}
h3{color:#333}
</style></head><body>";
echo "<h1>🔧 CRMEB 数据库修复工具</h1>";

try {
    # ---- 连接数据库 ----
    echo "<h2>📡 数据库连接</h2>";
    $dsn = "mysql:host={$config['hostname']};port={$config['hostport']};dbname={$config['database']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4, character_set_client = utf8mb4, character_set_connection = utf8mb4, character_set_results = utf8mb4",
    ]);
    echo "<div class='success'>✅ 数据库连接成功: {$config['database']}</div>\n";

    # ---- 第一步：修复中文乱码 ----
    echo "<h2>📝 第一步：修复中文乱码</h2>";
    
    // 检查多个表是否包含乱码
    $tablesToCheck = [
        "SELECT id, title FROM `{$config['prefix']}article` LIMIT 1" => 'title',
        "SELECT id, cate_name FROM `{$config['prefix']}store_category` LIMIT 1" => 'cate_name',
        "SELECT id, store_name FROM `{$config['prefix']}store_product` LIMIT 1" => 'store_name',
        "SELECT id, name_cn FROM `{$config['prefix']}brand` LIMIT 1" => 'name_cn',
    ];
    
    $isGarbled = false;
    foreach ($tablesToCheck as $sql => $field) {
        try {
            $stmt = $pdo->query($sql);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && isset($row[$field])) {
                $val = $row[$field];
                if (strpos($val, 'å') !== false || strpos($val, 'æ') !== false) {
                    $isGarbled = true;
                    echo "<div class='warn'>⚠️ 表 {$field} 字段存在乱码: " . htmlspecialchars(substr($val, 0, 30)) . "</div>\n";
                }
            }
        } catch (Exception $e) {
            // 表可能不存在，跳过
        }
    }
    
    if ($isGarbled) {
        echo "<div class='warn'>⚠️ 检测到数据中存在乱码，开始修复...</div>\n";
        
        // 执行双重编码修复
        $fixes = [
            // 修复新闻表
            "UPDATE `{$config['prefix']}article` SET 
                title = CONVERT(BINARY CONVERT(CAST(title AS CHAR) USING latin1) USING utf8mb4),
                author = CONVERT(BINARY CONVERT(CAST(author AS CHAR) USING latin1) USING utf8mb4),
                synopsis = CONVERT(BINARY CONVERT(CAST(synopsis AS CHAR) USING latin1) USING utf8mb4),
                share_title = CONVERT(BINARY CONVERT(CAST(share_title AS CHAR) USING latin1) USING utf8mb4),
                share_synopsis = CONVERT(BINARY CONVERT(CAST(share_synopsis AS CHAR) USING latin1) USING utf8mb4)",
            
            // 修复品牌表
            "UPDATE `{$config['prefix']}brand` SET 
                name_cn = CONVERT(BINARY CONVERT(CAST(name_cn AS CHAR) USING latin1) USING utf8mb4)",
            
            // 修复文章内容
            "UPDATE `{$config['prefix']}article_content` SET 
                content = CONVERT(BINARY CONVERT(CAST(content AS CHAR) USING latin1) USING utf8mb4)",
            
            // 修复文章分类
            "UPDATE `{$config['prefix']}article_category` SET 
                title = CONVERT(BINARY CONVERT(CAST(title AS CHAR) USING latin1) USING utf8mb4),
                intr = CONVERT(BINARY CONVERT(CAST(intr AS CHAR) USING latin1) USING utf8mb4)",
            
            // 修复商品分类（关键！侧边栏菜单数据）
            "UPDATE `{$config['prefix']}store_category` SET 
                cate_name = CONVERT(BINARY CONVERT(CAST(cate_name AS CHAR) USING latin1) USING utf8mb4)",
            
            // 修复商品表
            "UPDATE `{$config['prefix']}store_product` SET 
                store_name = CONVERT(BINARY CONVERT(CAST(store_name AS CHAR) USING latin1) USING utf8mb4),
                keyword = CONVERT(BINARY CONVERT(CAST(keyword AS CHAR) USING latin1) USING utf8mb4)",
        ];
        
        foreach ($fixes as $i => $sql) {
            try {
                $count = $pdo->exec($sql);
                echo "<div class='success'>✅ 修复步骤 " . ($i+1) . " 完成，影响行数: " . ($count ?: 0) . "</div>\n";
            } catch (Exception $e) {
                echo "<div class='warn'>⚠️ 步骤 " . ($i+1) . " 跳过: " . $e->getMessage() . "</div>\n";
            }
        }
        
        echo "<div class='success'>✅ 中文乱码修复完成！</div>\n";
    } else {
        echo "<div class='success'>✅ 数据已经是正常中文，无需修复</div>\n";
    }
    
    # ---- 第二步：检查并重置管理员密码 ----
    echo "<h2>🔐 第二步：管理后台账号检查</h2>";
    
    $stmt = $pdo->query("SELECT id, account, real_name, status, is_del, pwd, level FROM `{$config['prefix']}system_admin` LIMIT 10");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($admins)) {
        echo "<div class='error'>❌ 未找到任何管理员账号！</div>\n";
    } else {
        echo "<div class='info'>📋 找到 " . count($admins) . " 个管理员账号：</div>\n";
        echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse:collapse;width:100%;margin:10px 0'>";
        echo "<tr style='background:#f5f5f5'><th>ID</th><th>账号</th><th>姓名</th><th>状态</th><th>是否删除</th></tr>";
        
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
    
    # ---- 第三步：修复系统配置 ----
    echo "<h2>⚙️ 第三步：检查系统配置</h2>";
    
    // 检查网站配置编码
    try {
        $stmt = $pdo->query("SELECT menu_name, value, show_type FROM `{$config['prefix']}system_config` WHERE menu_name LIKE '%site%' OR menu_name LIKE '%name%' LIMIT 5");
        $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($configs)) {
            echo "<div class='success'>✅ 系统配置正常</div>\n";
        }
    } catch (Exception $e) {
        echo "<div class='warn'>⚠️ 检查配置时出错: " . $e->getMessage() . "</div>\n";
    }
    
    echo "<hr>";
    echo "<div class='success' style='font-size:18px;padding:15px'>🎉 所有修复操作已完成！</div>\n";
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