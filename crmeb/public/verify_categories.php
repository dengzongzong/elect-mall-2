<?php
header('Content-Type: text/html; charset=utf-8');
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=crmeb31;charset=utf8mb4', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 一级分类
    $stmt = $pdo->query("SELECT id, cate_name, sort, is_show FROM eb_store_category WHERE pid = 0 ORDER BY sort");
    $parents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 二级分类
    $stmt = $pdo->query("SELECT id, pid, cate_name, sort, is_show FROM eb_store_category WHERE pid > 0 ORDER BY pid, sort");
    $children = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $childMap = [];
    foreach ($children as $c) {
        $childMap[$c['pid']][] = $c;
    }
    
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>分类数据验证</title>';
    echo '<style>
        body{font-family:"Microsoft YaHei",sans-serif;max-width:1000px;margin:30px auto;padding:20px;background:#f5f5f5}
        h1{color:#2c3e50;border-bottom:3px solid #1890ff;padding-bottom:10px}
        .card{background:#fff;border-radius:8px;padding:20px;margin:20px 0;box-shadow:0 2px 8px rgba(0,0,0,0.1)}
        .parent{color:#1890ff;font-size:18px;font-weight:bold;margin:15px 0 10px;padding:10px 15px;background:#e6f7ff;border-radius:4px;border-left:4px solid #1890ff}
        .child{display:inline-block;margin:5px 8px;padding:8px 16px;background:#fafafa;border:1px solid #e8e8e8;border-radius:4px;font-size:14px;transition:all 0.3s}
        .child:hover{background:#e6f7ff;border-color:#1890ff}
        .summary{font-size:16px;padding:15px;background:#f6ffed;border:1px solid #b7eb8f;border-radius:4px;margin:10px 0}
        .badge{display:inline-block;background:#1890ff;color:#fff;padding:2px 8px;border-radius:10px;font-size:12px;margin-left:8px}
    </style></head><body>';
    echo '<h1>📦 商品分类数据验证</h1>';
    echo '<div class="card">';
    
    $totalParents = count($parents);
    $totalChildren = count($children);
    echo "<div class='summary'>✅ 一级分类: <strong>{$totalParents}</strong> 个 | 二级分类: <strong>{$totalChildren}</strong> 个 | 总计: <strong>" . ($totalParents + $totalChildren) . "</strong> 个</div>";
    
    foreach ($parents as $p) {
        echo "<div class='parent'>📁 {$p['cate_name']} <span class='badge'>ID: {$p['id']}</span></div>";
        if (isset($childMap[$p['id']])) {
            foreach ($childMap[$p['id']] as $child) {
                echo "<span class='child'>🔹 {$child['cate_name']}</span>";
            }
        }
    }
    
    echo '</div></body></html>';
    
} catch (Exception $e) {
    echo '❌ 数据库错误: ' . $e->getMessage();
}