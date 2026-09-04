<?php
/**
 * 检查商品分类表数据
 */
header('Content-Type: text/html; charset=utf-8');
require __DIR__ . '/../vendor/autoload.php';
$app = new think\App();
$app->initialize();

use think\facade\Db;

echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>商品分类检查</title>';
echo '<style>body{font-family:monospace;padding:20px;}table{border-collapse:collapse;width:100%;}';
echo 'th,td{border:1px solid #ddd;padding:6px 10px;text-align:left;}th{background:#f0f0f0;}</style></head><body>';
echo '<h1>商品分类数据检查</h1>';

try {
    // 1. 检查 store_category 表
    echo '<h2>eb_store_category 表数据</h2>';
    $categories = Db::name('store_category')->order('pid, sort')->select();
    if ($categories->isEmpty()) {
        echo '<p style="color:red">✗ 表为空，没有数据</p>';
    } else {
        echo '<table><tr><th>ID</th><th>PID</th><th>分类名称</th><th>排序</th><th>是否显示</th><th>添加时间</th></tr>';
        foreach ($categories as $cat) {
            $color = $cat['pid'] == 0 ? '#e8f5e9' : '#fff3e0';
            echo "<tr style='background:{$color}'>";
            echo "<td>{$cat['id']}</td>";
            echo "<td>{$cat['pid']}</td>";
            echo "<td>" . htmlspecialchars($cat['cate_name']) . "</td>";
            echo "<td>{$cat['sort']}</td>";
            echo "<td>" . ($cat['is_show'] ? '显示' : '隐藏') . "</td>";
            echo "<td>" . date('Y-m-d H:i:s', $cat['add_time']) . "</td>";
            echo "</tr>";
        }
        echo '</table>';
        echo '<p>共 ' . count($categories) . ' 条记录</p>';
    }

    // 2. 检查是否有 "陶瓷贴片电容器" 分类
    echo '<h2>搜索 "陶瓷贴片电容器"</h2>';
    $parent = Db::name('store_category')->where('cate_name', '陶瓷贴片电容器')->where('pid', 0)->find();
    if ($parent) {
        echo '<p style="color:green">✓ 找到一级分类: ID=' . $parent['id'] . '</p>';
        $children = Db::name('store_category')->where('pid', $parent['id'])->select();
        echo '<p>子分类数量: ' . count($children) . '</p>';
        echo '<table><tr><th>ID</th><th>名称</th><th>排序</th></tr>';
        foreach ($children as $child) {
            echo "<tr><td>{$child['id']}</td><td>" . htmlspecialchars($child['cate_name']) . "</td><td>{$child['sort']}</td></tr>";
        }
        echo '</table>';
    } else {
        echo '<p style="color:red">✗ 未找到 "陶瓷贴片电容器" 分类</p>';
    }

    // 3. 检查是否存在其他一级分类
    echo '<h2>所有一级分类（pid=0）</h2>';
    $topCategories = Db::name('store_category')->where('pid', 0)->select();
    if ($topCategories->isEmpty()) {
        echo '<p style="color:red">✗ 没有一级分类</p>';
    } else {
        echo '<table><tr><th>ID</th><th>名称</th><th>排序</th></tr>';
        foreach ($topCategories as $cat) {
            echo "<tr><td>{$cat['id']}</td><td>" . htmlspecialchars($cat['cate_name']) . "</td><td>{$cat['sort']}</td></tr>";
        }
        echo '</table>';
    }

} catch (Exception $e) {
    echo '<p style="color:red">错误: ' . $e->getMessage() . '</p>';
}

echo '</body></html>';