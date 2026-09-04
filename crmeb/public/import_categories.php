<?php
/**
 * 商品分类导入脚本：陶瓷贴片电容器
 * 直接访问此文件即可执行导入
 * 
 * 数据结构：
 * - 一级分类：陶瓷贴片电容器 (pid = 0)
 * - 二级分类：13个品牌 (pid = 一级分类ID)
 */

header('Content-Type: text/html; charset=utf-8');
set_time_limit(120);

// 加载ThinkPHP环境
$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    die("❌ 找不到 autoload.php，请确认路径正确");
}
require $autoload;

// 读取.env配置
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

$envFile = __DIR__ . '/../.env';
$config = parseEnv($envFile);
extract($config);

echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>商品分类导入 - 陶瓷贴片电容器</title>';
echo '<style>body{font-family:"Microsoft YaHei",sans-serif;max-width:900px;margin:20px auto;padding:20px;line-height:1.8}';
echo 'h1{color:#2c3e50;border-bottom:3px solid #e74c3c;padding-bottom:10px}h2{color:#2c3e50;border-left:4px solid #e74c3c;padding-left:10px}';
echo '.success{color:#090;background:#e8f8e8;padding:5px 10px;border-radius:4px;margin:5px 0}';
echo '.error{color:#900;background:#fce8e8;padding:5px 10px;border-radius:4px;margin:5px 0}';
echo '.info{background:#f0f8ff;padding:5px 10px;border-radius:4px;margin:5px 0}';
echo 'table{border-collapse:collapse;width:100%;margin:10px 0}th,td{border:1px solid #ddd;padding:6px 10px;text-align:left}th{background:#f5f5f5}</style></head><body>';
echo '<h1>商品分类导入 - 陶瓷贴片电容器</h1>';

try {
    // 连接数据库
    $pdo = new PDO(
        "mysql:host={$hostname};port={$hostport};dbname={$database};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    echo "<div class='success'>✅ 数据库连接成功: {$database}</div>";

    $categoryTable = $prefix . 'store_category';

    // ============================================================
    // 第一步：清空所有商品相关数据
    // ============================================================
    echo '<h2>第一步：清空所有商品数据</h2>';

    $productTables = [
        $prefix . 'store_product',
        $prefix . 'store_product_cate',
        $prefix . 'store_product_attr',
        $prefix . 'store_product_attr_value',
        $prefix . 'store_product_attr_result',
        $prefix . 'store_product_description',
        $prefix . 'store_product_coupon',
        $prefix . 'store_product_label',
        $prefix . 'store_product_log',
        $prefix . 'store_product_param',
        $prefix . 'store_product_protection',
        $prefix . 'store_product_relation',
        $prefix . 'store_product_reply',
        $prefix . 'store_product_virtual',
        $prefix . 'store_visit',
    ];

    $totalDeleted = 0;
    foreach ($productTables as $pt) {
        try {
            $count = $pdo->exec("DELETE FROM `{$pt}`");
            if ($count === false) $count = 0;
            $totalDeleted += $count;
            echo "<div class='info'>  📋 {$pt}: 已删除 {$count} 条</div>";
        } catch (Exception $e) {
            echo "<div class='info'>  ⚠️ {$pt}: " . $e->getMessage() . " (已忽略)</div>";
        }
    }
    echo "<div class='success'>✅ 商品数据清空完成，共删除 {$totalDeleted} 条记录</div>";

    // ============================================================
    // 第二步：删除已存在的陶瓷贴片电容器分类
    // ============================================================
    echo '<h2>第二步：清理旧数据</h2>';

    $stmt = $pdo->prepare("SELECT id FROM `{$categoryTable}` WHERE cate_name = ? AND pid = 0");
    $stmt->execute(['陶瓷贴片电容器']);
    $parentId = $stmt->fetchColumn();

    if ($parentId) {
        $stmt = $pdo->prepare("DELETE FROM `{$categoryTable}` WHERE pid = ?");
        $stmt->execute([$parentId]);
        $childrenDeleted = $stmt->rowCount();
        $stmt = $pdo->prepare("DELETE FROM `{$categoryTable}` WHERE id = ?");
        $stmt->execute([$parentId]);
        echo "<div class='success'>✅ 删除已存在的分类: 1个一级分类 + {$childrenDeleted} 个二级分类</div>";
    } else {
        echo "<div class='info'>ℹ️ 未找到已存在的陶瓷贴片电容器分类</div>";
    }

    // ============================================================
    // 第三步：插入一级分类
    // ============================================================
    echo '<h2>第三步：插入一级分类</h2>';

    $stmt = $pdo->prepare("INSERT INTO `{$categoryTable}` (pid, cate_name, sort, pic, is_show, add_time) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([0, '陶瓷贴片电容器', 1, '', 1, time()]);
    $parentId = $pdo->lastInsertId();
    echo "<div class='success'>✅ 一级分类插入成功：陶瓷贴片电容器 (ID: {$parentId})</div>";

    // ============================================================
    // 第四步：插入13个二级分类（品牌）
    // ============================================================
    echo '<h2>第四步：插入二级分类（品牌）</h2>';

    $brands = [
        1 => 'muRata(村田)',
        2 => 'TDK',
        3 => 'Taiyo Yuden(太诱)',
        4 => 'Kyocera(京瓷)',
        5 => 'Walsin(华科)',
        6 => 'SAMSUNG(三星)',
        7 => 'Holy Stone(禾伸堂)',
        8 => 'PSA(信昌)',
        9 => 'Yageo(国巨)',
        10 => 'FH(风华)',
        11 => 'CCTC(三环)',
        12 => 'VIYONG(微容)',
        13 => 'SAMWHA(三和)',
    ];

    $inserted = 0;
    $stmt = $pdo->prepare("INSERT INTO `{$categoryTable}` (pid, cate_name, sort, pic, is_show, add_time) VALUES (?, ?, ?, ?, ?, ?)");
    
    echo '<table><tr><th>#</th><th>品牌名称</th><th>状态</th></tr>';
    foreach ($brands as $sort => $brand) {
        try {
            $stmt->execute([$parentId, $brand, $sort, '', 1, time()]);
            if ($stmt->rowCount() > 0) {
                $inserted++;
                echo "<tr><td>{$sort}</td><td>" . htmlspecialchars($brand) . "</td><td class='success'>✅ 成功</td></tr>";
            } else {
                echo "<tr><td>{$sort}</td><td>" . htmlspecialchars($brand) . "</td><td class='error'>⚠️ 无返回</td></tr>";
            }
        } catch (Exception $e) {
            echo "<tr><td>{$sort}</td><td>" . htmlspecialchars($brand) . "</td><td class='error'>❌ " . $e->getMessage() . "</td></tr>";
        }
    }
    echo '</table>';

    echo "<div class='success'>✅ 二级分类插入完成: {$inserted} / " . count($brands) . " 个</div>";

    // ============================================================
    // 第五步：验证结果
    // ============================================================
    echo '<h2>第五步：验证导入结果</h2>';

    $stmt = $pdo->query("SELECT id, pid, cate_name, sort, is_show FROM `{$categoryTable}` WHERE pid = {$parentId} ORDER BY sort");
    $rows = $stmt->fetchAll();

    echo '<table><tr><th>ID</th><th>PID</th><th>分类名称</th><th>排序</th><th>状态</th></tr>';
    foreach ($rows as $row) {
        $status = $row['is_show'] ? '<span class="success">显示</span>' : '<span class="error">隐藏</span>';
        echo "<tr><td>{$row['id']}</td><td>{$row['pid']}</td><td><strong>" . htmlspecialchars($row['cate_name']) . "</strong></td><td>{$row['sort']}</td><td>{$status}</td></tr>";
    }
    echo '</table>';

    // ============================================================
    // 完成
    // ============================================================
    echo '<h2>🎉 导入完成！</h2>';
    echo "<div class='success'>";
    echo "<ul>";
    echo "<li>一级分类：陶瓷贴片电容器 (ID: {$parentId})</li>";
    echo "<li>二级分类：{$inserted} 个品牌</li>";
    echo "<li>商品数据：已清空所有现有商品</li>";
    echo "</ul>";
    echo "</div>";
    echo "<div class='info'>💡 现在你可以去后台 <strong>商品 → 商品分类</strong> 查看结果了</div>";
    echo "<div class='info'>⚠️ 导入完成后请删除此文件 (import_categories.php) 保证安全</div>";

} catch (Exception $e) {
    echo "<div class='error'>❌ 导入失败: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<div class='error'>文件: " . $e->getFile() . ":" . $e->getLine() . "</div>";
}

echo '</body></html>';
