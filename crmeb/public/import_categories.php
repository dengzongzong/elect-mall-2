<?php
/**
 * ============================================================
 * 产品分类导入脚本 - 陶瓷贴片电容器
 * ============================================================
 * 
 * 功能：导入两级分类结构
 * 一级分类：陶瓷贴片电容器
 * 二级分类：该类别下的所有品牌（共13个）
 * 
 * 使用方法：访问 http://你的域名/import_categories.php 即可执行
 * 安全提醒：执行成功后请立即删除此文件！
 * ============================================================
 */

header('Content-Type: text/html; charset=utf-8');

// 防止脚本超时
set_time_limit(120);

// 加载ThinkPHP框架
require __DIR__ . '/../vendor/autoload.php';

// 应用入口文件
$app = new think\App();
$app->initialize();

use think\facade\Db;

echo '<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>产品分类导入 - 陶瓷贴片电容器</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: -apple-system, "Microsoft YaHei", sans-serif;
    max-width: 960px; margin: 30px auto; padding: 20px;
    background: #f5f7fa; color: #333; line-height: 1.7;
}
h1 {
    color: #1a1a2e; border-bottom: 3px solid #e94560;
    padding-bottom: 12px; margin-bottom: 24px; font-size: 24px;
}
h2 {
    color: #1a1a2e; border-left: 4px solid #e94560;
    padding-left: 12px; margin: 28px 0 16px 0; font-size: 18px;
}
.summary-box {
    background: #fff; border-radius: 8px; padding: 20px;
    margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.output {
    background: #1a1a2e; color: #00ff88; padding: 16px;
    margin: 12px 0; border-radius: 6px; font-family: "Consolas","Monaco",monospace;
    font-size: 13px; line-height: 1.6; white-space: pre-wrap; word-break: break-all;
    max-height: 600px; overflow-y: auto;
}
.status-success { color: #00c853; font-weight: bold; }
.status-warning { color: #ff9100; font-weight: bold; }
.status-error { color: #ff1744; font-weight: bold; }
.status-info { color: #448aff; font-weight: bold; }
.btn {
    display: inline-block; background: #e94560; color: #fff;
    border: none; padding: 14px 32px; font-size: 16px; font-weight: bold;
    cursor: pointer; border-radius: 6px; transition: all 0.2s;
    text-decoration: none;
}
.btn:hover { background: #d63851; transform: translateY(-1px); }
table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 14px; }
table th, table td { border: 1px solid #ddd; padding: 6px 10px; text-align: left; }
table th { background: #f0f0f0; font-weight: 600; }
.warn-box { background: #fff3e0; border: 1px solid #ffcc02; border-radius: 6px; padding: 12px; margin: 12px 0; }
</style>
</head>
<body>

<h1>产品分类导入 - 陶瓷贴片电容器</h1>

<div class="summary-box">';
?>
<?php
// 定义两级分类数据
// 一级分类：陶瓷贴片电容器
// 二级分类：13个品牌

$categoryData = [
    // 一级分类
    'parent' => [
        'cate_name' => '陶瓷贴片电容器',
        'pid' => 0,
        'sort' => 1,
        'is_show' => 1,
        'pic' => '',
        'add_time' => time()
    ],
    // 二级分类（品牌列表）
    'children' => [
        ['name' => 'muRata(村田)', 'sort' => 1],
        ['name' => 'TDK', 'sort' => 2],
        ['name' => 'Taiyo Yuden(太诱)', 'sort' => 3],
        ['name' => 'Kyocera(京瓷)', 'sort' => 4],
        ['name' => 'Walsin(华科)', 'sort' => 5],
        ['name' => 'SAMSUNG(三星)', 'sort' => 6],
        ['name' => 'Holy Stone(禾伸堂)', 'sort' => 7],
        ['name' => 'PSA(信昌)', 'sort' => 8],
        ['name' => 'Yageo(国巨)', 'sort' => 9],
        ['name' => 'FH(风华)', 'sort' => 10],
        ['name' => 'CCTC(三环)', 'sort' => 11],
        ['name' => 'VIYONG(微容)', 'sort' => 12],
        ['name' => 'SAMWHA(三和)', 'sort' => 13],
    ]
];

echo "<p><strong>导入数据预览：</strong></p>";
echo "<table>";
echo "<tr><th>层级</th><th>分类名称</th><th>父ID</th></tr>";
echo "<tr><td>一级分类</td><td>{$categoryData['parent']['cate_name']}</td><td>0</td></tr>";
foreach ($categoryData['children'] as $child) {
    echo "<tr><td>二级分类</td><td>{$child['name']}</td><td>一级分类ID</td></tr>";
}
echo "</table>";

echo "<p>总共：1个一级分类 + " . count($categoryData['children']) . "个二级分类 = " . (count($categoryData['children']) + 1) . "条记录</p>";
echo "</div>";

// 检查是否已经执行
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo '<div class="summary-box">
    <form method="post">
        <p>请确认数据正确，点击下方按钮开始导入：</p>
        <br>
        <button type="submit" class="btn">开始导入</button>
    </form>
    </div>';
    echo '</body></html>';
    exit;
}

echo '<h2>执行结果</h2>
<div class="output">';

echo "[1/3] 开始连接数据库...\n";
try {
    // 检查数据库连接
    $connection = Db::connect();
    echo "✓ 数据库连接成功\n\n";
} catch (Exception $e) {
    echo "✗ 数据库连接失败: " . $e->getMessage() . "\n";
    echo '</div></body></html>';
    exit;
}

// 检查表是否存在
echo "[2/3] 检查数据表 eb_store_category...\n";
try {
    $hasTable = $connection->query("SHOW TABLES LIKE 'eb_store_category'");
    if (!$hasTable) {
        echo "✗ 数据表 eb_store_category 不存在，请先安装系统\n";
        echo '</div></body></html>';
        exit;
    }
    echo "✓ 数据表存在\n\n";
} catch (Exception $e) {
    echo "✗ 检查表失败: " . $e->getMessage() . "\n";
    echo '</div></body></html>';
    exit;
}

// 开始导入
echo "[3/3] 开始导入数据...\n\n";

$successCount = 0;
$errorCount = 0;
$errors = [];

try {
    // 检查一级分类是否已存在
    $existingParent = Db::name('store_category')
        ->where('cate_name', $categoryData['parent']['cate_name'])
        ->where('pid', 0)
        ->find();
    
    if ($existingParent) {
        echo "<span class=\"status-warning\">⚠ 一级分类 '{$categoryData['parent']['cate_name']}' 已存在 (ID: {$existingParent['id']})，将使用现有分类</span>\n";
        $parentId = $existingParent['id'];
        $successCount++;
    } else {
        // 插入一级分类
        $parentId = Db::name('store_category')->insertGetId($categoryData['parent']);
        echo "<span class=\"status-success\">✓ 成功插入一级分类 '{$categoryData['parent']['cate_name']}' (ID: {$parentId})</span>\n";
        $successCount++;
    }

    echo "\n开始插入二级分类...\n";

    // 插入二级分类
    foreach ($categoryData['children'] as $index => $child) {
        try {
            // 检查是否已存在
            $existingChild = Db::name('store_category')
                ->where('cate_name', $child['name'])
                ->where('pid', $parentId)
                ->find();
            
            if ($existingChild) {
                echo "<span class=\"status-warning\">  ⚠ '{$child['name']}' 已存在 (ID: {$existingChild['id']})，跳过</span>\n";
                continue;
            }

            // 插入数据
            $childData = [
                'cate_name' => $child['name'],
                'pid' => $parentId,
                'sort' => $child['sort'],
                'is_show' => 1,
                'pic' => '',
                'add_time' => time()
            ];

            $childId = Db::name('store_category')->insertGetId($childData);
            echo "<span class=\"status-success\">  ✓ '{$child['name']}' (ID: {$childId})</span>\n";
            $successCount++;

        } catch (Exception $e) {
            echo "<span class=\"status-error\">  ✗ '{$child['name']}' 插入失败: " . $e->getMessage() . "</span>\n";
            $errorCount++;
            $errors[] = "{$child['name']}: " . $e->getMessage();
        }
    }

} catch (Exception $e) {
    echo "<span class=\"status-error\">✗ 导入过程发生错误: " . $e->getMessage() . "</span>\n";
    $errorCount++;
    $errors[] = "整体导入失败: " . $e->getMessage();
}

echo "\n";
echo "====================\n";
echo "导入完成!\n";
echo "成功: <span class=\"status-success\">{$successCount}</span>\n";
echo "失败/跳过: <span class=\"status-warning\">{$errorCount}</span>\n";
echo "====================\n";

echo '</div>';

if (!empty($errors)) {
    echo '<h2>错误详情</h2>
    <div class="output">';
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    echo '</div>';
}

echo '<div class="summary-box">
    <p><strong class="status-success">导入完成！请登录后台查看产品分类。</strong></p>
    <div class="warn-box">
        <strong>安全提醒：</strong>为了安全起见，请在确认导入完成后删除此文件 <code>public/import_categories.php</code>
    </div>
</div>';

echo '</body></html>';
?>