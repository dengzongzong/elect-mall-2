<?php
/**
 * 数据库中文乱码修复脚本
 * 修复问题：双重编码（UTF-8被误作Latin-1存入数据库）
 * 
 * 使用方法：
 * 1. 将此文件上传到服务器 /var/www/elect-mall/database/fix_encoding.php
 * 2. 在浏览器访问 http://你的域名/database/fix_encoding.php
 * 3. 或者在命令行执行 php /var/www/elect-mall/database/fix_encoding.php
 */

// 加载ThinkPHP环境
require __DIR__ . '/../crmeb/vendor/autoload.php';

// 读取配置
$envFile = __DIR__ . '/../crmeb/.env';
$config = [
    'type' => 'mysql',
    'hostname' => '127.0.0.1',
    'database' => 'crmeb31',
    'username' => 'root',
    'password' => 'root',
    'hostport' => '3306',
    'charset' => 'utf8mb4',
    'prefix' => 'eb_',
];

if (file_exists($envFile)) {
    $content = file_get_contents($envFile);
    if (preg_match('/DATABASE\s+=\s+(.*)/', $content, $m)) {
        // 解析.env格式
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                switch ($key) {
                    case 'TYPE': $config['type'] = $value; break;
                    case 'HOSTNAME': $config['hostname'] = $value; break;
                    case 'DATABASE': $config['database'] = $value; break;
                    case 'USERNAME': $config['username'] = $value; break;
                    case 'PASSWORD': $config['password'] = $value; break;
                    case 'HOSTPORT': $config['hostport'] = $value; break;
                    case 'CHARSET': $config['charset'] = $value; break;
                    case 'PREFIX': $config['prefix'] = $value; break;
                }
            }
        }
    }
}

echo "====================================================\n";
echo "  CRMEB 数据库中文乱码修复工具\n";
echo "  修复双重编码问题\n";
echo "====================================================\n\n";

echo "数据库配置:\n";
echo "  Host: {$config['hostname']}:{$config['hostport']}\n";
echo "  Database: {$config['database']}\n";
echo "  User: {$config['username']}\n";
echo "\n开始修复...\n\n";

try {
    // 创建PDO连接
    $dsn = "{$config['type']}:host={$config['hostname']};port={$config['hostport']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
    ]);
    
    // 选择数据库
    $pdo->exec("USE `{$config['database']}`");
    echo "✓ 连接数据库成功\n\n";
    
    // 需要修复的表和字段
    $tables = [
        'article' => ['title', 'author', 'synopsis', 'share_title', 'share_synopsis'],
        'article_category' => ['title', 'intr'],
        'article_content' => ['content'],
        'brand' => ['name_cn'],
        'store_product' => ['store_name', 'keyword', 'unit_name', 'description', 'share_title', 'share_description', 'attr_name'],
        'article_category' => ['title', 'intr'],
        'system_group' => ['name', 'info'],
        'partner_apply' => ['company_name', 'brand', 'category', 'contact_name'],
    ];
    
    $totalFixed = 0;
    
    foreach ($tables as $table => $fields) {
        $tableName = $config['prefix'] . $table;
        
        // 检查表是否存在
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$tableName]);
        if (!$stmt->rowCount()) {
            echo "⚠  表 {$tableName} 不存在，跳过\n";
            continue;
        }
        
        echo "🔍 正在处理表 {$tableName}...\n";
        
        foreach ($fields as $field) {
            // 检查该列是否存在
            try {
                $stmt = $pdo->query("DESCRIBE {$tableName} {$field}");
                if (!$stmt->rowCount()) {
                    echo "  ⚠  列 {$field} 不存在，跳过\n";
                    continue;
                }
            } catch (Exception $e) {
                echo "  ⚠  列 {$field} 不存在，跳过\n";
                continue;
            }
            
            // 查询所有记录中需要修复的字段
            // 只修复那些看起来像Latin-1编码的UTF-8数据
            $stmt = $pdo->query("SELECT id, {$field} FROM {$tableName} WHERE {$field} IS NOT NULL AND {$field} != ''");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $fixedCount = 0;
            foreach ($rows as $row) {
                $id = $row['id'];
                $original = $row[$field];
                
                // 判断是否需要修复：如果包含Latin-1编码的UTF-8特征（形如æåäè这种字符）
                if (needsFix($original)) {
                    $fixed = fixEncoding($original);
                    if ($fixed !== $original) {
                        // 更新数据库
                        $updateStmt = $pdo->prepare("UPDATE {$tableName} SET {$field} = ? WHERE id = ?");
                        $updateStmt->execute([$fixed, $id]);
                        $fixedCount++;
                        $totalFixed++;
                    }
                }
            }
            
            if ($fixedCount > 0) {
                echo "  ✓ {$field}: 修复了 {$fixedCount} 条记录\n";
            } else {
                echo "  ✓ {$field}: 无需修复\n";
            }
        }
        echo "\n";
    }
    
    echo "====================================================\n";
    echo "✓ 修复完成！共修复了 {$totalFixed} 处乱码\n";
    echo "====================================================\n";
    echo "\n";
    echo "请刷新页面查看中文是否已经正常显示。\n";
    
} catch (Exception $e) {
    echo "✗ 错误: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

/**
 * 判断字符串是否需要修复
 * 检测是否有Latin-1编码UTF-8的特征（乱码特征）
 */
function needsFix($str) {
    if (empty($str)) return false;
    
    // 如果已经是合法的UTF-8，不包含奇怪的Latin-1字符，说明没问题
    if (!mb_check_encoding($str, 'UTF-8')) {
        return true;
    }
    
    // 检测是否包含大量 Latin-1 范围的字符（0xC3-0xC5 开头，说明是双编码）
    // 这是 UTF-8 被当作 Latin-1 再编码一遍的特征
    $count = preg_match_all('/[\xc0-\xc5]/', $str);
    // 如果字符串不长但是有多个这样的字节，说明需要修复
    $len = strlen($str);
    if ($len > 0 && $count / $len > 0.2) {
        return true;
    }
    
    // 检测常见乱码模式：æ 、è 、¥ 等连续出现
    if (strpos($str, 'æ') !== false && strpos($str, '¥') !== false) {
        return true;
    }
    
    return false;
}

/**
 * 修复双重编码
 * 原理：先按latin-1转回二进制，再按utf-8解读
 */
function fixEncoding($str) {
    if (empty($str)) return $str;
    
    // 方法：convert(binary convert(field using latin1) using utf8)
    // PHP版本实现
    $fixed = '';
    $len = strlen($str);
    for ($i = 0; $i < $len; $i++) {
        $ord = ord($str[$i]);
        // latin-1 每个字节就是一个字符，转成UTF-8字节
        $fixed .= chr($ord);
    }
    
    // 现在按UTF-8重新解读
    if (mb_check_encoding($fixed, 'UTF-8')) {
        return $fixed;
    }
    
    // 如果还是不对，试另一种方法
    return mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1');
}
?>