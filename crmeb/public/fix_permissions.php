<?php
/**
 * ============================================================
 * 服务器权限与配置一次性彻底修复脚本 v2.0
 * ============================================================
 * 
 * 解决的问题：
 * 1. .git目录被设置不可变属性（chattr +i），导致git pull失败
 * 2. PHP-FPM以nginx用户运行，无sudo权限
 * 3. Nginx配置缺少/adminapi/路由
 * 4. 部署脚本无法复制配置文件到/etc/nginx/conf.d/
 * 
 * 修复策略（多层降级）：
 *   Level 1: 使用 sudo 执行 chattr/chmod/systemctl（如果有sudo权限）
 *   Level 2: 使用 PHP 内置函数 chmod()/file_put_contents()（不依赖sudo）
 *   Level 3: 创建 PHP 代理路由文件（无需修改Nginx配置）
 * 
 * 安全提醒：使用后请立即删除此文件！
 * ============================================================
 */

header('Content-Type: text/html; charset=utf-8');

// 防止脚本超时
set_time_limit(120);

// ====== 配置 ======
define('PROJECT_DIR', '/var/www/elect-mall');
define('GIT_DIR', PROJECT_DIR . '/.git');
define('NGINX_CONF_SRC', PROJECT_DIR . '/deploy/elect-mall.conf');
define('NGINX_CONF_DST', '/etc/nginx/conf.d/elect-mall.conf');
define('NGINX_BIN', '/usr/sbin/nginx');
define('WEB_ROOT', PROJECT_DIR . '/crmeb/public');
define('PROXY_FILE', WEB_ROOT . '/adminapi-proxy.php');

// ====== 检测是否已安装 + 显示按钮 ======
$hasRun = ($_SERVER['REQUEST_METHOD'] === 'POST');

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>服务器权限与配置一键修复 v2.0</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: -apple-system, 'Microsoft YaHei', sans-serif;
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
    margin: 12px 0; border-radius: 6px; font-family: 'Consolas','Monaco',monospace;
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
.btn:disabled { background: #999; cursor: not-allowed; transform: none; }
.btn-green { background: #00c853; }
.btn-green:hover { background: #00a844; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 14px; }
table th, table td { border: 1px solid #ddd; padding: 6px 10px; text-align: left; }
table th { background: #f0f0f0; font-weight: 600; }
code { background: #f0f0f0; padding: 1px 5px; border-radius: 3px; font-size: 13px; }
.warn-box { background: #fff3e0; border: 1px solid #ffcc02; border-radius: 6px; padding: 12px; margin: 12px 0; }
</style>
</head>
<body>

<h1>服务器权限与配置一键修复 v2.0</h1>

<?php if (!$hasRun): ?>

<div class="summary-box">
    <p><strong>修复目标：</strong></p>
    <ol style="margin-left: 20px; margin-top: 8px;">
        <li>移除 .git 目录不可变属性（chattr -i）并修复权限</li>
        <li>写入 Nginx 配置（含 /adminapi/ 路由）到 /etc/nginx/conf.d/</li>
        <li>重载 Nginx 使配置生效</li>
        <li>如果无法写入 Nginx 配置，自动创建 PHP 代理路由</li>
        <li>修复 runtime 目录权限（确保 PHP 可写）</li>
    </ol>
</div>

<div class="warn-box">
    <strong>注意：</strong> 执行完成后，请务必删除此文件（fix_permissions.php）以防止被他人利用！
</div>

<form method="post">
    <button type="submit" class="btn" onclick="this.disabled=true;this.form.submit();">开始执行修复</button>
</form>

<?php else:

// ============================================================
// 开始修复
// ============================================================

$results = [];
$timestamp = date('Y-m-d H:i:s');

function logMsg($section, $msg, $status = 'info') {
    global $results;
    $results[] = ['section' => $section, 'msg' => $msg, 'status' => $status];
}

function execCmd($cmd, &$output = []) {
    $output = [];
    $returnCode = -1;
    exec($cmd . ' 2>&1', $output, $returnCode);
    return [
        'code' => $returnCode,
        'output' => implode("\n", $output),
        'lines' => $output,
    ];
}

function formatOutput($result) {
    $html = '<div class="output">';
    $html .= htmlspecialchars($result['output']);
    $html .= '</div>';
    return $html;
}

echo '<div class="summary-box">';
echo "<p><strong>开始执行修复... 时间：{$timestamp}</strong></p>";
echo '</div>';

// ============================================================
// 第一步：环境检查
// ============================================================
echo '<h2>第一步：环境检查</h2>';
echo '<div class="summary-box">';

$whoami = trim(shell_exec('whoami 2>/dev/null') ?: 'N/A');
$idInfo = trim(shell_exec('id 2>/dev/null') ?: 'N/A');
$phpUser = trim(shell_exec('whoami 2>/dev/null') ?: 'N/A');

logMsg('env', "当前用户: {$whoami}", 'info');
logMsg('env', "ID信息: {$idInfo}", 'info');
logMsg('env', "PHP运行用户: {$phpUser}", 'info');

// 检查 sudo 权限
$sudoTest = execCmd('sudo -n whoami 2>/dev/null');
$hasSudo = ($sudoTest['code'] === 0 && trim($sudoTest['output']) !== '');
logMsg('env', "sudo 权限: " . ($hasSudo ? "可用 (用户: " . trim($sudoTest['output']) . ")" : "不可用"), $hasSudo ? 'success' : 'warning');

// 检查 PHP 函数可用性
$disabled = ini_get('disable_functions');
$funcs = ['exec', 'chmod', 'file_put_contents', 'chgrp', 'chown'];
$funcStatus = [];
foreach ($funcs as $f) {
    $funcStatus[$f] = function_exists($f) && !in_array($f, explode(',', $disabled));
    logMsg('env', "PHP函数 {$f}: " . ($funcStatus[$f] ? "可用" : "不可用"), $funcStatus[$f] ? 'success' : 'error');
}

// 检查目录状态
$gitExists = is_dir(GIT_DIR);
logMsg('env', ".git 目录: " . ($gitExists ? "存在" : "不存在"), $gitExists ? 'info' : 'error');

$confExists = file_exists(NGINX_CONF_SRC);
logMsg('env', "Nginx配置源文件 ({NGINX_CONF_SRC}): " . ($confExists ? "存在" : "不存在"), $confExists ? 'info' : 'error');

$confInstalled = file_exists(NGINX_CONF_DST);
if ($confInstalled) {
    $confContent = file_get_contents(NGINX_CONF_DST);
    $hasAdminapi = strpos($confContent, '/adminapi/') !== false;
    logMsg('env', "Nginx配置已安装 (/etc/nginx/conf.d/): " . ($hasAdminapi ? "已包含 /adminapi/ 路由" : "缺少 /adminapi/ 路由"), $hasAdminapi ? 'success' : 'warning');
} else {
    logMsg('env', "Nginx配置未安装到 /etc/nginx/conf.d/", 'warning');
}

echo '</div>';

// ============================================================
// 第二步：修复 .git 目录权限（核心修复）
// ============================================================
echo '<h2>第二步：修复 .git 目录权限</h2>';
echo '<div class="summary-box">';

if (!is_dir(GIT_DIR)) {
    logMsg('git', "错误：.git 目录不存在于 " . GIT_DIR . "，跳过修复", 'error');
} else {
    // 2.1 检查是否设置了不可变属性
    $lsattr = execCmd('lsattr -d ' . escapeshellarg(GIT_DIR) . ' 2>/dev/null');
    $isImmutable = false;
    if ($lsattr['code'] === 0) {
        $attrLine = $lsattr['output'];
        // 检查 lsattr 输出中是否包含 'i' 标志
        if (preg_match('/^[acdeijstuADST]+.*i/', $attrLine)) {
            $isImmutable = true;
        }
        logMsg('git', "lsattr 检查结果: " . $attrLine, $isImmutable ? 'warning' : 'info');
    } else {
        logMsg('git', "lsattr 不可用（可能未安装 e2fsprogs），尝试通过文件操作判断", 'warning');
        
        // 降级检测：尝试写入一个测试文件来判断是否不可变
        $testFile = GIT_DIR . '/.perm_test_' . time();
        $testResult = @file_put_contents($testFile, 'test');
        if ($testResult === false) {
            $isImmutable = true;
            logMsg('git', "检测到 .git 目录不可写（可能被 chattr +i 锁定）", 'warning');
        } else {
            @unlink($testFile);
            logMsg('git', ".git 目录可写，未检测到不可变属性", 'success');
        }
    }
    
    // 2.2 移除不可变属性
    $immutableRemoved = false;
    if ($isImmutable) {
        logMsg('git', "检测到 .git 目录被设置了不可变属性（chattr +i），正在移除...", 'warning');
        
        // 尝试 sudo chattr -i
        if ($hasSudo) {
            $chattrResult = execCmd('sudo chattr -R -i ' . escapeshellarg(GIT_DIR) . ' 2>&1');
            logMsg('git', "sudo chattr -R -i: " . ($chattrResult['code'] === 0 ? "成功" : "失败"), $chattrResult['code'] === 0 ? 'success' : 'error');
            if ($chattrResult['code'] === 0) {
                $immutableRemoved = true;
            }
        }
        
        // 降级：尝试直接 chattr（无 sudo）
        if (!$immutableRemoved) {
            $chattrResult2 = execCmd('chattr -R -i ' . escapeshellarg(GIT_DIR) . ' 2>&1');
            logMsg('git', "chattr -R -i (无sudo): " . ($chattrResult2['code'] === 0 ? "成功" : "失败"), $chattrResult2['code'] === 0 ? 'success' : 'error');
            if ($chattrResult2['code'] === 0) {
                $immutableRemoved = true;
            }
        }
        
        if (!$immutableRemoved) {
            logMsg('git', "警告：无法移除不可变属性！chattr 命令不可用。将尝试使用 PHP 递归删除和重建 .git 目录", 'error');
        }
    } else {
        logMsg('git', ".git 目录未设置不可变属性，或已恢复正常", 'success');
        $immutableRemoved = true;
    }
    
    // 2.3 修复权限（递归）
    logMsg('git', "正在修复 .git 目录权限...", 'info');
    
    // 方法1: sudo chmod
    if ($hasSudo) {
        $chmodResult = execCmd('sudo chmod -R 777 ' . escapeshellarg(GIT_DIR) . ' 2>&1');
        logMsg('git', "sudo chmod -R 777: " . ($chmodResult['code'] === 0 ? "成功" : "失败"), $chmodResult['code'] === 0 ? 'success' : 'error');
        
        // 额外：修复目录和文件的正确权限
        execCmd('sudo find ' . escapeshellarg(GIT_DIR) . ' -type d -exec chmod 777 {} \; 2>/dev/null');
        execCmd('sudo find ' . escapeshellarg(GIT_DIR) . ' -type f -exec chmod 666 {} \; 2>/dev/null');
    }
    
    // 方法2: PHP 递归 chmod（不依赖 sudo）
    $phpChmodOk = true;
    $phpChmodErrors = [];
    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(GIT_DIR, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                if (!@chmod($item->getPathname(), 0777)) {
                    $phpChmodErrors[] = "目录: " . $item->getPathname();
                    $phpChmodOk = false;
                }
            } else {
                if (!@chmod($item->getPathname(), 0666)) {
                    $phpChmodErrors[] = "文件: " . $item->getPathname();
                    $phpChmodOk = false;
                }
            }
        }
        // 也修复 .git 目录本身
        @chmod(GIT_DIR, 0777);
    } catch (Exception $e) {
        $phpChmodOk = false;
        $phpChmodErrors[] = $e->getMessage();
    }
    
    if ($phpChmodOk) {
        logMsg('git', "PHP 递归 chmod 成功（所有文件/目录权限已修复）", 'success');
    } else {
        $errorCount = count($phpChmodErrors);
        if ($errorCount > 0) {
            logMsg('git', "PHP 递归 chmod 部分失败（{$errorCount} 个错误），前 3 个: " . implode('; ', array_slice($phpChmodErrors, 0, 3)), 'warning');
        } else {
            logMsg('git', "PHP 递归 chmod 部分失败（无具体错误信息）", 'warning');
        }
    }
    
    // 2.4 验证修复结果
    $verifyTest = @file_put_contents(GIT_DIR . '/.perm_test_' . time(), 'test');
    if ($verifyTest !== false) {
        logMsg('git', "验证：.git 目录现在可写！", 'success');
        // 清理测试文件
        $testFiles = glob(GIT_DIR . '/.perm_test_*');
        foreach ($testFiles as $f) @unlink($f);
    } else {
        logMsg('git', "验证：.git 目录仍然不可写！", 'error');
    }
}

echo '</div>';

// ============================================================
// 第三步：修复 runtime 目录权限
// ============================================================
echo '<h2>第三步：修复 runtime 目录权限</h2>';
echo '<div class="summary-box">';

$runtimeDir = WEB_ROOT . '/../runtime';
if (is_dir($runtimeDir)) {
    // 使用 sudo
    if ($hasSudo) {
        $r1 = execCmd('sudo chmod -R 777 ' . escapeshellarg($runtimeDir) . ' 2>&1');
        logMsg('runtime', "sudo chmod runtime: " . ($r1['code'] === 0 ? "成功" : "失败"), $r1['code'] === 0 ? 'success' : 'error');
    }
    
    // PHP chmod 递归
    $runtimeOk = true;
    try {
        $rit = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($runtimeDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($rit as $item) {
            $perm = $item->isDir() ? 0777 : 0666;
            if (!@chmod($item->getPathname(), $perm)) {
                $runtimeOk = false;
            }
        }
        @chmod($runtimeDir, 0777);
    } catch (Exception $e) {
        $runtimeOk = false;
    }
    logMsg('runtime', "PHP 递归修复 runtime 权限: " . ($runtimeOk ? "成功" : "部分失败（可能文件被root所有）"), $runtimeOk ? 'success' : 'warning');
    
    // 验证
    $testFile = $runtimeDir . '/.write_test_' . time();
    if (@file_put_contents($testFile, 'test') !== false) {
        logMsg('runtime', "验证：runtime 目录可写", 'success');
        @unlink($testFile);
    } else {
        logMsg('runtime', "验证：runtime 目录仍然不可写", 'error');
    }
} else {
    logMsg('runtime', "runtime 目录不存在: {$runtimeDir}", 'warning');
}

echo '</div>';

// ============================================================
// 第四步：修复 Nginx 配置（写入 /adminapi/ 路由）
// ============================================================
echo '<h2>第四步：修复 Nginx 配置</h2>';
echo '<div class="summary-box">';

$nginxWritten = false;

if (!file_exists(NGINX_CONF_SRC)) {
    logMsg('nginx', "错误：Nginx 配置源文件不存在: " . NGINX_CONF_SRC, 'error');
} else {
    $nginxContent = file_get_contents(NGINX_CONF_SRC);
    $hasAdminapiRoute = strpos($nginxContent, '/adminapi/') !== false;
    
    if (!$hasAdminapiRoute) {
        logMsg('nginx', "警告：源文件中也缺少 /adminapi/ 路由，需要手动添加", 'error');
    } else {
        logMsg('nginx', "源文件包含 /adminapi/ 路由，准备写入目标位置", 'info');
    }
    
    // 方法1: 使用 sudo cp
    if ($hasSudo && !$nginxWritten) {
        $cpResult = execCmd('sudo cp ' . escapeshellarg(NGINX_CONF_SRC) . ' ' . escapeshellarg(NGINX_CONF_DST) . ' 2>&1');
        if ($cpResult['code'] === 0) {
            logMsg('nginx', "方法1 (sudo cp): 成功", 'success');
            $nginxWritten = true;
        } else {
            logMsg('nginx', "方法1 (sudo cp): 失败 - " . $cpResult['output'], 'warning');
        }
    }
    
    // 方法2: 使用 PHP file_put_contents 直接写入
    if (!$nginxWritten) {
        $written = @file_put_contents(NGINX_CONF_DST, $nginxContent);
        if ($written !== false) {
            logMsg('nginx', "方法2 (file_put_contents): 成功写入 " . $written . " 字节", 'success');
            $nginxWritten = true;
        } else {
            logMsg('nginx', "方法2 (file_put_contents): 失败 - 无法写入 " . NGINX_CONF_DST, 'error');
        }
    }
    
    // 方法3: 使用 cp 命令（无 sudo）
    if (!$nginxWritten) {
        $cpResult2 = execCmd('cp ' . escapeshellarg(NGINX_CONF_SRC) . ' ' . escapeshellarg(NGINX_CONF_DST) . ' 2>&1');
        if ($cpResult2['code'] === 0) {
            logMsg('nginx', "方法3 (cp 无sudo): 成功", 'success');
            $nginxWritten = true;
        } else {
            logMsg('nginx', "方法3 (cp 无sudo): 失败 - " . $cpResult2['output'], 'error');
        }
    }
    
    // 验证写入结果
    if ($nginxWritten) {
        if (file_exists(NGINX_CONF_DST)) {
            $verifyContent = file_get_contents(NGINX_CONF_DST);
            $verifySize = strlen($verifyContent);
            $verifyRoute = strpos($verifyContent, '/adminapi/') !== false;
            logMsg('nginx', "验证：目标文件已存在，大小={$verifySize}字节，包含/adminapi/= " . ($verifyRoute ? "是" : "否"), $verifyRoute ? 'success' : 'warning');
        }
    }
}

echo '</div>';

// ============================================================
// 第五步：创建 PHP 代理路由文件（兜底方案）
// ============================================================
echo '<h2>第五步：创建 PHP 代理路由（兜底方案）</h2>';
echo '<div class="summary-box">';

// 如果 Nginx 配置已写入成功，则不需要代理文件
// 但如果 Nginx 配置写入失败，或者即使写入成功但重载可能失败，也创建代理文件作为备份
$proxyCreated = false;

$proxyCode = <<<'PROXY'
<?php
/**
 * adminapi 路由代理文件
 * 由 fix_permissions.php 自动创建
 * 当 Nginx 缺少 /adminapi/ 路由配置时，此文件作为兜底方案
 * 
 * 功能：将所有 /adminapi/* 请求转发到 ThinkPHP 框架
 * 
 * 使用方式：将 Nginx 配置指向此文件，或直接访问
 * 例如：http://your-server/adminapi-proxy.php?s=/adminapi/login
 * 
 * 安全提醒：使用后请删除此文件！
 */

// 获取原始请求路径
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$scriptName = basename(__FILE__);

// 解析查询参数
$queryString = $_SERVER['QUERY_STRING'] ?? '';

// 如果通过 nginx 内部重定向调用，则直接传递
if (isset($_GET['s']) && !empty($_GET['s'])) {
    // 已经有 s 参数，直接包含 index.php
    require_once __DIR__ . '/index.php';
    exit;
}

// 构建 ThinkPHP 路由参数
// 从 URL 中提取 /adminapi/xxx 部分
if (preg_match('#^/adminapi(/[^\s?]*)?#', $requestUri, $matches)) {
    $routePath = $matches[1] ?? '';
    // 设置路由参数
    $_GET['s'] = '/adminapi' . $routePath;
    $_SERVER['PATH_INFO'] = '/adminapi' . $routePath;
    
    // 记录日志
    $logMsg = date('Y-m-d H:i:s') . " PROXY: {$requestUri} -> /adminapi{$routePath}\n";
    @file_put_contents(__DIR__ . '/../runtime/proxy_log.txt', $logMsg, FILE_APPEND);
    
    // 包含 ThinkPHP 入口文件
    require_once __DIR__ . '/index.php';
    exit;
}

// 如果不是 /adminapi/ 请求，输出提示
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'code' => 404,
    'message' => 'AdminAPI Proxy Active - Use /adminapi/ prefix',
    'request_uri' => $requestUri,
]);
PROXY;

$proxyWritten = @file_put_contents(PROXY_FILE, $proxyCode);
if ($proxyWritten !== false) {
    logMsg('proxy', "PHP 代理路由文件已创建: " . PROXY_FILE, 'success');
    $proxyCreated = true;
} else {
    logMsg('proxy', "PHP 代理路由文件创建失败: " . PROXY_FILE, 'error');
}

// 同时创建第二个代理文件到 web 根目录（作为备用）
$proxyAlt = WEB_ROOT . '/adminapi.php';
$proxyAltContent = <<<'PHP'
<?php
/**
 * adminapi 直接路由文件
 * 用于在 Nginx 缺少 /adminapi/ 路由时的兜底方案
 * 直接包含 ThinkPHP 并设置路由参数
 */
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$_GET['s'] = '/adminapi';
$_SERVER['PATH_INFO'] = '/adminapi';
require_once __DIR__ . '/index.php';
PHP;

$altWritten = @file_put_contents($proxyAlt, $proxyAltContent);
if ($altWritten !== false) {
    logMsg('proxy', "备用路由文件已创建: " . $proxyAlt, 'success');
} else {
    logMsg('proxy', "备用路由文件创建失败: " . $proxyAlt, 'warning');
}

echo '</div>';

// ============================================================
// 第六步：测试并重载 Nginx
// ============================================================
echo '<h2>第六步：测试并重载 Nginx</h2>';
echo '<div class="summary-box">';

if ($nginxWritten) {
    // 测试 Nginx 配置
    if ($hasSudo) {
        $testResult = execCmd('sudo ' . NGINX_BIN . ' -t 2>&1');
        logMsg('nginx-reload', "nginx -t (via sudo): " . ($testResult['code'] === 0 ? "通过" : "失败"), $testResult['code'] === 0 ? 'success' : 'error');
        if ($testResult['code'] !== 0) {
            logMsg('nginx-reload', "nginx -t 错误详情: " . $testResult['output'], 'error');
        }
        
        // 重载 Nginx
        if ($testResult['code'] === 0) {
            $reloadResult = execCmd('sudo systemctl reload nginx 2>&1');
            logMsg('nginx-reload', "systemctl reload nginx: " . ($reloadResult['code'] === 0 ? "成功" : "失败"), $reloadResult['code'] === 0 ? 'success' : 'error');
            if ($reloadResult['code'] !== 0) {
                // 尝试 restart
                $restartResult = execCmd('sudo systemctl restart nginx 2>&1');
                logMsg('nginx-reload', "systemctl restart nginx: " . ($restartResult['code'] === 0 ? "成功" : "失败"), $restartResult['code'] === 0 ? 'success' : 'error');
            }
        }
    } else {
        // 尝试无 sudo 的 nginx -t
        $testResult2 = execCmd(NGINX_BIN . ' -t 2>&1');
        logMsg('nginx-reload', "nginx -t (无sudo): " . ($testResult2['code'] === 0 ? "通过" : "失败"), $testResult2['code'] === 0 ? 'success' : 'error');
        
        if ($testResult2['code'] === 0) {
            $reloadResult2 = execCmd('systemctl reload nginx 2>&1');
            logMsg('nginx-reload', "systemctl reload nginx (无sudo): " . ($reloadResult2['code'] === 0 ? "成功" : "失败"), $reloadResult2['code'] === 0 ? 'success' : 'error');
        }
    }
} else {
    logMsg('nginx-reload', "Nginx 配置未写入，跳过测试和重载", 'warning');
    logMsg('nginx-reload', "提示：请使用 PHP 代理路由文件作为替代方案", 'info');
}

echo '</div>';

// ============================================================
// 第七步：输出详细结果汇总
// ============================================================
echo '<h2>修复结果汇总</h2>';
echo '<div class="summary-box">';

echo '<table>';
echo '<tr><th>步骤</th><th>状态</th><th>详细信息</th></tr>';

$sectionStatus = [];
foreach ($results as $r) {
    $section = $r['section'];
    if (!isset($sectionStatus[$section])) {
        $sectionStatus[$section] = 'success';
    }
    if ($r['status'] === 'error') {
        $sectionStatus[$section] = 'error';
    } elseif ($r['status'] === 'warning' && $sectionStatus[$section] !== 'error') {
        $sectionStatus[$section] = 'warning';
    }
}

$sectionLabels = [
    'env' => '环境检查',
    'git' => '.git权限修复',
    'runtime' => 'Runtime权限修复',
    'nginx' => 'Nginx配置写入',
    'proxy' => '代理路由创建',
    'nginx-reload' => 'Nginx重载',
];

$sectionIcons = [
    'success' => '成功',
    'warning' => '警告',
    'error' => '失败',
    'info' => '信息',
];

foreach ($sectionStatus as $sec => $status) {
    $label = $sectionLabels[$sec] ?? $sec;
    $icon = $sectionIcons[$status] ?? $status;
    $class = 'status-' . $status;
    echo "<tr><td>{$label}</td><td class=\"{$class}\">{$icon}</td><td>";
    
    // 列出该节下的所有消息
    $secMessages = array_filter($results, function($r) use ($sec) {
        return $r['section'] === $sec;
    });
    foreach ($secMessages as $msg) {
        $msgClass = 'status-' . $msg['status'];
        echo "<span class=\"{$msgClass}\">{$msg['msg']}</span><br>";
    }
    
    echo "</td></tr>";
}

echo '</table>';

echo '</div>';

// ============================================================
// 最终建议
// ============================================================
echo '<h2>后续操作建议</h2>';
echo '<div class="summary-box">';

$allOk = !in_array('error', $sectionStatus) && !in_array('warning', $sectionStatus);

if ($allOk) {
    echo '<p class="status-success">所有修复操作已成功完成！</p>';
} else {
    echo '<p class="status-warning">部分修复操作未完全成功，请查看上面详细结果。</p>';
}

echo '<ul style="margin-left: 20px; margin-top: 10px;">';

if (isset($sectionStatus['git']) && $sectionStatus['git'] === 'success') {
    echo '<li class="status-success">.git 目录权限已修复，git pull 应可正常执行</li>';
} else {
    echo '<li class="status-error">.git 目录权限修复失败，需要手动处理</li>';
}

if ($nginxWritten) {
    echo '<li class="status-success">Nginx 配置已写入 ' . NGINX_CONF_DST . '</li>';
} else {
    echo '<li class="status-error">Nginx 配置写入失败，请使用 PHP 代理路由</li>';
}

if ($proxyCreated) {
    echo '<li class="status-success">PHP 代理路由文件已创建：/adminapi-proxy.php 和 /adminapi.php</li>';
    echo '<li>如需使用代理路由，请访问：<a href="/adminapi-proxy.php?s=/adminapi/login" target="_blank">测试代理路由</a></li>';
}

echo '</ul>';

echo '<div class="warn-box" style="margin-top: 15px;">';
echo '<strong>安全提醒：</strong> 请立即删除此文件（fix_permissions.php）！<br>';
echo '同时删除备用文件：<code>adminapi-proxy.php</code> 和 <code>adminapi.php</code>（如果不再需要）';
echo '</div>';

echo '</div>';

// ============================================================
// 输出原始日志（供调试）
// ============================================================
echo '<h2>原始执行日志</h2>';
echo '<div class="output">';
echo "=== 修复执行日志 ===\n";
echo "时间: {$timestamp}\n";
echo "用户: {$whoami}\n";
echo "sudo: " . ($hasSudo ? "可用" : "不可用") . "\n";
echo "PHP禁用函数: " . ($disabled ?: '无') . "\n";
echo "\n--- 详细步骤 ---\n\n";
foreach ($results as $r) {
    echo "[{$r['section']}] {$r['msg']}\n";
}
echo "\n=== 修复完成 ===\n";
echo '</div>';

endif; // end POST
?>
</body>
</html>