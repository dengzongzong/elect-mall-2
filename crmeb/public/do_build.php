<?php
/**
 * Nuxt.js 前端构建工具
 * 访问此文件将执行 PC 前端构建（npm install + npm run generate）
 * 安全提醒：执行完成后请立即删除此文件！
 */
set_time_limit(0);
header('Content-Type: text/plain; charset=utf-8');

echo "=== Nuxt.js 前端构建工具 ===\n\n";
echo "时间: " . date('Y-m-d H:i:s') . "\n\n";

$pcDir = '/var/www/elect-mall/template/pc';
$homeDir = '/var/www/elect-mall/crmeb/public/home';

// 检查目录
if (!is_dir($pcDir)) {
    die("错误: PC前端目录不存在: {$pcDir}\n");
}

echo "[1/3] 安装 npm 依赖...\n";
chdir($pcDir);
$output = [];
$ret = 0;
exec('npm install --legacy-peer-deps 2>&1', $output, $ret);
echo implode("\n", $output) . "\n";
echo ($ret === 0 ? "✓ npm install 完成\n" : "✗ npm install 失败 (code: {$ret})\n");
echo "\n";

echo "[2/3] 构建 Nuxt.js 前端...\n";
ob_flush();
flush();
$output = [];
exec('npm run generate 2>&1', $output, $ret);
echo implode("\n", $output) . "\n";
echo ($ret === 0 ? "✓ npm run generate 完成\n" : "✗ npm run generate 失败 (code: {$ret})\n");
echo "\n";

echo "[3/3] 检查构建结果...\n";
if (is_dir($homeDir)) {
    $files = glob($homeDir . '/*/index.html');
    echo "找到 " . count($files) . " 个页面文件:\n";
    foreach ($files as $f) {
        $relPath = str_replace($homeDir, '/home', $f);
        $size = filesize($f);
        echo "  - {$relPath} ({$size} 字节)\n";
    }
    
    // 检查新页面是否存在
    $newPages = ['brand_list', 'bom_copy', 'page/sqdl'];
    foreach ($newPages as $page) {
        $pageFile = $homeDir . '/' . $page . '/index.html';
        if (file_exists($pageFile)) {
            echo "\n✓ {$page}/index.html 构建成功!";
        } else {
            echo "\n✗ {$page}/index.html 不存在!";
        }
    }
    echo "\n";
} else {
    echo "✗ 构建目录不存在: {$homeDir}\n";
}

echo "\n=== 构建完成 ===\n";
echo "时间: " . date('Y-m-d H:i:s') . "\n";