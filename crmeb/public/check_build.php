<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== 检查 PC 前端构建结果 ===\n\n";

$distDir = '/var/www/elect-mall/crmeb/public/home';
$brandListFile = '/var/www/elect-mall/template/pc/pages/brand_list.vue';

if (is_dir($distDir)) {
    echo "✓ $distDir 存在\n";
    echo "文件数: " . count(scandir($distDir)) . "\n";
    $jsFiles = glob("$distDir/*.js");
    echo "JS文件: " . count($jsFiles) . "\n";
    echo "\n--- dist目录文件列表 ---\n";
    foreach (scandir($distDir) as $f) {
        if ($f !== '.' && $f !== '..') {
            echo "  $f " . (is_dir("$distDir/$f") ? "(dir)" : "(" . filesize("$distDir/$f") . " bytes)") . "\n";
        }
    }
} else {
    echo "✗ $distDir 不存在\n";
}

echo "\n--- 检查 brand_list.vue 源码 ---\n";
if (file_exists($brandListFile)) {
    echo "✓ brand_list.vue 存在，大小: " . filesize($brandListFile) . " bytes\n";
    $content = file_get_contents($brandListFile);
    if (strpos($content, '品牌列表') !== false) {
        echo "✓ 包含'品牌列表'字样\n";
    }
} else {
    echo "✗ brand_list.vue 不存在\n";
}

echo "\n--- 检查 Nuxt 构建后的 index.html ---\n";
$indexFile = '/var/www/elect-mall/crmeb/public/home/index.html';
if (file_exists($indexFile)) {
    echo "✓ index.html 存在，大小: " . filesize($indexFile) . " bytes\n";
} else {
    echo "✗ index.html 不存在\n";
}
