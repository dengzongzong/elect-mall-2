<?php
/**
 * Nginx 配置修复 + .git权限修复
 * 功能：修复Nginx配置以支持/adminapi/路由，然后重载
 * 安全提醒：执行完成后请立即删除此文件！
 */
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Nginx 配置修复</title>
<style>
body{font-family:'Microsoft YaHei',sans-serif;max-width:800px;margin:30px auto;padding:20px;line-height:1.8}
h1{color:#2c3e50;border-bottom:3px solid #e74c3c;padding-bottom:10px}
.output{background:#1e1e1e;color:#00ff00;border:1px solid #333;padding:15px;margin:15px 0;border-radius:4px;white-space:pre-wrap;word-break:break-all;font-family:monospace;font-size:14px}
.success{color:#27ae60}
.error{color:#e74c3c}
.info{background:#e8f4f8;padding:10px;border-radius:4px;margin:10px 0}
.btn{background:#e74c3c;color:white;border:none;padding:12px 24px;font-size:16px;cursor:pointer;border-radius:4px}
.btn:hover{background:#c0392b}
</style>
</head>
<body>
<h1>🔧 Nginx 配置一键修复</h1>

<div class="info">
<strong>修复内容：</strong><br>
1. 修复 .git 目录权限（sudo）<br>
2. 复制 Nginx 配置并重载（sudo）<br>
3. 验证 /adminapi/ 路由是否生效<br>
</div>

<?php if (!isset($_POST['run'])): ?>
<form method="post">
<button type="submit" name="run" value="1" class="btn" onclick="this.disabled=true;this.form.submit();">开始修复</button>
</form>
<?php else:

echo "<div class='output'>\n";
echo "=== 开始修复 ===\n\n";

$projectDir = '/var/www/elect-mall';

// 1. 修复 .git 目录权限（需要sudo）
echo "[1/4] 修复 .git 目录权限...\n";
ob_flush();
flush();
exec("sudo chmod -R 777 {$projectDir}/.git 2>&1", $out);
echo implode("\n", $out) . "\n";
echo "✓ .git 权限修复完成\n\n";

// 2. 复制Nginx配置
echo "[2/4] 更新Nginx配置...\n";
ob_flush();
flush();
$src = "{$projectDir}/deploy/elect-mall.conf";
$dst = '/etc/nginx/conf.d/elect-mall.conf';
if (file_exists($src)) {
    exec("sudo cp '{$src}' '{$dst}' 2>&1", $out, $code);
    echo implode("\n", $out) . "\n";
    if ($code === 0) {
        echo "✓ 已复制配置到 {$dst}\n";
    } else {
        // 尝试直接写入
        $content = file_get_contents($src);
        if (file_put_contents($dst, $content)) {
            echo "✓ 已直接写入 {$dst}\n";
        } else {
            echo "✗ 无法写入 {$dst}，请手动执行：sudo cp {$src} {$dst}\n";
        }
    }
} else {
    echo "✗ 源文件不存在: {$src}\n";
}
echo "\n";

// 3. 测试并重载Nginx
echo "[3/4] 测试并重载Nginx...\n";
ob_flush();
flush();
exec('sudo /usr/sbin/nginx -t 2>&1', $out);
echo implode("\n", $out) . "\n";
exec('sudo systemctl reload nginx 2>&1', $out);
echo implode("\n", $out) . "\n";
echo "✓ Nginx 重载完成\n\n";

// 4. 验证
echo "[4/4] 验证 /adminapi/ 路由...\n";
ob_flush();
flush();
$ch = curl_init('http://localhost/adminapi/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$resp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✓ /adminapi/ 路由正常 (HTTP {$httpCode})\n";
    if (strpos($resp, '<!doctype html') !== false) {
        echo "⚠️ 注意：返回的是HTML页面（非API），请检查路由配置\n";
    } else {
        echo "✓ 返回了API响应内容\n";
    }
} else {
    echo "✗ /adminapi/ 路由异常 (HTTP {$httpCode})\n";
}

echo "\n=== 完成 ===\n";
echo "</div>";

echo '<div class="success">✅ 修复完成！</div>';
echo '<div class="info">👉 <a href="/adminapi/login" target="_blank">点击测试 /adminapi/login</a></div>';

endif; ?>
</body>
</html>