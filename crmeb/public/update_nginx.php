<?php
/**
 * 更新Nginx配置并重载
 */
header('Content-Type: text/plain; charset=utf-8');
set_time_limit(30);

echo "=== 更新Nginx配置 ===\n\n";

$src = '/var/www/elect-mall/deploy/elect-mall.conf';
$dst = '/etc/nginx/conf.d/elect-mall.conf';

// 检查源文件
if (!file_exists($src)) {
    echo "✗ 源文件不存在: $src\n";
    exit(1);
}

// 读取新配置内容
$content = file_get_contents($src);
echo "✓ 读取配置: $src\n";
echo "  大小: " . strlen($content) . " 字节\n";

// 复制配置
echo "\n[1/2] 复制配置到 $dst ...\n";
exec('sudo cp ' . escapeshellarg($src) . ' ' . escapeshellarg($dst) . ' 2>&1', $out, $code);
echo implode("\n", $out) . "\n";
echo $code === 0 ? "✓ 复制成功\n" : "✗ 复制失败(code=$code)\n";

// 检查目标配置
$dstContent = file_get_contents($dst);
if (strpos($dstContent, 'rewrite ^/(.+)\.html\$ /home/\$1/index.html last;') !== false) {
    echo "✓ 目标配置已包含.html路由规则\n";
} else {
    echo "⚠ 目标配置未包含.html路由规则，可能不是最新版本\n";
    echo "   源文件内容片段:\n";
    echo "   " . substr($content, 0, 200) . "\n";
}

// 测试Nginx配置
echo "\n[2/2] 测试并重载Nginx...\n";
exec('sudo /usr/sbin/nginx -t 2>&1', $out, $code);
echo implode("\n", $out) . "\n";
if ($code !== 0) {
    echo "✗ Nginx配置测试失败，跳过重载\n";
    exit(1);
}
echo "✓ Nginx配置测试通过\n";

exec('sudo systemctl reload nginx 2>&1', $out, $code);
echo implode("\n", $out) . "\n";
echo $code === 0 ? "✓ Nginx重载成功\n" : "✗ Nginx重载失败(code=$code)\n";

echo "\n=== 完成 ===\n";
echo "请访问以下URL测试:\n";
echo "  http://134.175.246.242/brand_list.html\n";
echo "  http://134.175.246.242/bom_copy.html\n";
echo "  http://134.175.246.242/authorized_dealer.html\n";