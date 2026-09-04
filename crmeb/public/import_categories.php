<?php
/**
 * 商品分类完整导入脚本 - 电子元器件9大分类
 * 直接访问此文件即可执行：删除所有旧分类 → 导入完整9大类
 *
 * 数据结构（依据用户Excel品牌分类表）：
 *   一级分类9个 → 二级品牌 → (部分)三级子类
 *
 * 说明：
 *   1. 会删除 eb_store_category 表中所有旧数据（老分类全部清空）
 *   2. 会删除所有商品相关表数据（保持数据一致）
 *   3. 可重复执行，幂等
 */

header('Content-Type: text/html; charset=utf-8');
set_time_limit(300);

// 加载ThinkPHP环境
$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    $autoload = __DIR__ . '/vendor/autoload.php';
}
if (!file_exists($autoload)) {
    die("❌ 找不到 autoload.php");
}
require $autoload;

// 读取.env配置（解析 [DATABASE] 段）
function parseEnv($file) {
    $defaults = [
        'hostname' => '127.0.0.1',
        'database' => 'crmeb31',
        'username' => 'root',
        'password' => 'root',
        'hostport' => '3306',
        'prefix' => 'eb_',
    ];
    if (!file_exists($file)) return $defaults;
    $content = file_get_contents($file);
    $lines = preg_split('/\r\n|\r|\n/', $content);
    $section = '';
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || $line[0] == '#' || $line[0] == ';') continue;
        if ($line[0] == '[' && strpos($line, ']') !== false) {
            $section = strtoupper(trim($line, '[]'));
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($k, $v) = explode('=', $line, 2);
            $k = strtoupper(trim($k));
            $v = trim($v);
            if ($section === 'DATABASE' || $section === '') {
                switch ($envKey = ['HOSTNAME' => 'hostname', 'DATABASE' => 'database',
                                   'USERNAME' => 'username', 'PASSWORD' => 'password',
                                   'HOSTPORT' => 'hostport', 'PREFIX' => 'prefix'][$k] ?? null) {
                    case 'hostname': $defaults['hostname'] = $v; break;
                    case 'database': $defaults['database'] = $v; break;
                    case 'username': $defaults['username'] = $v; break;
                    case 'password': $defaults['password'] = $v; break;
                    case 'hostport': $defaults['hostport'] = $v; break;
                    case 'prefix':   $defaults['prefix'] = $v; break;
                }
            }
        }
    }
    return $defaults;
}

$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) $envFile = __DIR__ . '/.env';
$config = parseEnv($envFile);
extract($config);

echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>商品分类完整导入 - 9大分类</title>';
echo '<style>body{font-family:"Microsoft YaHei",sans-serif;max-width:1000px;margin:20px auto;padding:20px;line-height:1.8}';
echo 'h1{color:#2c3e50;border-bottom:3px solid #e74c3c;padding-bottom:10px}h2{color:#2c3e50;border-left:4px solid #e74c3c;padding-left:10px}';
echo '.success{color:#090;background:#e8f8e8;padding:5px 10px;border-radius:4px;margin:5px 0}';
echo '.error{color:#900;background:#fce8e8;padding:5px 10px;border-radius:4px;margin:5px 0}';
echo '.info{background:#f0f8ff;padding:5px 10px;border-radius:4px;margin:5px 0}';
echo 'table{border-collapse:collapse;width:100%;margin:10px 0}th,td{border:1px solid #ddd;padding:5px 8px;text-align:left}th{background:#f5f5f5}</style></head><body>';
echo '<h1>商品分类完整导入 - 电子元器件9大分类</h1>';

try {
    $pdo = new PDO(
        "mysql:host={$hostname};port={$hostport};dbname={$database};charset=utf8mb4",
        $username, $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    echo "<div class='success'>✅ 数据库连接成功: {$database}</div>";

    $categoryTable = $prefix . 'store_category';

    // ============================================================
    // 第一步：清空所有商品相关数据 + 所有分类
    // ============================================================
    echo '<h2>第一步：清空旧数据</h2>';

    $productTables = [
        $prefix.'store_product', $prefix.'store_product_cate', $prefix.'store_product_attr',
        $prefix.'store_product_attr_value', $prefix.'store_product_attr_result',
        $prefix.'store_product_description', $prefix.'store_product_coupon',
        $prefix.'store_product_label', $prefix.'store_product_log', $prefix.'store_product_param',
        $prefix.'store_product_protection', $prefix.'store_product_relation',
        $prefix.'store_product_reply', $prefix.'store_product_virtual', $prefix.'store_visit',
    ];
    $totalDeleted = 0;
    foreach ($productTables as $pt) {
        try {
            $c = $pdo->exec("DELETE FROM `{$pt}`");
            $totalDeleted += $c;
            echo "<div class='info'>  📋 {$pt}: 删除 {$c} 条</div>";
        } catch (Exception $e) {
            echo "<div class='info'>  ⚠️ {$pt}: ".$e->getMessage()." (忽略)</div>";
        }
    }

    $oldCats = $pdo->exec("DELETE FROM `{$categoryTable}`");
    echo "<div class='success'>✅ 分类表已清空: 删除 {$oldCats} 条旧分类（老数据全部清除）</div>";

    // ============================================================
    // 第二步：定义9大分类完整结构
    // ============================================================
    echo '<h2>第二步：定义分类结构</h2>';

    // 结构：一级 => ['sort' => , 'brands' => [品牌 => [sort =>, 'subs' => [三级列表] 或 false(无三级)]]]
    $categories = [
        // 1. 陶瓷贴片电容器 - 只有2级品牌
        '陶瓷贴片电容器' => ['sort' => 1, 'brands' => [
            'muRata(村田)' => false, 'TDK' => false, 'Taiyo Yuden(太诱)' => false,
            'Kyocera(京瓷)' => false, 'Walsin(华科)' => false, 'SAMSUNG(三星)' => false,
            'Holy Stone(禾伸堂)' => false, 'PSA(信昌)' => false, 'Yageo(国巨)' => false,
            'FH(风华)' => false, 'CCTC(三环)' => false, 'VIYONG(微容)' => false, 'SAMWHA(三和)' => false,
        ]],
        // 2. 电感器（线圈） - 2级品牌 + 三级子类（按用户选择：TDK只建到二级）
        '电感器（线圈）' => ['sort' => 2, 'brands' => [
            'muRata(村田)' => ['一般电路用电感器', '电源线用电感器', '高频电路用电感器', 'PoE电感器', '无线电感器', '静噪用电感器', '可变电感器'],
            'TDK' => false, // 用户确认：TDK只建到二级品牌
        ]],
        // 3. EMI静噪滤波器 - 2个品牌共享3级
        'EMI静噪滤波器' => ['sort' => 3, 'brands' => [
            'muRata(村田)' => ['噪音滤波器', 'BSD陷波滤波器', '3端子滤波器', '共模扼流圈/滤波器', '低通滤波器', '双工器(Diplexer)', '三工器', '平衡器', '定向耦合器', '分配器/分线器', '贴片天线', '高频电路用电感器', 'LC低通滤波器'],
            'TDK' => ['噪音滤波器', 'BSD陷波滤波器', '3端子滤波器', '共模扼流圈/滤波器', '低通滤波器', '双工器(Diplexer)', '三工器', '平衡器', '定向耦合器', '分配器/分线器', '贴片天线', '高频电路用电感器', 'LC低通滤波器'],
        ]],
        // 4. 片状铁氧体磁珠 - 2品牌
        '片状铁氧体磁珠' => ['sort' => 4, 'brands' => [
            'TDK' => ['贴片磁珠'],
            'muRata(村田)' => ['磁珠'],
        ]],
        // 5. 电源电路保护 - 2个品牌共享3级
        '电源电路保护' => ['sort' => 5, 'brands' => [
            'muRata(村田)' => ['NTC热敏电阻', 'PTC热敏电阻', 'PRF系列/PTC热敏电阻', 'PRG系列/PTC热敏电阻', '压敏电阻'],
            'TDK' => ['NTC热敏电阻', 'PTC热敏电阻', 'PRF系列/PTC热敏电阻', 'PRG系列/PTC热敏电阻', '压敏电阻'],
        ]],
        // 6. 传感器 无线射频 - 2个品牌共享3级
        '传感器 无线射频' => ['sort' => 6, 'brands' => [
            'muRata(村田)' => ['温度传感器(NTC)', '温度传感器(PTC)', '液位传感器', '压力传感器', '湿度传感器', '电阻型传感器', '电流传感器', '角度传感器', '超声波传感器', '线性霍尔传感器'],
            'TDK' => ['温度传感器(NTC)', '温度传感器(PTC)', '液位传感器', '压力传感器', '湿度传感器', '电阻型传感器', '电流传感器', '角度传感器', '超声波传感器', '线性霍尔传感器'],
        ]],
        // 7. 集成电路（IC） - 只保留一级
        '集成电路（IC）' => ['sort' => 7, 'brands' => []],
        // 8. 晶体管·晶振 - 只保留一级
        '晶体管·晶振' => ['sort' => 8, 'brands' => []],
        // 9. 二极管整流器 - 只保留一级
        '二极管整流器' => ['sort' => 9, 'brands' => []],
    ];

    echo "<div class='info'>ℹ️ 共 ".count($categories)." 个一级分类</div>";

    // ============================================================
    // 第三步：插入一级分类
    // ============================================================
    echo '<h2>第三步：插入一级分类</h2>';

    $insertCat = function($pdo, $pid, $name, $sort) use ($categoryTable) {
        $stmt = $pdo->prepare("INSERT INTO `{$categoryTable}` (pid, cate_name, sort, pic, is_show, add_time) VALUES (?, ?, ?, '', 1, ?)");
        $stmt->execute([$pid, $name, $sort, time()]);
        return $pdo->lastInsertId();
    };

    $insertedCount = 0;
    $parentIds = [];
    foreach ($categories as $name => $info) {
        $id = $insertCat($pdo, 0, $name, $info['sort']);
        $parentIds[$name] = $id;
        $insertedCount++;
        $hasBrands = count($info['brands']) ? "（含 ".count($info['brands'])." 个品牌待插入）" : "";
        echo "<div class='success'>✅ 一级分类: {$name} (ID: {$id}) {$hasBrands}</div>";
    }
    echo "<div class='success'>✅ 一级分类插入完成: {$insertedCount} 个</div>";

    // ============================================================
    // 第四步：插入二级品牌 + 三级子类
    // ============================================================
    echo '<h2>第四步：插入二级品牌与三级子类</h2>';

    $stmtBrand = $pdo->prepare("INSERT INTO `{$categoryTable}` (pid, cate_name, sort, pic, is_show, add_time) VALUES (?, ?, ?, '', 1, ?)");
    $stmtSub   = $pdo->prepare("INSERT INTO `{$categoryTable}` (pid, cate_name, sort, pic, is_show, add_time) VALUES (?, ?, ?, '', 1, ?)");

    $brandTotal = 0;
    $subTotal = 0;

    foreach ($categories as $catName => $info) {
        $catId = $parentIds[$catName];
        $brandSort = 1;
        foreach ($info['brands'] as $brandName => $subs) {
            $stmtBrand->execute([$catId, $brandName, $brandSort, time()]);
            $brandId = $pdo->lastInsertId();
            $brandTotal++;
            $brandSort++;
            echo "<div class='info'>  🏷 {$catName} > {$brandName} (ID: {$brandId})</div>";

            if (is_array($subs) && count($subs)) {
                $subSort = 1;
                foreach ($subs as $subName) {
                    $stmtSub->execute([$brandId, $subName, $subSort, time()]);
                    $subTotal++;
                    $subSort++;
                }
                echo "    └ 三级子类: ".implode(' / ', $subs)."<br>";
            }
        }
    }

    echo "<div class='success'>✅ 二级品牌插入完成: {$brandTotal} 个</div>";
    echo "<div class='success'>✅ 三级子类插入完成: {$subTotal} 个</div>";

    // ============================================================
    // 第五步：验证结果
    // ============================================================
    echo '<h2>第五步：验证导入结果</h2>';

    $stmt = $pdo->query("SELECT id, pid, cate_name, sort FROM `{$categoryTable}` ORDER BY pid, sort, id");
    $rows = $stmt->fetchAll();

    echo '<table><tr><th>ID</th><th>PID</th><th>分类名称</th><th>排序</th></tr>';
    foreach ($rows as $row) {
        $name = htmlspecialchars($row['cate_name']);
        $depth = ($row['pid'] == 0) ? '<strong>' : (in_array($row['pid'], $parentIds) ? '&nbsp;&nbsp;└ ' : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;· ');
        echo "<tr><td>{$row['id']}</td><td>{$row['pid']}</td><td>{$depth}{$name}</td><td>{$row['sort']}</td></tr>";
    }
    echo '</table>';
    echo '<p>共 '.count($rows).' 条记录</p>';

    // 统计
    $topCount  = 0; $brandCount = 0; $subCount3 = 0;
    foreach ($rows as $r) {
        if ($r['pid'] == 0) $topCount++;
        elseif (in_array($r['pid'], $parentIds)) $brandCount++;
        else $subCount3++;
    }
    echo "<div class='success'>✅ 统计：一级 {$topCount} 个，二级品牌 {$brandCount} 个，三级子类 {$subCount3} 个</div>";

    echo '<h2>🎉 导入完成！</h2>';
    echo "<div class='info'>💡 现在去后台 <strong>商品 → 商品分类</strong> 查看：应该是完整的9大分类结构，旧数据已全部清除</div>";
    echo "<div class='error'>⚠️ 验证通过后请删除本脚本 (import_categories.php)，保证服务器安全</div>";

} catch (Exception $e) {
    echo "<div class='error'>❌ 导入失败: ".htmlspecialchars($e->getMessage())."</div>";
    echo "<div class='error'>文件: ".$e->getFile().":".$e->getLine()."</div>";
}

echo '</body></html>';