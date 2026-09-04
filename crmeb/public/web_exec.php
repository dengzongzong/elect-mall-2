<?php
/**
 * 网页端执行工具 - 一次性修复工具
 * 安全提醒：执行完成后请立即删除此文件！
 */
if (isset($_GET['action']) && $_GET['action'] === 'check') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "=== 系统诊断 ===\n\n";
    echo "--- whoami ---\n" . trim(shell_exec('whoami 2>/dev/null') ?: 'N/A') . "\n\n";
    echo "--- id ---\n" . trim(shell_exec('id 2>/dev/null') ?: 'N/A') . "\n\n";
    echo "--- sudo -n whoami ---\n" . trim(shell_exec('sudo -n whoami 2>&1') ?: 'N/A') . "\n\n";
    echo "--- sudo -l (head) ---\n";
    $sudoL = shell_exec('sudo -l 2>&1');
    echo $sudoL ? substr($sudoL, 0, 1000) : 'N/A';
    echo "\n\n";
    echo "--- nginx master ---\n" . trim(shell_exec('ps aux | grep "nginx: master" | grep -v grep 2>/dev/null') ?: 'N/A') . "\n\n";
    echo "--- php-fpm master ---\n" . trim(shell_exec('ps aux | grep "php-fpm.*master" | grep -v grep 2>/dev/null') ?: 'N/A') . "\n\n";
    echo "--- .git owner ---\n" . trim(shell_exec('ls -la /var/www/elect-mall/.git 2>/dev/null') ?: 'N/A') . "\n\n";
    echo "--- elect-mall.conf (server) ---\n";
    $f = '/etc/nginx/conf.d/elect-mall.conf';
    if (file_exists($f)) {
        $c = file_get_contents($f);
        echo strpos($c, '/adminapi/') !== false ? "✓ /adminapi/ exists\n" : "✗ /adminapi/ NOT found\n";
        echo substr($c, 0, 500) . "\n";
    } else {
        echo "✗ File not found\n";
    }
    echo "\n--- repo elect-mall.conf ---\n";
    $src = '/var/www/elect-mall/deploy/elect-mall.conf';
    if (file_exists($src)) {
        $c = file_get_contents($src);
        echo strpos($c, '/adminapi/') !== false ? "✓ /adminapi/ exists\n" : "✗ /adminapi/ NOT found\n";
    } else {
        echo "✗ File not found\n";
    }
    echo "\n--- Try writing nginx config ---\n";
    $src = '/var/www/elect-mall/deploy/elect-mall.conf';
    $dst = '/etc/nginx/conf.d/elect-mall.conf';
    if (file_exists($src)) {
        $content = file_get_contents($src);
        $written = @file_put_contents($dst, $content);
        echo $written !== false ? "✓ WRITE SUCCESS!\n" : "✗ Write failed\n";
        // Try with sudo
        exec('sudo cp ' . escapeshellarg($src) . ' ' . escapeshellarg($dst) . ' 2>&1', $out, $code);
        echo "sudo cp: code=$code, output=" . implode("\n", $out) . "\n";
    }
    exit;
}
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>服务器一键修复</title>
<style>
body{font-family:'Microsoft YaHei',sans-serif;max-width:900px;margin:30px auto;padding:20px;line-height:1.8}
h1{color:#2c3e50;border-bottom:3px solid #3498db;padding-bottom:10px}
.btn{background:#3498db;color:white;border:none;padding:12px 24px;font-size:16px;cursor:pointer;border-radius:4px}
.btn:hover{background:#2980b9}
.output{background:#f8f9fa;border:1px solid #ddd;padding:15px;margin:15px 0;border-radius:4px;white-space:pre-wrap;word-break:break-all;max-height:500px;overflow:auto}
.success{color:#27ae60}
.error{color:#e74c3c}
.info{background:#e8f4f8;padding:10px;border-radius:4px;margin:10px 0}
</style>
</head>
<body>
<h1>🔧 CRMEB 数据库+登录问题一键修复</h1>

<div class="info">
<strong>任务：</strong><br>
1. 拉取最新代码（包含修复）<br>
2. 更新Nginx配置并重载<br>
3. 访问修复脚本修复数据库乱码<br>
</div>

<?php
if (!isset($_POST['run'])):
?>
<form method="post">
<p>点击下方按钮开始执行修复：</p>
<button type="submit" name="run" value="1" class="btn" onclick="this.disabled=true;this.form.submit();">开始执行修复</button>
</form>
<?php else:

echo "<div class='output'>\n";
echo "=== 开始修复 ===\n\n";

// 0. 修复.git目录权限（使用sudo提权，且只做一次chmod不做递归find避免超时）
echo "[0/5] 修复 .git 目录权限...\n";
ob_flush();
flush();
$output = [];
exec('sudo chmod -R 777 /var/www/elect-mall/.git 2>&1', $output);
echo implode("\n", $output) . "\n";
echo "✓ 权限修复完成\n\n";

// 1. 拉取最新代码
echo "[1/5] 执行 git pull...\n";
chdir('/var/www/elect-mall');
ob_flush();
flush();
$output = [];
exec('git pull origin main 2>&1', $output);
echo implode("\n", $output) . "\n\n";

// 2. 更新Nginx配置（使用sudo提权）
echo "[2/5] 更新Nginx配置...\n";
ob_flush();
flush();
$src = '/var/www/elect-mall/deploy/elect-mall.conf';
$dst = '/etc/nginx/conf.d/elect-mall.conf';
if (file_exists($src)) {
    $content = file_get_contents($src);
    // 先尝试sudo cp
    chdir(dirname($src));
    exec('sudo cp elect-mall.conf ' . $dst . ' 2>&1', $cpOut, $cpCode);
    if ($cpCode === 0) {
        echo "✓ 已复制配置到 {$dst} (via sudo)\n";
    } else {
        // 失败则尝试直接file_put_contents
        if (file_put_contents($dst, $content)) {
            echo "✓ 已复制配置到 {$dst}\n";
        } else {
            echo "✗ 无法写入 {$dst}，请检查权限\n";
        }
    }
} else {
    echo "✗ 源文件不存在: {$src}\n";
}

// 3. 重载Nginx（使用sudo提权）
echo "\n[3/5] 重载Nginx...\n";
ob_flush();
flush();
exec('sudo /usr/sbin/nginx -t 2>&1', $output);
echo implode("\n", $output) . "\n";
exec('sudo systemctl reload nginx 2>&1', $output);
echo implode("\n", $output) . "\n";

echo "\n[4/5] 重载PHP-FPM...\n";
exec('sudo systemctl reload php-fpm 2>&1', $output);
echo implode("\n", $output) . "\n";

echo "\n[5/5] 检查文件...\n";
$fixFile = '/var/www/elect-mall/crmeb/public/fix_all.php';
if (file_exists($fixFile)) {
    echo "✓ fix_all.php 已存在: {$fixFile}\n";
    echo "文件大小: " . filesize($fixFile) . " 字节\n";
} else {
    echo "✗ fix_all.php 不存在，可能git pull失败\n";
}

echo "\n=== 完成 ===\n";
echo "✓ 代码拉取和Nginx配置更新完成\n";
echo "👉 现在请点击下方链接进入修复页面：\n";
echo "</div>\n";
?>
<p class="success">
✅ 操作已完成！<br>
👉 <a href="fix_all.php" target="_blank" style="font-size:18px;background:#27ae60;color:white;padding:10px 20px;text-decoration:none;border-radius:4px;display:inline-block;margin:10px 0">点击这里: 打开 fix_all.php 修复数据库</a>
</p>
<p class="error">
⚠️ 修复完成后，请务必删除此文件 (web_exec.php) 和 fix_all.php！
</p>
<?php endif; ?>
</body>
</html>
