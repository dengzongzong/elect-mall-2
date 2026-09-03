<?php
/**
 * 编码诊断工具
 * 检查数据库、PHP、Nginx各环节的编码设置
 * 使用后请立即删除此文件！
 */
header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>编码诊断工具</title>";
echo "<style>
body{font-family:'Microsoft YaHei',sans-serif;max-width:960px;margin:30px auto;padding:20px;line-height:1.8}
h1{color:#2c3e50;border-bottom:3px solid #e74c3c;padding-bottom:10px}
h2{color:#2c3e50;border-left:4px solid #e74c3c;padding-left:10px;margin-top:30px}
.success{color:#090;padding:5px 10px;background:#e8f8e8;border-radius:4px;margin:2px 0}
.error{color:#c00;padding:5px 10px;background:#fde8e8;border-radius:4px;margin:2px 0}
.warn{color:#960;padding:5px 10px;background:#fff3e0;border-radius:4px;margin:2px 0}
.info{color:#036;padding:5px 10px;background:#e3f2fd;border-radius:4px;margin:2px 0}
table{border-collapse:collapse;width:100%;margin:10px 0;font-size:13px}
table td, table th{border:1px solid #ddd;padding:4px 8px;text-align:left;word-break:break-all}
table th{background:#f5f5f5;font-weight:bold}
pre{background:#f5f5f5;padding:10px;border-radius:4px;overflow-x:auto;font-size:12px}
</style></head><body>";
echo "<h1>🔍 编码诊断工具</h1>";

// 加载ThinkPHP环境
$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) die("❌ 找不到 autoload.php");
require $autoload;
$envFile = __DIR__ . '/../.env';
$config = parseEnv($envFile);

echo "<h2>1️⃣ 环境信息</h2>";
echo "<table>";
echo "<tr><td>PHP 版本</td><td>" . phpversion() . "</td></tr>";
echo "<tr><td>PHP default_charset</td><td>" . (ini_get('default_charset') ?: '未设置') . "</td></tr>";
echo "<tr><td>PHP mbstring.internal_encoding</td><td>" . (ini_get('mbstring.internal_encoding') ?: '未设置') . "</td></tr>";
echo "<tr><td>PHP mbstring.http_output</td><td>" . (ini_get('mbstring.http_output') ?: '未设置') . "</td></tr>";
echo "<tr><td>PHP output_handler</td><td>" . (ini_get('output_handler') ?: '未设置') . "</td></tr>";
echo "<tr><td>.env 数据库 charset</td><td><strong>" . $config['charset'] . "</strong></td></tr>";
echo "</table>";

echo "<h2>2️⃣ MySQL 数据库连接编码（ThinkPHP方式）</h2>";
try {
    $dsn = "mysql:host={$config['hostname']};port={$config['hostport']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    // 查询MySQL实际的字符集设置
    $charsetInfo = $pdo->query("SHOW VARIABLES LIKE 'character_set_%'")->fetchAll(PDO::FETCH_ASSOC);
    echo "<table><tr><th>变量名</th><th>当前值</th></tr>";
    foreach ($charsetInfo as $row) {
        $color = (strpos($row['Value'], 'utf8') !== false) ? 'success' : 'warn';
        echo "<tr><td>{$row['Variable_name']}</td><td class='{$color}'>{$row['Value']}</td></tr>";
    }
    echo "</table>";
    
    echo "<h2>3️⃣ 菜单数据实际编码诊断</h2>";
    $menuTable = $config['prefix'] . 'system_menus';
    
    // 检查表结构
    $stmt = $pdo->query("SHOW CREATE TABLE `{$menuTable}`");
    $createTable = $stmt->fetch(PDO::FETCH_ASSOC);
    if (preg_match('/DEFAULT CHARSET=(\w+)/', $createTable['Create Table'], $m)) {
        echo "<div class='info'>📋 菜单表字符集: <strong>{$m[1]}</strong></div>";
    }
    
    // 读取菜单数据并显示字节信息
    $stmt = $pdo->query("SELECT id, menu_name FROM `{$menuTable}` WHERE is_del = 0 ORDER BY pid, sort DESC LIMIT 20");
    $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table><tr><th>ID</th><th>menu_name</th><th>Hex字节</th><th>长度</th><th>是否UTF-8</th></tr>";
    foreach ($menus as $menu) {
        $name = $menu['menu_name'];
        $hex = bin2hex($name);
        $len = strlen($name);
        $isUtf8 = mb_check_encoding($name, 'UTF-8') ? '✅ 是' : '❌ 否';
        $isAscii = preg_match('/^[ -~]+$/', $name) ? '✅ 纯ASCII' : '⚠️ 含非ASCII';
        
        echo "<tr>";
        echo "<td>{$menu['id']}</td>";
        echo "<td>" . htmlspecialchars($name) . "</td>";
        echo "<td><code style='font-size:11px;word-break:break-all'>{$hex}</code></td>";
        echo "<td>{$len}</td>";
        echo "<td class='" . (strpos($isUtf8, '✅') !== false ? 'success' : 'error') . "'>{$isUtf8} / {$isAscii}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>4️⃣ 不同连接编码读取对比</h2>";
    
    // 用 utf8mb4 连接读取
    $pdoUtf8mb4 = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
    ]);
    
    // 用 latin1 连接读取
    $dsnLatin1 = "mysql:host={$config['hostname']};port={$config['hostport']};dbname={$config['database']};charset=latin1";
    $pdoLatin1 = new PDO($dsnLatin1, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    $menuNames = $pdo->query("SELECT menu_name FROM `{$menuTable}` WHERE is_del = 0 AND menu_name != '' LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<table><tr><th>#</th><th>utf8 连接读取</th><th>utf8mb4 连接读取</th><th>latin1 连接读取</th></tr>";
    foreach ($menuNames as $i => $name) {
        // 用utf8mb4连接读取
        $pdoUtf8mb4->prepare("SELECT menu_name FROM `{$menuTable}` WHERE id = ?");
        $stmt4 = $pdoUtf8mb4->prepare("SELECT menu_name FROM `{$menuTable}` WHERE id = ?");
        $stmt4->execute([$i + 1]);
        $val4 = $stmt4->fetchColumn();
        
        // 用latin1连接读取
        $stmt1 = $pdoLatin1->prepare("SELECT menu_name FROM `{$menuTable}` WHERE id = ?");
        $stmt1->execute([$i + 1]);
        $val1 = $stmt1->fetchColumn();
        
        $display = htmlspecialchars($name);
        $display4 = htmlspecialchars($val4);
        $display1 = htmlspecialchars(mb_convert_encoding($val1, 'UTF-8', 'ISO-8859-1'));
        
        echo "<tr>";
        echo "<td>{$i}</td>";
        echo "<td>{$display}</td>";
        echo "<td>{$display4}</td>";
        echo "<td>" . ($val1 !== $name ? "<span class='warn'>{$display1}</span>" : "<span class='success'>相同</span>") . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div class='info'>💡 <strong>诊断结论：</strong><br>";
    echo "如果 utf8 和 utf8mb4 读取结果一致，说明数据库数据没有问题。<br>";
    echo "如果 latin1 读取结果与 utf8 不同，说明数据库连接编码影响数据读取。<br>";
    echo "请检查上方菜单数据的 Hex 字节，看是否包含正确的 UTF-8 编码序列。</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ 错误: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<h2>5️⃣ 快速修复方案</h2>";
echo "<div class='warn'>";
echo "<p><strong>如果数据库数据正确但前端乱码，请执行以下任一方案：</strong></p>";
echo "<p><strong>方案A：</strong> 修改 .env 文件，将 CHARSET 改为 utf8mb4</p>";
echo "<p><strong>方案B：</strong> 在服务器上执行：</p>";
echo "<pre>
# 检查 PHP-FPM 默认编码
php -i | grep default_charset

# 修改 php.ini 确保 default_charset = UTF-8
# 然后重启 PHP-FPM
systemctl reload php-fpm

# 检查 Nginx 配置
nginx -t
nginx -s reload
</pre>";
echo "</div>";

echo "</body></html>";

function parseEnv($file) {
    $defaults = ['hostname'=>'127.0.0.1','database'=>'crmeb31','username'=>'root','password'=>'root','hostport'=>'3306','charset'=>'utf8','prefix'=>'eb_'];
    if (!file_exists($file)) return $defaults;
    $content = file_get_contents($file);
    $lines = preg_split('/\r\n|\r|\n/', $content);
    $section = '';
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        if (strpos($line, '[') === 0 && strpos($line, ']') !== false) { $section = strtolower(trim($line, '[]')); continue; }
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = strtolower(trim($key)); $value = trim($value);
            if ($section === 'database') {
                switch($key) {
                    case 'hostname': $defaults['hostname']=$value; break;
                    case 'database': $defaults['database']=$value; break;
                    case 'username': $defaults['username']=$value; break;
                    case 'password': $defaults['password']=$value; break;
                    case 'hostport': $defaults['hostport']=$value; break;
                    case 'charset': $defaults['charset']=$value; break;
                    case 'prefix': $defaults['prefix']=$value; break;
                }
            }
        }
    }
    return $defaults;
}
?>